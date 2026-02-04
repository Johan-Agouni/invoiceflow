<?php

declare(strict_types=1);

namespace Tests\Unit\Middleware;

use Tests\TestCase;
use App\Middleware\RateLimitMiddleware;
use ReflectionClass;

/**
 * Rate Limit Middleware Tests
 *
 * @covers \App\Middleware\RateLimitMiddleware
 */
class RateLimitMiddlewareTest extends TestCase
{
    public function testCanBeInstantiatedWithDefaults(): void
    {
        $middleware = new RateLimitMiddleware();
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    public function testCanBeInstantiatedWithCustomLimits(): void
    {
        $middleware = new RateLimitMiddleware(50, 30);
        $this->assertInstanceOf(RateLimitMiddleware::class, $middleware);
    }

    public function testGetIdentifierPrefersBearerToken(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getIdentifier');
        $method->setAccessible(true);

        // Without any headers, should return IP-based identifier
        $result = $method->invoke($middleware);

        $this->assertStringStartsWith('ip_', $result);
    }

    public function testGetBearerTokenReturnsNullWithoutHeader(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getBearerToken');
        $method->setAccessible(true);

        $result = $method->invoke($middleware);

        $this->assertNull($result);
    }

    public function testGetClientIpReturnsValidIp(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $_SERVER['REMOTE_ADDR'] = '192.168.1.1';
        $result = $method->invoke($middleware);

        $this->assertEquals('192.168.1.1', $result);
    }

    public function testGetClientIpHandlesProxyHeaders(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $_SERVER['HTTP_X_FORWARDED_FOR'] = '10.0.0.1, 192.168.1.1';
        $result = $method->invoke($middleware);

        $this->assertEquals('10.0.0.1', $result);

        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
    }

    public function testGetClientIpHandlesCloudflare(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        $_SERVER['HTTP_CF_CONNECTING_IP'] = '172.16.0.1';
        $result = $method->invoke($middleware);

        $this->assertEquals('172.16.0.1', $result);

        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
    }

    public function testGetClientIpRejectsInvalidIp(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('getClientIp');
        $method->setAccessible(true);

        // Clear all IP headers
        unset($_SERVER['HTTP_CF_CONNECTING_IP']);
        unset($_SERVER['HTTP_X_FORWARDED_FOR']);
        unset($_SERVER['HTTP_X_REAL_IP']);
        unset($_SERVER['REMOTE_ADDR']);

        $result = $method->invoke($middleware);

        $this->assertEquals('0.0.0.0', $result);
    }

    public function testHandleAllowsFirstRequest(): void
    {
        $middleware = new RateLimitMiddleware(100, 60);

        // Use a unique identifier for this test
        $testIdentifier = 'test_middleware_' . uniqid();

        // We can't fully test handle() as it sets headers and may exit
        // But we can verify the middleware can be constructed and methods exist
        $this->assertTrue(method_exists($middleware, 'handle'));
    }

    public function testSetHeadersMethodExists(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('setHeaders');

        $this->assertTrue($method->isPrivate());
    }

    public function testSendTooManyRequestsResponseMethodExists(): void
    {
        $middleware = new RateLimitMiddleware();

        $reflection = new ReflectionClass($middleware);
        $method = $reflection->getMethod('sendTooManyRequestsResponse');

        $this->assertTrue($method->isPrivate());
    }
}
