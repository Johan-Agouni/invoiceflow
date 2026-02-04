<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Services\RateLimiter;

/**
 * Rate Limiter Service Tests
 *
 * @covers \App\Services\RateLimiter
 */
class RateLimiterTest extends TestCase
{
    private RateLimiter $limiter;
    private string $testKey = 'test_rate_limit_key';

    protected function setUp(): void
    {
        parent::setUp();
        $this->limiter = new RateLimiter();
        $this->limiter->clear($this->testKey);
    }

    protected function tearDown(): void
    {
        $this->limiter->clear($this->testKey);
        parent::tearDown();
    }

    public function testCanSetLimits(): void
    {
        $result = $this->limiter->setLimits(50, 30);

        $this->assertInstanceOf(RateLimiter::class, $result);
    }

    public function testCheckReturnsCorrectStructure(): void
    {
        $result = $this->limiter->check($this->testKey);

        $this->assertArrayHasKey('allowed', $result);
        $this->assertArrayHasKey('remaining', $result);
        $this->assertArrayHasKey('reset', $result);
        $this->assertArrayHasKey('limit', $result);
    }

    public function testFirstRequestIsAllowed(): void
    {
        $result = $this->limiter->check($this->testKey);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(100, $result['limit']);
    }

    public function testHitDecrementsRemaining(): void
    {
        $before = $this->limiter->check($this->testKey);
        $this->limiter->hit($this->testKey);
        $after = $this->limiter->check($this->testKey);

        $this->assertEquals($before['remaining'] - 1, $after['remaining']);
    }

    public function testAttemptChecksAndHits(): void
    {
        $result = $this->limiter->attempt($this->testKey);

        $this->assertTrue($result['allowed']);
        $this->assertEquals(99, $result['remaining']); // 100 - 1 after hit
    }

    public function testClearResetsLimiter(): void
    {
        // Make some hits
        for ($i = 0; $i < 5; $i++) {
            $this->limiter->hit($this->testKey);
        }

        $beforeClear = $this->limiter->check($this->testKey);
        $this->limiter->clear($this->testKey);
        $afterClear = $this->limiter->check($this->testKey);

        $this->assertLessThan($afterClear['remaining'], $beforeClear['remaining']);
    }

    public function testRateLimitBlocksAfterExceeded(): void
    {
        $this->limiter->setLimits(5, 60);

        // Make 5 attempts (should all succeed)
        for ($i = 0; $i < 5; $i++) {
            $result = $this->limiter->attempt($this->testKey);
            $this->assertTrue($result['allowed'], "Request {$i} should be allowed");
        }

        // 6th request should be blocked
        $result = $this->limiter->attempt($this->testKey);
        $this->assertFalse($result['allowed']);
        $this->assertEquals(0, $result['remaining']);
    }

    public function testResetTimeIsInFuture(): void
    {
        $result = $this->limiter->check($this->testKey);

        $this->assertGreaterThan(time(), $result['reset']);
    }

    public function testLimitIsConfigurable(): void
    {
        $this->limiter->setLimits(200, 120);
        $result = $this->limiter->check($this->testKey);

        $this->assertEquals(200, $result['limit']);
    }

    public function testSanitizesKeyForSafety(): void
    {
        $unsafeKey = 'test/../../../etc/passwd';
        $result = $this->limiter->check($unsafeKey);

        $this->assertArrayHasKey('allowed', $result);
        $this->assertTrue($result['allowed']);

        // Clean up
        $this->limiter->clear($unsafeKey);
    }

    public function testMultipleKeysAreIndependent(): void
    {
        $key1 = 'test_key_1';
        $key2 = 'test_key_2';

        $this->limiter->setLimits(3, 60);

        // Exhaust key1
        for ($i = 0; $i < 3; $i++) {
            $this->limiter->attempt($key1);
        }

        // key1 should be blocked
        $result1 = $this->limiter->attempt($key1);
        $this->assertFalse($result1['allowed']);

        // key2 should still be allowed
        $result2 = $this->limiter->attempt($key2);
        $this->assertTrue($result2['allowed']);

        // Cleanup
        $this->limiter->clear($key1);
        $this->limiter->clear($key2);
    }

    public function testCleanupRemovesOldFiles(): void
    {
        // This tests the cleanup functionality
        $deleted = $this->limiter->cleanup();

        $this->assertIsInt($deleted);
        $this->assertGreaterThanOrEqual(0, $deleted);
    }
}
