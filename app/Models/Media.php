<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Media extends Model
{
    use HasUuids;

    protected $table = 'asl_media';

    protected $fillable = [
        'model_type',
        'model_id',
        'collection_name',
        'file_name',
        'disk',
        'path',
        'mime_type',
        'size',
        'custom_properties',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'custom_properties' => 'array',
            'size' => 'integer',
        ];
    }

    public function model(): MorphTo
    {
        return $this->morphTo();
    }
}
