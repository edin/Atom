<?php

declare(strict_types=1);

namespace Atom\Tests\Cache;

use Atom\Application;
use Atom\Cache\CacheException;
use Atom\Cache\CacheInterface;
use Atom\Cache\CacheOptions;
use Atom\Cache\FileCache;
use Atom\Cache\Commands\CacheCommands;
use Atom\Console\BufferedConsoleOutput;
use Atom\Config\Config;
use Atom\Http\RateLimitMiddleware;
use DateInterval;
use FilesystemIterator;
use PHPUnit\Framework\TestCase;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class CacheTest extends TestCase
{
    /** @var string[] */
    private array $directories = [];

    protected function tearDown(): void
    {
        Application::$app = null;
        foreach ($this->directories as $directory) {
            $this->removeDirectory($directory);
        }
    }

    public function testStoresRetrievesChecksAndDeletesValuesIncludingNull(): void
    {
        $cache = $this->cache();
        $cache->set("user:42", ["name" => "Atom"]);
        $cache->set("nullable", null);

        $this->assertSame(["name" => "Atom"], $cache->get("user:42"));
        $this->assertTrue($cache->has("nullable"));
        $this->assertNull($cache->get("nullable", "fallback"));
        $this->assertSame("fallback", $cache->get("missing", "fallback"));

        $cache->delete("user:42");
        $this->assertFalse($cache->has("user:42"));
    }

    public function testTtlExpiresUsingIntegerAndDateInterval(): void
    {
        $now = 1000;
        $cache = $this->cache("atom", 0, function () use (&$now): int {
            return $now;
        });
        $cache->set("seconds", "value", 10);
        $cache->set("interval", "value", new DateInterval("PT20S"));

        $now = 1010;
        $this->assertFalse($cache->has("seconds"));
        $this->assertTrue($cache->has("interval"));

        $now = 1020;
        $this->assertFalse($cache->has("interval"));
    }

    public function testDefaultTtlAndNonPositiveExplicitTtlBehavior(): void
    {
        $now = 2000;
        $cache = $this->cache("atom", 5, function () use (&$now): int {
            return $now;
        });
        $cache->set("default", "value");
        $cache->set("forever", "value", 0);
        $cache->set("expired", "value", -1);

        $now = 2005;
        $this->assertFalse($cache->has("default"));
        $this->assertTrue($cache->has("forever"));
        $this->assertFalse($cache->has("expired"));
    }

    public function testRememberComputesOnlyOnceAndCachesFalse(): void
    {
        $cache = $this->cache();
        $calls = 0;

        $first = $cache->remember("query", 60, function () use (&$calls): bool {
            $calls++;
            return false;
        });
        $second = $cache->remember("query", 60, function () use (&$calls): bool {
            $calls++;
            return true;
        });

        $this->assertFalse($first);
        $this->assertFalse($second);
        $this->assertSame(1, $calls);
    }

    public function testAddOnlyStoresMissingOrExpiredKeys(): void
    {
        $now = 3000;
        $cache = $this->cache("atom", 0, function () use (&$now): int {
            return $now;
        });

        $this->assertTrue($cache->add("lock", "first", 5));
        $this->assertFalse($cache->add("lock", "second", 5));
        $this->assertSame("first", $cache->get("lock"));

        $now = 3005;
        $this->assertTrue($cache->add("lock", "third", 5));
        $this->assertSame("third", $cache->get("lock"));
    }

    public function testIncrementIsAtomicAndPreservesExistingExpiration(): void
    {
        $now = 4000;
        $cache = $this->cache("atom", 0, function () use (&$now): int {
            return $now;
        });

        $this->assertSame(2, $cache->increment("attempts", 2, 10));
        $now = 4005;
        $this->assertSame(3, $cache->increment("attempts", 1, 100));
        $now = 4010;
        $this->assertFalse($cache->has("attempts"));
    }

    public function testIncrementRejectsNonIntegerValue(): void
    {
        $cache = $this->cache();
        $cache->set("name", "Atom");

        $this->expectException(CacheException::class);
        $cache->increment("name");
    }

    public function testIncrementRejectsPositiveIntegerOverflow(): void
    {
        $cache = $this->cache();
        $cache->set("counter", PHP_INT_MAX);

        $this->expectException(CacheException::class);
        $cache->increment("counter");
    }

    public function testIncrementRejectsNegativeIntegerOverflow(): void
    {
        $cache = $this->cache();
        $cache->set("counter", PHP_INT_MIN);

        $this->expectException(CacheException::class);
        $cache->increment("counter", -1);
    }

    public function testPrefixesAreIsolatedAndClearOnlyAffectsCurrentPrefix(): void
    {
        $directory = $this->directory();
        $first = new FileCache($directory, "first");
        $second = new FileCache($directory, "second");
        $first->set("shared", "one");
        $second->set("shared", "two");

        $first->clear();

        $this->assertFalse($first->has("shared"));
        $this->assertSame("two", $second->get("shared"));
    }

    public function testPruneRemovesExpiredAndCorruptEntriesButKeepsValidValues(): void
    {
        $now = 5000;
        $directory = $this->directory();
        $cache = new FileCache($directory, "prune", clock: function () use (&$now): int {
            return $now;
        });
        $cache->set("valid", "keep", 20);
        $cache->set("expired", "remove", 5);
        $cache->set("corrupt", "corrupt-marker", 20);
        $files = glob($directory . DIRECTORY_SEPARATOR . "data" . DIRECTORY_SEPARATOR . "*"
            . DIRECTORY_SEPARATOR . "*" . DIRECTORY_SEPARATOR . "*.cache") ?: [];
        foreach ($files as $file) {
            if (str_contains((string) file_get_contents($file), "corrupt-marker")) {
                file_put_contents($file, "not serialized");
            }
        }

        $now = 5005;
        $removed = $cache->prune();

        $this->assertSame(2, $removed);
        $this->assertSame("keep", $cache->get("valid"));
        $this->assertFalse($cache->has("expired"));
        $this->assertFalse($cache->has("corrupt"));
    }

    public function testCacheCommandsReportMaintenanceResults(): void
    {
        $now = 6000;
        $cache = $this->cache("commands", clock: function () use (&$now): int {
            return $now;
        });
        $cache->set("expired", true, 1);
        $output = new BufferedConsoleOutput();
        $commands = new CacheCommands($cache, $output);
        $now = 6001;

        $this->assertSame(0, $commands->prune());
        $this->assertSame("Pruned 1 cache entry." . PHP_EOL, $output->output());

        $cache->set("remaining", true);
        $this->assertSame(0, $commands->clear());
        $this->assertFalse($cache->has("remaining"));
        $this->assertStringContainsString("Cache cleared.", $output->output());
    }

    public function testInvalidKeysAndUnserializableValuesAreRejected(): void
    {
        $cache = $this->cache();

        try {
            $cache->get("");
            $this->fail("Empty key was accepted.");
        } catch (CacheException) {
            $this->addToAssertionCount(1);
        }

        $this->expectException(CacheException::class);
        $cache->set("closure", static fn(): string => "no");
    }

    public function testOptionsHydrateFromEnvironmentConfiguration(): void
    {
        $options = Config::fromEnv([
            "CACHE_DIRECTORY" => "@root/var/cache",
            "CACHE_PREFIX" => "example",
            "CACHE_DEFAULT_TTL" => "120",
        ])->options(CacheOptions::class);

        $this->assertSame("@root/var/cache", $options->directory);
        $this->assertSame("example", $options->prefix);
        $this->assertSame(120, $options->defaultTtl);
    }

    public function testApplicationRegistersSingletonCacheService(): void
    {
        $directory = $this->directory();
        $app = new CacheTestApplication($directory);
        $app->initialize();

        $cache = $app->getCache();
        $cache->set("ready", true);

        $this->assertInstanceOf(CacheInterface::class, $cache);
        $this->assertSame($cache, $app->getInjector()->get(CacheInterface::class));
        $this->assertInstanceOf(RateLimitMiddleware::class, $app->getInjector()->get(RateLimitMiddleware::class));
        $this->assertTrue($app->getCache()->get("ready"));
    }

    public function testApplicationConsoleDiscoversAndRunsCacheCommands(): void
    {
        $directory = $this->directory();
        $app = new CacheTestApplication($directory);
        $app->initialize();
        $app->getCache()->set("temporary", "value");
        $console = $app->getConsole();
        $output = new BufferedConsoleOutput();

        $this->assertTrue($console->commands()->has("cache:clear"));
        $this->assertTrue($console->commands()->has("cache:prune"));
        $this->assertSame(0, $console->run(["atom", "cache:clear"], $output));
        $this->assertFalse($app->getCache()->has("temporary"));
        $this->assertSame("Cache cleared." . PHP_EOL, $output->output());
    }

    private function cache(string $prefix = "atom", int $defaultTtl = 0, ?callable $clock = null): FileCache
    {
        return new FileCache($this->directory(), $prefix, $defaultTtl, $clock);
    }

    private function directory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_cache_" . bin2hex(random_bytes(6));
        $this->directories[] = $directory;
        return $directory;
    }

    private function removeDirectory(string $directory): void
    {
        if (!is_dir($directory)) {
            return;
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $item) {
            $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
        }
        rmdir($directory);
    }
}

final class CacheTestApplication extends Application
{
    public function __construct(private readonly string $cacheDirectory)
    {
        parent::__construct();
    }

    protected function configure(Config $config): void
    {
        $config->set(new CacheOptions($this->cacheDirectory, "test"));
    }
}
