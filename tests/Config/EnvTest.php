<?php

declare(strict_types=1);

namespace Atom\Tests\Config;

use Atom\Config\Env;
use Atom\Config\EnvException;
use PHPUnit\Framework\TestCase;

final class EnvTest extends TestCase
{
    /** @var string[] */
    private array $keys = [
        "ATOM_TEST_NAME",
        "ATOM_TEST_EMPTY",
        "ATOM_TEST_QUOTED",
        "ATOM_TEST_EXPORTED",
        "ATOM_TEST_PORT",
        "ATOM_TEST_ENABLED",
        "ATOM_TEST_RATIO",
        "ATOM_TEST_EXISTING",
    ];

    protected function tearDown(): void
    {
        foreach ($this->keys as $key) {
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    public function testLoadsSimpleEnvironmentFile(): void
    {
        Env::load($this->file(<<<'ENV'
            # comment
            ATOM_TEST_NAME=Atom
            ATOM_TEST_EMPTY=
            ATOM_TEST_QUOTED="Hello Atom"
            export ATOM_TEST_EXPORTED=yes
            ENV));

        $this->assertSame("Atom", Env::string("ATOM_TEST_NAME"));
        $this->assertSame("", Env::string("ATOM_TEST_EMPTY", "fallback"));
        $this->assertSame("Hello Atom", Env::string("ATOM_TEST_QUOTED"));
        $this->assertTrue(Env::bool("ATOM_TEST_EXPORTED"));
    }

    public function testDoesNotOverrideExistingValuesByDefault(): void
    {
        putenv("ATOM_TEST_EXISTING=server");

        Env::load($this->file("ATOM_TEST_EXISTING=file"));

        $this->assertSame("server", Env::string("ATOM_TEST_EXISTING"));
    }

    public function testCanOverrideExistingValues(): void
    {
        putenv("ATOM_TEST_EXISTING=server");

        Env::load($this->file("ATOM_TEST_EXISTING=file"), override: true);

        $this->assertSame("file", Env::string("ATOM_TEST_EXISTING"));
    }

    public function testReadsTypedValues(): void
    {
        Env::load($this->file(<<<'ENV'
            ATOM_TEST_PORT=8080
            ATOM_TEST_ENABLED=on
            ATOM_TEST_RATIO=1.5
            ENV));

        $this->assertSame(8080, Env::int("ATOM_TEST_PORT"));
        $this->assertTrue(Env::bool("ATOM_TEST_ENABLED"));
        $this->assertSame(1.5, Env::float("ATOM_TEST_RATIO"));
    }

    public function testLoadIfExistsReturnsFalseForMissingFile(): void
    {
        $this->assertFalse(Env::loadIfExists(__DIR__ . "/missing.env"));
    }

    public function testThrowsForInvalidLine(): void
    {
        $this->expectException(EnvException::class);

        Env::load($this->file("ATOM_TEST_NAME"));
    }

    private function file(string $contents): string
    {
        $path = tempnam(sys_get_temp_dir(), "atom_env_");
        $this->assertIsString($path);
        file_put_contents($path, $this->normalize($contents));

        return $path;
    }

    private function normalize(string $contents): string
    {
        $lines = explode("\n", $contents);
        $lines = array_map(static fn(string $line): string => preg_replace('/^\s{12}/', "", $line) ?? $line, $lines);

        return trim(implode("\n", $lines)) . "\n";
    }
}
