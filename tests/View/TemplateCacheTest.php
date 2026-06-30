<?php

declare(strict_types=1);

namespace Atom\Tests\View;

use Atom\View\Ast\TemplateNode;
use Atom\View\TemplateCache;
use Atom\View\Templates;
use PHPUnit\Framework\TestCase;

final class TemplateCacheTest extends TestCase
{
    protected function tearDown(): void
    {
        Templates::reset();
    }

    public function testCachesTemplateByPathAndModifiedTime(): void
    {
        $path = tempnam(sys_get_temp_dir(), "atom_template_");
        $this->assertIsString($path);
        file_put_contents($path, "<h1>Hello</h1>");

        $cache = new TemplateCache();
        $count = 0;

        $first = $cache->remember($path, function () use (&$count): TemplateNode {
            $count++;
            return new TemplateNode();
        });
        $second = $cache->remember($path, function () use (&$count): TemplateNode {
            $count++;
            return new TemplateNode();
        });

        $this->assertSame($first, $second);
        $this->assertSame(1, $count);
    }

    public function testTemplatesFacadeUsesFallbackCache(): void
    {
        $path = tempnam(sys_get_temp_dir(), "atom_template_");
        $this->assertIsString($path);
        file_put_contents($path, "<h1>Hello</h1>");
        $count = 0;

        Templates::remember($path, function () use (&$count): TemplateNode {
            $count++;
            return new TemplateNode();
        });
        Templates::remember($path, function () use (&$count): TemplateNode {
            $count++;
            return new TemplateNode();
        });

        $this->assertSame(1, $count);
    }
}
