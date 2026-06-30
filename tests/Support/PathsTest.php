<?php

declare(strict_types=1);

namespace Atom\Tests\Support;

use Atom\Support\Paths;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PathsTest extends TestCase
{
    public function testResolvesRegisteredAliases(): void
    {
        $paths = (new Paths())
            ->alias("root", "D:\\Apps\\Atom")
            ->alias("@app", "D:/Apps/Atom/app");

        $this->assertSame("D:/Apps/Atom", $paths->resolve("@root"));
        $this->assertSame("D:/Apps/Atom/app/Pages/Home.php", $paths->resolve("@app/Pages/Home.php"));
    }

    public function testResolveFromKeepsAbsoluteAndAliasedPaths(): void
    {
        $paths = (new Paths())->alias("root", "D:/Apps/Atom");

        $this->assertSame("D:/data/app.sqlite", $paths->resolveFrom("@root", "D:/data/app.sqlite"));
        $this->assertSame("D:/Apps/Atom/storage/app.sqlite", $paths->resolveFrom("@root", "storage/app.sqlite"));
        $this->assertSame("D:/Apps/Atom/storage/app.sqlite", $paths->resolveFrom("@root", "@root/storage/app.sqlite"));
    }

    public function testThrowsForMissingAlias(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("Path alias '@missing' is not registered.");

        (new Paths())->resolve("@missing/file.txt");
    }
}
