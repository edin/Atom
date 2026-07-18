<?php

declare(strict_types=1);

namespace Atom\Tests\Publish;

use Atom\Di\Bindings;
use Atom\Di\Injector;
use Atom\Publish\PublishBundle;
use Atom\Publish\PublishException;
use Atom\Publish\Publisher;
use Atom\Publish\PublishServices;
use Atom\Support\Paths;
use PHPUnit\Framework\TestCase;

final class PublisherTest extends TestCase
{
    private string $root;
    private Paths $paths;

    protected function setUp(): void
    {
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_publish_" . uniqid();
        mkdir($this->root . DIRECTORY_SEPARATOR . "resources", 0777, true);
        $this->paths = (new Paths($this->root))
            ->alias("app", $this->root . DIRECTORY_SEPARATOR . "app")
            ->alias("migrations", $this->root . DIRECTORY_SEPARATOR . "database/migrations");
    }

    protected function tearDown(): void
    {
        if (!is_dir($this->root)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($this->root, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $item) {
            if ($item->isDir()) {
                rmdir($item->getPathname());
            } else {
                unlink($item->getPathname());
            }
        }

        rmdir($this->root);
    }

    public function testPublishesBundleFilesToAliasesAndRootRelativePaths(): void
    {
        $this->source("User.php", "<?php final class User {}\n");
        $this->source("M0001_accounts.php", "<?php return 'migration';\n");
        $bundle = (new PublishBundle(
            "accounts",
            sourceDirectory: $this->path("resources")
        ))
            ->file("User.php", "@app/Models/User.php")
            ->file("M0001_accounts.php", "@migrations/M0001_accounts.php");

        $result = (new Publisher($this->paths))->publish($bundle);

        $user = $this->path("app/Models/User.php");
        $migration = $this->path("database/migrations/M0001_accounts.php");
        $this->assertSame("accounts", $result->bundle);
        $this->assertSame([$user, $migration], $result->published);
        $this->assertSame([], $result->skipped);
        $this->assertSame([], $result->overwritten);
        $this->assertTrue($result->changed());
        $this->assertSame("<?php final class User {}\n", file_get_contents($user));
        $this->assertSame("<?php return 'migration';\n", file_get_contents($migration));
    }

    public function testSkipsExistingFilesUnlessForceIsEnabled(): void
    {
        $source = $this->source("User.php", "first");
        $bundle = (new PublishBundle("accounts"))->file($source, "@app/Models/User.php");
        $publisher = new Publisher($this->paths);
        $destination = $this->path("app/Models/User.php");

        $publisher->publish($bundle);
        file_put_contents($source, "second");
        $skipped = $publisher->publish($bundle);

        $this->assertSame([$destination], $skipped->skipped);
        $this->assertFalse($skipped->changed());
        $this->assertSame("first", file_get_contents($destination));

        $overwritten = $publisher->publish($bundle, force: true);

        $this->assertSame([$destination], $overwritten->overwritten);
        $this->assertTrue($overwritten->changed());
        $this->assertSame("second", file_get_contents($destination));
    }

    public function testPreflightRejectsMissingSourcesBeforeWritingAnyFiles(): void
    {
        $source = $this->source("User.php", "user");
        $bundle = (new PublishBundle("accounts"))
            ->file($source, "@app/Models/User.php")
            ->file("resources/missing.php", "@app/Models/Missing.php");

        try {
            (new Publisher($this->paths))->publish($bundle);
            $this->fail("Expected missing publish source to fail.");
        } catch (PublishException $exception) {
            $this->assertStringContainsString("missing.php", $exception->getMessage());
        }

        $this->assertFileDoesNotExist($this->path("app/Models/User.php"));
    }

    public function testPreflightRejectsDuplicateResolvedDestinations(): void
    {
        $first = $this->source("First.php", "first");
        $second = $this->source("Second.php", "second");
        $bundle = (new PublishBundle("accounts"))
            ->file($first, "@app/Models/User.php")
            ->file($second, "@app/Models/User.php");

        $this->expectException(PublishException::class);
        $this->expectExceptionMessage("defines destination");

        (new Publisher($this->paths))->publish($bundle);
    }

    public function testPublishServiceRegistersSingletonPublisher(): void
    {
        $bindings = Bindings::create()->value(Paths::class, $this->paths);
        (new PublishServices())->register($bindings);
        $injector = Injector::create($bindings);

        $this->assertSame($injector->get(Publisher::class), $injector->get(Publisher::class));
    }

    public function testRejectsEmptyBundleNames(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage("bundle name");

        new PublishBundle("");
    }

    public function testBundleSourceDirectoryDoesNotChangeAbsoluteOrAliasedSources(): void
    {
        $absolute = $this->source("Absolute.php", "absolute");
        $bundle = (new PublishBundle("accounts", sourceDirectory: "ignored"))
            ->file($absolute, "first.php")
            ->file("@root/resources/Aliased.php", "second.php");

        $this->assertSame($absolute, $bundle->files()[0]->source);
        $this->assertSame("@root/resources/Aliased.php", $bundle->files()[1]->source);
    }

    private function source(string $name, string $contents): string
    {
        $path = $this->path("resources/{$name}");
        file_put_contents($path, $contents);

        return $path;
    }

    private function path(string $path): string
    {
        return str_replace("/", DIRECTORY_SEPARATOR, $this->root . "/" . $path);
    }
}
