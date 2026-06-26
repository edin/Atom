<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Seeder;

use Atom\Database\Seeder\SeederCreator;
use Atom\Database\Seeder\SeederOptions;
use PHPUnit\Framework\TestCase;

final class SeederCreatorTest extends TestCase
{
    public function testCreatesAnonymousSeederFile(): void
    {
        $directory = $this->tempDirectory();
        $file = (new SeederCreator(new SeederOptions($directory)))->create("Seed Users");

        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/S\d{2}_\d{2}_\d{2}_\d{6}_seed_users\.php$/', $file);
        $this->assertStringContainsString("return new class extends Seeder", file_get_contents($file));
        $this->assertStringContainsString("public function run(): void", file_get_contents($file));
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_seeders_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
