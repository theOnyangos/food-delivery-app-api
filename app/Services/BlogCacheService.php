<?php

declare(strict_types=1);

namespace App\Services;

use Closure;

class BlogCacheService
{
    public function __construct(
        private readonly RedisService $redis
    ) {}

    /**
     * @param  Closure(): mixed  $callback
     */
    public function remember(string $key, int $ttl, Closure $callback): mixed
    {
        $tag = (string) config('blogs.public_tag', 'blogs_public');

        return $this->redis->rememberWithTags($tag, $key, $ttl, $callback);
    }

    public function flushPublic(): bool
    {
        $tag = (string) config('blogs.public_tag', 'blogs_public');

        return $this->redis->flushTag($tag);
    }
}
