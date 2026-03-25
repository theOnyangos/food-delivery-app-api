<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AiKbSource extends Model
{
    use HasUuids;

    protected $table = 'asl_ai_kb_sources';

    protected $fillable = [
        'type',
        'title',
        'source_url',
        'file_path',
        'content_raw',
        'status',
        'created_by',
        'last_ingested_at',
        'ingest_error',
    ];

    protected function casts(): array
    {
        return [
            'last_ingested_at' => 'datetime',
        ];
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(AiKbChunk::class, 'source_id');
    }
}
