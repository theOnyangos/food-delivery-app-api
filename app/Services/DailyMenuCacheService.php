<?php

declare(strict_types=1);

namespace App\Services;

use Closure;

class DailyMenuCacheService
{
    private readonly RedisService $redis;

    private readonly int $ttl;

    private readonly int $statsTtl;

    private readonly string $tag;

    private readonly string $prefix;

    public function __construct(
        RedisService $redis,
        ?int $ttl = null,
        ?int $statsTtl = null,
        ?string $tag = null,
        ?string $prefix = null
    ) {
        $this->redis = $redis;
        $this->ttl = $ttl ?? (int) config('daily_menus.cache_ttl', 600);
        $this->statsTtl = $statsTtl ?? (int) config('daily_menus.stats_cache_ttl', 60);
        $this->tag = $tag ?? (string) config('daily_menus.cache_tag', 'daily_menus');
        $this->prefix = $prefix ?? (string) config('daily_menus.cache_prefix', 'asl:daily_menus:');
    }

    /**
     * @param  Closure(): mixed  $callback
     */
    public function rememberEffective(string $dateYmd, Closure $callback): mixed
    {
        $key = $this->key('effective_'.$dateYmd);

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    /**
     * @param  Closure(): mixed  $callback
     */
    public function rememberAdminShow(string $dailyMenuId, Closure $callback): mixed
    {
        $key = $this->key('admin_show_'.$dailyMenuId);

        return $this->redis->rememberWithTags($this->tag, $key, $this->ttl, $callback);
    }

    /**
     * @param  Closure(): mixed  $callback
     */
    public function rememberStatsSummary(string $fingerprint, Closure $callback): mixed
    {
        $key = $this->key('stats_'.$fingerprint);

        return $this->redis->rememberWithTags($this->tag, $key, $this->statsTtl, $callback);
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
