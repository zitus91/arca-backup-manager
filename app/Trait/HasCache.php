<?php

namespace App\Trait;

use Illuminate\Support\Facades\Cache;

trait HasCache
{
    /**
     * Get cache key prefix for this model.
     */
    protected function cachePrefix(): string
    {
        return strtolower(class_basename(static::class));
    }

    /**
     * Cache a value using the model's cache prefix.
     */
    public function caching(string $key, int $ttl, \Closure $callback): mixed
    {
        $fullKey = $this->cachePrefix() . '.' . $key;

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Forget a cached value.
     */
    public function forgetCache(string $key): void
    {
        Cache::forget($this->cachePrefix() . '.' . $key);
    }

    /**
     * Static helper for caching without an instance.
     */
    public static function cachingStatic(string $key, int $ttl, \Closure $callback): mixed
    {
        $prefix = strtolower(class_basename(static::class));
        $fullKey = $prefix . '.' . $key;

        return Cache::remember($fullKey, $ttl, $callback);
    }

    /**
     * Clear all cache for this model type.
     */
    public static function clearModelCache(string $key): void
    {
        $prefix = strtolower(class_basename(static::class));
        Cache::forget($prefix . '.' . $key);
    }

    /**
     * Boot the trait - auto-clear cache on model events.
     */
    protected static function bootHasCache(): void
    {
        static::saved(function ($model) {
            $model->forgetCache('all');
            $model->forgetCache('active');
        });

        static::deleted(function ($model) {
            $model->forgetCache('all');
            $model->forgetCache('active');
        });
    }
}
