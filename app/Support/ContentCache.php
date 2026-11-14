<?php

namespace App\Support;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * Caches the JSON of the public, user-independent list endpoints. Every key contains a version number that
 * is bumped whenever works, posts, products or comments change, so cached data can never be stale.
 */
final class ContentCache
{
    private const VERSION_KEY = 'content:version';

    private const TTL_SECONDS = 600;

    public static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }

    public static function bump(): void
    {
        Cache::forever(self::VERSION_KEY, self::version() + 1);
    }

    /**
     * @param  Closure(): array<string, mixed>  $callback  builds the response data on a cache miss
     * @return array<string, mixed>
     */
    public static function remember(string $name, Request $request, Closure $callback): array
    {
        $query = $request->query();
        ksort($query);
        $key = sprintf('content:%d:%s:%s', self::version(), $name, md5(http_build_query($query)));

        return Cache::remember($key, self::TTL_SECONDS, $callback);
    }
}
