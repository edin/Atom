<?php

declare(strict_types=1);

namespace Atom\Tests\Http;

use Atom\Cache\CacheException;
use Atom\Cache\CacheInterface;
use Atom\Cache\FileCache;
use Atom\Config\Config;
use Atom\Http\RateLimitMiddleware;
use Atom\Http\RateLimitOptions;
use Atom\Http\Request;
use Atom\Http\RequestHandlerInterface;
use Atom\Http\Response;
use DateInterval;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class RateLimitTest extends TestCase
{
    private ?string $directory = null;

    protected function tearDown(): void
    {
        if ($this->directory === null || !is_dir($this->directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($this->directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($this->directory);
    }

    public function testAllowsQuotaThenReturns429WithLimitHeaders(): void
    {
        $now = 100;
        $handler = new RateLimitTestHandler();
        $middleware = $this->middleware(new RateLimitOptions(maxAttempts: 2, windowSeconds: 60), $now);
        $request = $this->request("198.51.100.7", "/login");

        $first = $middleware->process($request, $handler);
        $second = $middleware->process($request, $handler);
        $denied = $middleware->process($request, $handler);

        $this->assertSame("1", $first->headers()->get("X-RateLimit-Remaining"));
        $this->assertSame("0", $second->headers()->get("X-RateLimit-Remaining"));
        $this->assertSame(429, $denied->getStatus());
        $this->assertSame("Too Many Requests", $denied->getReasonPhrase());
        $this->assertSame("2", $denied->headers()->get("X-RateLimit-Limit"));
        $this->assertSame("0", $denied->headers()->get("X-RateLimit-Remaining"));
        $this->assertSame("120", $denied->headers()->get("X-RateLimit-Reset"));
        $this->assertSame("20", $denied->headers()->get("Retry-After"));
        $this->assertSame(2, $handler->calls);
    }

    public function testNewWindowResetsQuota(): void
    {
        $now = 59;
        $handler = new RateLimitTestHandler();
        $middleware = $this->middleware(new RateLimitOptions(maxAttempts: 1, windowSeconds: 60), $now);
        $request = $this->request("198.51.100.7", "/api");

        $this->assertSame(200, $middleware->process($request, $handler)->getStatus());
        $this->assertSame(429, $middleware->process($request, $handler)->getStatus());
        $now = 60;
        $this->assertSame(200, $middleware->process($request, $handler)->getStatus());
    }

    public function testClientsAndPathsHaveIndependentQuotasByDefault(): void
    {
        $now = 200;
        $middleware = $this->middleware(new RateLimitOptions(maxAttempts: 1), $now);

        $this->assertSame(200, $middleware->process(
            $this->request("198.51.100.1", "/one"),
            new RateLimitTestHandler()
        )->getStatus());
        $this->assertSame(200, $middleware->process(
            $this->request("198.51.100.2", "/one"),
            new RateLimitTestHandler()
        )->getStatus());
        $this->assertSame(200, $middleware->process(
            $this->request("198.51.100.1", "/two"),
            new RateLimitTestHandler()
        )->getStatus());
        $this->assertSame(429, $middleware->process(
            $this->request("198.51.100.1", "/one"),
            new RateLimitTestHandler()
        )->getStatus());
    }

    public function testCustomKeyResolverCanShareQuotaAcrossRequests(): void
    {
        $now = 300;
        $middleware = new RateLimitMiddleware(
            $this->cache(),
            new RateLimitOptions(maxAttempts: 1),
            static fn(Request $request): string => "user:42",
            function () use (&$now): int {
                return $now;
            }
        );

        $this->assertSame(200, $middleware->process(
            $this->request("198.51.100.1", "/one"),
            new RateLimitTestHandler()
        )->getStatus());
        $this->assertSame(429, $middleware->process(
            $this->request("198.51.100.2", "/two"),
            new RateLimitTestHandler()
        )->getStatus());
    }

    public function testDisabledLimiterDoesNotTouchCache(): void
    {
        $handler = new RateLimitTestHandler();
        $middleware = new RateLimitMiddleware(
            new FailingRateLimitCache(),
            new RateLimitOptions(maxAttempts: 0)
        );

        $response = $middleware->process(new Request("GET", "/"), $handler);

        $this->assertSame(200, $response->getStatus());
        $this->assertSame(1, $handler->calls);
        $this->assertFalse($response->headers()->has("X-RateLimit-Limit"));
    }

    public function testCacheFailuresCanFailOpenOrClosed(): void
    {
        $request = new Request("GET", "/");
        $openHandler = new RateLimitTestHandler();
        $open = new RateLimitMiddleware(
            new FailingRateLimitCache(),
            new RateLimitOptions(failOpen: true)
        );
        $closed = new RateLimitMiddleware(
            new FailingRateLimitCache(),
            new RateLimitOptions(failOpen: false)
        );

        $this->assertSame(200, $open->process($request, $openHandler)->getStatus());
        $this->assertSame(1, $openHandler->calls);

        $this->expectException(CacheException::class);
        $closed->process($request, new RateLimitTestHandler());
    }

    public function testResponseSpecificLimitHeaderIsPreserved(): void
    {
        $now = 400;
        $handler = new RateLimitTestHandler((new Response())->header("X-RateLimit-Limit", "custom"));
        $response = $this->middleware(new RateLimitOptions(), $now)->process(
            $this->request("198.51.100.7", "/"),
            $handler
        );

        $this->assertSame("custom", $response->headers()->get("X-RateLimit-Limit"));
        $this->assertSame("59", $response->headers()->get("X-RateLimit-Remaining"));
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "RATE_LIMIT_MAX_ATTEMPTS" => "10",
            "RATE_LIMIT_WINDOW_SECONDS" => "30",
            "RATE_LIMIT_INCLUDE_PATH" => "false",
            "RATE_LIMIT_INCLUDE_METHOD" => "true",
            "RATE_LIMIT_FAIL_OPEN" => "false",
        ])->options(RateLimitOptions::class);

        $this->assertSame(10, $options->maxAttempts);
        $this->assertSame(30, $options->windowSeconds);
        $this->assertFalse($options->includePath);
        $this->assertTrue($options->includeMethod);
        $this->assertFalse($options->failOpen);
    }

    private function middleware(RateLimitOptions $options, int &$now): RateLimitMiddleware
    {
        return new RateLimitMiddleware(
            $this->cache(),
            $options,
            clock: function () use (&$now): int {
                return $now;
            }
        );
    }

    private function cache(): FileCache
    {
        $this->directory ??= sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_rate_" . bin2hex(random_bytes(6));
        return new FileCache($this->directory, "rate-test");
    }

    private function request(string $ip, string $path): Request
    {
        return new Request("GET", $path, serverParams: ["REMOTE_ADDR" => $ip]);
    }
}

final class RateLimitTestHandler implements RequestHandlerInterface
{
    public int $calls = 0;

    public function __construct(private ?Response $response = null)
    {
    }

    public function handle(Request $request): Response
    {
        $this->calls++;
        return $this->response ?? (new Response())->content("ok");
    }
}

final class FailingRateLimitCache implements CacheInterface
{
    public function get(string $key, mixed $default = null): mixed { throw new CacheException("offline"); }
    public function has(string $key): bool { throw new CacheException("offline"); }
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): void { throw new CacheException("offline"); }
    public function delete(string $key): void { throw new CacheException("offline"); }
    public function clear(): void { throw new CacheException("offline"); }
    public function remember(string $key, DateInterval|int|null $ttl, callable $factory): mixed { throw new CacheException("offline"); }
    public function add(string $key, mixed $value, DateInterval|int|null $ttl = null): bool { throw new CacheException("offline"); }
    public function increment(string $key, int $amount = 1, DateInterval|int|null $ttl = null): int { throw new CacheException("offline"); }
}
