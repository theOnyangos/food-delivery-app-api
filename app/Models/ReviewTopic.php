<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ReviewTopic extends Model
{
    use HasUuids;

    protected $table = 'asl_review_topics';

    protected $fillable = [
        'name',
        'slug',
        'sort_order',
    ];

    public function reviews(): BelongsToMany
    {
        return $this->belongsToMany(Review::class, 'asl_review_review_topic', 'review_topic_id', 'review_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }
}
