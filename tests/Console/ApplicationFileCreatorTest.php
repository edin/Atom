<?php

declare(strict_types=1);

namespace Atom\Tests\Console;

use Atom\Console\FileTemplateRenderer;
use Atom\Console\Make\ApplicationFileCreator;
use Atom\Console\Make\MakeOptions;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ApplicationFileCreatorTest extends TestCase
{
    public function testCreatesPageClassAndTemplate(): void
    {
        $root = $this->tempDirectory();
        $files = $this->creator($root)->page("Admin Users");

        $this->assertFileExists($files["class"]);
        $this->assertFileExists($files["view"]);
        $this->assertStringEndsWith("app" . DIRECTORY_SEPARATOR . "Pages" . DIRECTORY_SEPARATOR . "AdminUsersPage.php", $files["class"]);
        $this->assertStringContainsString("final class AdminUsersPage extends Page", file_get_contents($files["class"]));
        $this->assertStringContainsString('#[PageRoute("/admin-users")]', file_get_contents($files["class"]));
        $this->assertStringContainsString("<h1>Admin Users</h1>", file_get_contents($files["view"]));
    }

    public function testCreatesComponentClass(): void
    {
        $root = $this->tempDirectory();
        $file = $this->creator($root)->component("Alert Box");

        $this->assertFileExists($file);
        $this->assertStringEndsWith("app" . DIRECTORY_SEPARATOR . "Components" . DIRECTORY_SEPARATOR . "AlertBox.php", $file);
        $this->assertStringContainsString("final class AlertBox implements ComponentInterface", file_get_contents($file));
    }

    public function testThrowsWhenTargetFileExists(): void
    {
        $root = $this->tempDirectory();
        $creator = $this->creator($root);
        $creator->component("Alert");

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("already exists");

        $creator->component("Alert");
    }

    public function testCanUseApplicationTemplateOverrides(): void
    {
        $root = $this->tempDirectory();
        $templateRoot = $root . DIRECTORY_SEPARATOR . "templates" . DIRECTORY_SEPARATOR . "atom";
        mkdir($templateRoot . DIRECTORY_SEPARATOR . "component", 0777, true);
        file_put_contents($templateRoot . DIRECTORY_SEPARATOR . "component" . DIRECTORY_SEPARATOR . "component.php.tpl", "<?php\nfinal class {{ class }} {}\n");

        $creator = new ApplicationFileCreator(
            new FileTemplateRenderer([$templateRoot, __DIR__ . "/../../src/Templates"]),
            new MakeOptions(root: $root)
        );

        $file = $creator->component("Badge");

        $this->assertStringContainsString("final class Badge {}", file_get_contents($file));
    }

    private function creator(string $root): ApplicationFileCreator
    {
        return new ApplicationFileCreator(new FileTemplateRenderer(), new MakeOptions(root: $root));
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_make_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
