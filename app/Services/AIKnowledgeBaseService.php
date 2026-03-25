<?php

namespace App\Services;

use App\Models\AiKbChunk;
use App\Models\AiKbSource;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Smalot\PdfParser\Parser;

class AIKnowledgeBaseService
{
    /**
     * Ingest one source into chunks. Returns ['success' => bool, 'source_id' => string, 'chunks' => int|'error' => string].
     */
    public function ingestSource(string $sourceId): array
    {
        $source = AiKbSource::query()->find($sourceId);
        if (! $source) {
            return [
                'success' => false,
                'source_id' => $sourceId,
                'error' => 'Source not found',
            ];
        }

        $now = now();

        try {
            $rawText = $this->extractSourceText($source);
            $rawText = $this->normalizeText($rawText);

            if (mb_strlen($rawText) < 50) {
                throw new \RuntimeException('Extracted text is too short to ingest.');
            }

            $source->update([
                'content_raw' => $rawText,
                'last_ingested_at' => $now,
                'ingest_error' => null,
            ]);

            AiKbChunk::query()->where('source_id', $sourceId)->delete();

            $chunks = $this->chunkText($rawText, 1200);
            $chunkCount = 0;

            foreach ($chunks as $idx => $chunk) {
                AiKbChunk::query()->create([
                    'source_id' => $sourceId,
                    'chunk_index' => $idx,
                    'content' => $chunk,
                    'metadata' => [
                        'source_id' => $sourceId,
                        'source_type' => $source->type,
                        'title' => $source->title,
                        'source_url' => $source->source_url,
                        'file_path' => $source->file_path,
                        'chunk_index' => $idx,
                    ],
                ]);
                $chunkCount++;
            }

            return [
                'success' => true,
                'source_id' => $sourceId,
                'chunks' => $chunkCount,
            ];
        } catch (\Throwable $e) {
            $source->update([
                'last_ingested_at' => $now,
                'ingest_error' => $e->getMessage(),
            ]);
            Log::error('AI KB ingestion failed for '.$sourceId.': '.$e->getMessage());

            return [
                'success' => false,
                'source_id' => $sourceId,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Ingest all active (or all) sources. Returns ['success' => true, 'results' => array].
     */
    public function ingestAll(bool $includeDisabled = false): array
    {
        $query = AiKbSource::query()->orderByDesc('updated_at');
        if (! $includeDisabled) {
            $query->where('status', 'active');
        }
        $sources = $query->get();
        $results = [];

        foreach ($sources as $src) {
            $results[] = $this->ingestSource($src->id);
        }

        return [
            'success' => true,
            'results' => $results,
        ];
    }

    protected function extractSourceText(AiKbSource $source): string
    {
        $type = $source->type ?? 'text';

        if ($type === 'text') {
            return (string) ($source->content_raw ?? '');
        }

        if ($type === 'url') {
            $url = (string) ($source->source_url ?? '');
            if ($url === '') {
                throw new \InvalidArgumentException('source_url is required for url sources.');
            }
            $response = Http::timeout(15)
                ->withHeaders(['User-Agent' => 'AmazingSouls-AI-KB-Ingest/1.0'])
                ->get($url);
            if (! $response->successful()) {
                throw new \RuntimeException('Failed to fetch URL. HTTP '.$response->status());
            }

            return $this->htmlToText($response->body());
        }

        if ($type === 'file') {
            $filePath = (string) ($source->file_path ?? '');
            if ($filePath === '') {
                throw new \InvalidArgumentException('file_path is required for file sources.');
            }
            $abs = storage_path('app/'.ltrim($filePath, '/'));
            if (! is_file($abs)) {
                throw new \RuntimeException('File not found at '.$abs);
            }
            $ext = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
            if (in_array($ext, ['txt', 'md'], true)) {
                return (string) file_get_contents($abs);
            }
            if ($ext === 'pdf') {
                if (! class_exists(Parser::class)) {
                    throw new \RuntimeException('PDF parsing dependency missing. Please install smalot/pdfparser.');
                }
                $parser = new Parser;
                $pdf = $parser->parseFile($abs);

                return (string) $pdf->getText();
            }
            throw new \RuntimeException('Unsupported file type: '.$ext);
        }

        throw new \RuntimeException('Unsupported source type: '.$type);
    }

    protected function htmlToText(string $html): string
    {
        $html = preg_replace('#<script[^>]*>.*?</script>#is', ' ', $html) ?? $html;
        $html = preg_replace('#<style[^>]*>.*?</style>#is', ' ', $html) ?? $html;
        $html = preg_replace('#</(p|div|h1|h2|h3|h4|h5|h6|li|br|tr)>#i', "\n", $html) ?? $html;
        $text = strip_tags($html);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return $text;
    }

    protected function normalizeText(string $text): string
    {
        $text = str_replace(["\r\n", "\r"], "\n", $text);
        $text = preg_replace("/[ \t]+/", ' ', $text) ?? $text;
        $text = preg_replace("/\n{3,}/", "\n\n", $text) ?? $text;

        return trim($text);
    }

    /**
     * Chunk by paragraphs up to max chars.
     *
     * @return string[]
     */
    protected function chunkText(string $text, int $maxChars = 1200): array
    {
        $paragraphs = preg_split("/\n\s*\n/", $text) ?: [];
        $chunks = [];
        $current = '';

        foreach ($paragraphs as $p) {
            $p = trim($p);
            if ($p === '') {
                continue;
            }
            $candidate = $current === '' ? $p : ($current."\n\n".$p);
            if (mb_strlen($candidate) <= $maxChars) {
                $current = $candidate;

                continue;
            }
            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }
            while (mb_strlen($p) > $maxChars) {
                $chunks[] = mb_substr($p, 0, $maxChars);
                $p = mb_substr($p, $maxChars);
                $p = ltrim($p);
            }
            $current = $p;
        }
        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_values(array_filter($chunks, fn ($c) => mb_strlen(trim($c)) >= 50));
    }
}
