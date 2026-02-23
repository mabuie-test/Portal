<?php

declare(strict_types=1);

namespace App\Security;

final class RateLimiter
{
    /** @var array<string, array{count:int,first:int}> */
    private static array $hits = [];

    public static function allow(string $key, int $max, int $windowSeconds): bool
    {
        $now = time();
        $bucket = self::$hits[$key] ?? ['count' => 0, 'first' => $now];
        if ($now - $bucket['first'] > $windowSeconds) {
            $bucket = ['count' => 0, 'first' => $now];
        }
        $bucket['count']++;
        self::$hits[$key] = $bucket;
        return $bucket['count'] <= $max;
    }
}
