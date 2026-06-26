<?php

declare(strict_types=1);

namespace Atom\Tests\Database\Migration;

use Atom\Database\Migration\MigrationCreator;
use Atom\Database\Migration\MigrationOptions;
use PHPUnit\Framework\TestCase;

final class MigrationCreatorTest extends TestCase
{
    public function testCreatesAnonymousMigrationFile(): void
    {
        $directory = $this->tempDirectory();
        $file = (new MigrationCreator(new MigrationOptions($directory)))->create("Create Users");

        $this->assertFileExists($file);
        $this->assertMatchesRegularExpression('/M\d{2}_\d{2}_\d{2}_\d{6}_create_users\.php$/', $file);
        $this->assertStringContainsString("return new class extends Migration", file_get_contents($file));
        $this->assertStringContainsString("public function up(Schema \$schema): void", file_get_contents($file));
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_migrations_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
