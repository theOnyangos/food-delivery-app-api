<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiKbChunk extends Model
{
    use HasUuids;

    protected $table = 'asl_ai_kb_chunks';

    public $timestamps = false;

    protected $fillable = [
        'source_id',
        'chunk_index',
        'content',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(AiKbSource::class, 'source_id');
    }
}
