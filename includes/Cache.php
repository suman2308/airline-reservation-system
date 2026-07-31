<?php
/**
 * AeroBook – Lightweight Query Result Cache
 *
 * Caches expensive, non-user-specific query results in static arrays
 * with automatic TTL expiration. Cache lives only for the current
 * request (static cache) — no file or redis dependency required.
 *
 * Use for: dashboard KPIs, analytics data, route stats, system health.
 * Do NOT use for: user-specific data, authentication, booking queries.
 *
 * Usage:
 *   $data = AeroCache::remember('today_metrics', 60, function() {
 *       global $conn;
 *       return mysqli_fetch_all(mysqli_query($conn, "SELECT ..."), MYSQLI_ASSOC);
 *   });
 */

class AeroCache {
    private static $store = [];
    private static $expires = [];

    /**
     * Get data from cache or compute and store it.
     *
     * @param string $key Unique cache key
     * @param int $ttlSeconds Time-to-live in seconds (0 = no cache)
     * @param callable $callback Function that returns the data to cache
     * @return mixed
     */
    public static function remember($key, $ttlSeconds, $callback) {
        if ($ttlSeconds <= 0) {
            return $callback();
        }

        $now = time();

        // Return cached value if still fresh
        if (isset(self::$store[$key]) && isset(self::$expires[$key]) && self::$expires[$key] > $now) {
            return self::$store[$key];
        }

        // Compute new value
        $value = $callback();
        self::$store[$key] = $value;
        self::$expires[$key] = $now + $ttlSeconds;

        return $value;
    }

    /**
     * Invalidate a specific cache key.
     */
    public static function forget($key) {
        unset(self::$store[$key], self::$expires[$key]);
    }

    /**
     * Invalidate all cached values.
     */
    public static function flush() {
        self::$store = [];
        self::$expires = [];
    }
}
