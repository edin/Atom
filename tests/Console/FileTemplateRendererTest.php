<?php

declare(strict_types=1);

namespace Atom\Tests\Console;

use Atom\Console\FileTemplateRenderer;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class FileTemplateRendererTest extends TestCase
{
    public function testRendersTemplateWithVariables(): void
    {
        $directory = $this->tempDirectory();
        file_put_contents($directory . "/hello.tpl", "Hello {{ name }}!");

        $contents = (new FileTemplateRenderer($directory))->render("hello.tpl", [
            "name" => "Atom",
        ]);

        $this->assertSame("Hello Atom!", $contents);
    }

    public function testThrowsWhenTemplateDoesNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("Template 'missing.tpl' does not exist.");

        (new FileTemplateRenderer($this->tempDirectory()))->render("missing.tpl");
    }

    public function testUsesFirstTemplateRootThatContainsTemplate(): void
    {
        $appTemplates = $this->tempDirectory();
        $frameworkTemplates = $this->tempDirectory();

        file_put_contents($frameworkTemplates . "/hello.tpl", "Hello from framework");
        file_put_contents($appTemplates . "/hello.tpl", "Hello from app");

        $contents = (new FileTemplateRenderer([$appTemplates, $frameworkTemplates]))->render("hello.tpl");

        $this->assertSame("Hello from app", $contents);
    }

    public function testFallsBackToLaterTemplateRoot(): void
    {
        $appTemplates = $this->tempDirectory();
        $frameworkTemplates = $this->tempDirectory();

        file_put_contents($frameworkTemplates . "/hello.tpl", "Hello from framework");

        $contents = (new FileTemplateRenderer([$appTemplates, $frameworkTemplates]))->render("hello.tpl");

        $this->assertSame("Hello from framework", $contents);
    }

    private function tempDirectory(): string
    {
        $directory = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "atom_templates_" . uniqid();
        mkdir($directory, 0777, true);

        return $directory;
    }
}
