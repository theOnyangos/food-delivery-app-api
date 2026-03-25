<?php

namespace App\Services;

use Closure;

class MealCacheService
{
    private readonly RedisService $redis;

    private readonly int $ttl;

    private readonly string $tag;

    private readonly string $prefix;

    public function __construct(
        RedisService $redis,
        ?int $ttl = null,
        ?string $tag = null,
        ?string $prefix = null
    ) {
        $this->redis = $redis;
        $this->ttl = $ttl ?? (int) config('meals.cache_ttl', 600);
        $this->tag = $tag ?? (string) config('meals.cache_tag', 'meals');
        $this->prefix = $prefix ?? (string) config('meals.cache_prefix', 'asl:meals:');
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function publishedList(string $userId, array $filters, Closure $callback): mixed
    {
        $key = $this->key('published_list_user_'.$userId.'_'.md5((string) json_encode($filters)));

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function manageableList(string $userId, array $filters, Closure $callback): mixed
    {
        $key = $this->key('manageable_list_user_'.$userId.'_'.md5((string) json_encode($filters)));

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    public function publishedMeal(string $mealId, string $viewerId, bool $isProViewer, Closure $callback): mixed
    {
        $key = $this->key('published_meal_'.$mealId.'_viewer_'.$viewerId.'_pro_'.($isProViewer ? '1' : '0'));

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    public function manageableMeal(string $mealId, string $userId, Closure $callback): mixed
    {
        $key = $this->key('manageable_meal_'.$mealId.'_user_'.$userId);

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    public function invalidate(): bool
    {
        return $this->redis->flushTag($this->tag);
    }

    private function key(string $suffix): string
    {
        return $this->prefix.$suffix;
    }
}
