<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class RedisService
{
    private readonly string $store;

    private readonly string $keyPrefix;

    public function __construct(?string $store = null, string $keyPrefix = '')
    {
        $this->store = $store ?? config('cache.default');
        $this->keyPrefix = $keyPrefix;
    }

    private function key(string $key): string
    {
        return $this->keyPrefix.$key;
    }

    public function get(string $key): mixed
    {
        return Cache::store($this->store)->get($this->key($key));
    }

    public function set(string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        if ($ttlSeconds !== null) {
            return Cache::store($this->store)->put($this->key($key), $value, $ttlSeconds);
        }

        return Cache::store($this->store)->put($this->key($key), $value);
    }

    public function forget(string $key): bool
    {
        return Cache::store($this->store)->forget($this->key($key));
    }

    public function has(string $key): bool
    {
        return Cache::store($this->store)->has($this->key($key));
    }

    /**
     * @param  Closure(): mixed  $callback
     */
    public function remember(string $key, int $ttlSeconds, Closure $callback): mixed
    {
        return Cache::store($this->store)->remember($this->key($key), $ttlSeconds, $callback);
    }

    public function pull(string $key): mixed
    {
        $fullKey = $this->key($key);
        $value = Cache::store($this->store)->get($fullKey);
        Cache::store($this->store)->forget($fullKey);

        return $value;
    }

    public function throttle(string $key, int $ttlSeconds, mixed $value = true): bool
    {
        if ($this->has($key)) {
            return false;
        }

        $this->set($key, $value, $ttlSeconds);

        return true;
    }

    /**
     * @param  Closure(): mixed  $callback
     */
    public function rememberWithTags(string $tag, string $key, int $ttlSeconds, Closure $callback): mixed
    {
        return Cache::store($this->store)
            ->tags([$tag])
            ->remember($this->key($key), $ttlSeconds, $callback);
    }

    public function setWithTag(string $tag, string $key, mixed $value, ?int $ttlSeconds = null): bool
    {
        $store = Cache::store($this->store)->tags([$tag]);
        $fullKey = $this->key($key);

        if ($ttlSeconds !== null) {
            return $store->put($fullKey, $value, $ttlSeconds);
        }

        return $store->put($fullKey, $value);
    }

    public function getWithTag(string $tag, string $key): mixed
    {
        return Cache::store($this->store)
            ->tags([$tag])
            ->get($this->key($key));
    }

    public function flushTag(string $tag): bool
    {
        return Cache::store($this->store)->tags([$tag])->flush();
    }
}