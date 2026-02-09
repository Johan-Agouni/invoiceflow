<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Rate Limiter Service
 *
 * Implements a sliding window rate limiting algorithm using file-based storage
 * or Redis when available. Protects API endpoints from abuse.
 */
class RateLimiter
{
    private string $storageDir;

    private bool $useRedis = false;

    private ?\Redis $redis = null;

    // Default limits
    private int $maxRequests = 100;

    private int $windowSeconds = 60;

    public function __construct()
    {
        $this->storageDir = dirname(__DIR__, 2) . '/storage/rate_limits';

        // Ensure storage directory exists
        if (!is_dir($this->storageDir)) {
            mkdir($this->storageDir, 0o755, true);
        }

        // Try to connect to Redis if available
        $this->initRedis();
    }

    /**
     * Initialize Redis connection if available
     */
    private function initRedis(): void
    {
        if (!extension_loaded('redis')) {
            return;
        }

        try {
            $this->redis = new \Redis();
            $host = $_ENV['REDIS_HOST'] ?? 'redis';
            $port = (int) ($_ENV['REDIS_PORT'] ?? 6379);

            if ($this->redis->connect($host, $port, 2.0)) {
                $this->useRedis = true;
            }
        } catch (\Exception $e) {
            $this->redis = null;
            $this->useRedis = false;
        }
    }

    /**
     * Configure rate limits
     */
    public function setLimits(int $maxRequests, int $windowSeconds): self
    {
        $this->maxRequests = $maxRequests;
        $this->windowSeconds = $windowSeconds;

        return $this;
    }

    /**
     * Check if the request should be allowed
     *
     * @param string $key Unique identifier (e.g., API token hash, IP address)
     *
     * @return array{allowed: bool, remaining: int, reset: int, limit: int}
     */
    public function check(string $key): array
    {
        $key = $this->sanitizeKey($key);
        $now = time();
        $windowStart = $now - $this->windowSeconds;

        if ($this->useRedis) {
            return $this->checkRedis($key, $now, $windowStart);
        }

        return $this->checkFile($key, $now, $windowStart);
    }

    /**
     * Record a request hit
     */
    public function hit(string $key): void
    {
        $key = $this->sanitizeKey($key);
        $now = time();

        if ($this->useRedis) {
            $this->hitRedis($key, $now);
        } else {
            $this->hitFile($key, $now);
        }
    }

    /**
     * Check and record in one operation
     *
     * @return array{allowed: bool, remaining: int, reset: int, limit: int}
     */
    public function attempt(string $key): array
    {
        $result = $this->check($key);

        if ($result['allowed']) {
            $this->hit($key);
            $result['remaining']--;
        }

        return $result;
    }

    /**
     * Clear rate limit for a key
     */
    public function clear(string $key): void
    {
        $key = $this->sanitizeKey($key);

        if ($this->useRedis && $this->redis) {
            $this->redis->del("rate_limit:{$key}");
        } else {
            $file = $this->getFilePath($key);
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }

    /**
     * Redis-based rate limit check
     */
    private function checkRedis(string $key, int $now, int $windowStart): array
    {
        $redisKey = "rate_limit:{$key}";

        // Remove old entries
        $this->redis->zRemRangeByScore($redisKey, '-inf', (string) $windowStart);

        // Count current requests
        $count = $this->redis->zCard($redisKey);

        $remaining = max(0, $this->maxRequests - $count);
        $reset = $now + $this->windowSeconds;

        return [
            'allowed' => $count < $this->maxRequests,
            'remaining' => $remaining,
            'reset' => $reset,
            'limit' => $this->maxRequests,
        ];
    }

    /**
     * Redis-based hit recording
     */
    private function hitRedis(string $key, int $now): void
    {
        $redisKey = "rate_limit:{$key}";

        // Add new entry with timestamp as score
        $this->redis->zAdd($redisKey, $now, "{$now}:" . uniqid());

        // Set expiration on the key
        $this->redis->expire($redisKey, $this->windowSeconds + 1);
    }

    /**
     * File-based rate limit check
     */
    private function checkFile(string $key, int $now, int $windowStart): array
    {
        $file = $this->getFilePath($key);
        $timestamps = $this->readTimestamps($file);

        // Filter to only include timestamps within the window
        $timestamps = array_filter($timestamps, fn ($ts) => $ts > $windowStart);

        $count = count($timestamps);
        $remaining = max(0, $this->maxRequests - $count);
        $reset = $now + $this->windowSeconds;

        return [
            'allowed' => $count < $this->maxRequests,
            'remaining' => $remaining,
            'reset' => $reset,
            'limit' => $this->maxRequests,
        ];
    }

    /**
     * File-based hit recording
     */
    private function hitFile(string $key, int $now): void
    {
        $file = $this->getFilePath($key);
        $windowStart = $now - $this->windowSeconds;

        $timestamps = $this->readTimestamps($file);

        // Remove old timestamps and add new one
        $timestamps = array_filter($timestamps, fn ($ts) => $ts > $windowStart);
        $timestamps[] = $now;

        // Write back
        file_put_contents($file, implode("\n", $timestamps), LOCK_EX);
    }

    /**
     * Read timestamps from file
     */
    private function readTimestamps(string $file): array
    {
        if (!file_exists($file)) {
            return [];
        }

        $content = file_get_contents($file);
        if (empty($content)) {
            return [];
        }

        return array_map('intval', explode("\n", trim($content)));
    }

    /**
     * Get file path for a key
     */
    private function getFilePath(string $key): string
    {
        return $this->storageDir . '/' . $key . '.txt';
    }

    /**
     * Sanitize key for file system
     */
    private function sanitizeKey(string $key): string
    {
        return preg_replace('/[^a-zA-Z0-9_-]/', '_', $key);
    }

    /**
     * Cleanup old rate limit files (should be called periodically)
     */
    public function cleanup(): int
    {
        $deleted = 0;
        $cutoff = time() - ($this->windowSeconds * 2);

        $files = glob($this->storageDir . '/*.txt');
        foreach ($files as $file) {
            if (filemtime($file) < $cutoff) {
                unlink($file);
                $deleted++;
            }
        }

        return $deleted;
    }
}
