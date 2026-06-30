<?php

declare(strict_types=1);

namespace Atom\Tests\Config;

use Atom\Config\Config;
use Atom\Config\FromEnv;
use Atom\Config\Options;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ConfigTest extends TestCase
{
    public function testStoresAndReturnsTypedOptions(): void
    {
        $config = Config::fromEnv();
        $options = new TestOptions(name: "Manual");

        $config->set($options);

        $this->assertTrue($config->has(TestOptions::class));
        $this->assertSame($options, $config->get(TestOptions::class));
        $this->assertSame($options, $config->options(TestOptions::class));
    }

    public function testHydratesOptionsFromEnvironment(): void
    {
        $config = Config::fromEnv([
            "TEST_NAME" => "Atom",
            "TEST_COUNT" => "42",
            "TEST_ENABLED" => "true",
            "TEST_LEVEL" => "error",
            "TEST_CUSTOM_PATH" => "storage/custom.log",
        ]);

        $options = $config->options(TestOptions::class);

        $this->assertSame("Atom", $options->name);
        $this->assertSame(42, $options->count);
        $this->assertTrue($options->enabled);
        $this->assertSame(TestLevel::Error, $options->level);
        $this->assertSame("storage/custom.log", $options->path);
    }

    public function testUsesDefaultsWhenEnvironmentValueIsMissing(): void
    {
        $options = Config::fromEnv()->options(TestOptions::class);

        $this->assertSame("Default", $options->name);
        $this->assertSame(1, $options->count);
        $this->assertFalse($options->enabled);
        $this->assertSame(TestLevel::Info, $options->level);
        $this->assertSame("storage/app.log", $options->path);
    }

    public function testThrowsWhenRequiredEnvironmentValueIsMissing(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Missing environment option 'REQUIRED_NAME'");

        Config::fromEnv()->options(RequiredOptions::class);
    }
}

#[Options(prefix: "TEST_")]
final readonly class TestOptions
{
    public function __construct(
        public string $name = "Default",
        public int $count = 1,
        public bool $enabled = false,
        public TestLevel $level = TestLevel::Info,
        #[FromEnv("CUSTOM_PATH")]
        public string $path = "storage/app.log"
    ) {
    }
}

#[Options(prefix: "REQUIRED_")]
final readonly class RequiredOptions
{
    public function __construct(public string $name)
    {
    }
}

enum TestLevel: string
{
    case Info = "info";
    case Error = "error";
}
