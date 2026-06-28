<?php

declare(strict_types=1);

namespace Atom\View\Component;

use Atom\View\Parser\ViewParser;
use Atom\View\Ast\TemplateNode;
use Atom\View\Render\ViewRenderException;
use ReflectionObject;

abstract class TemplateComponent implements ComponentInterface
{
    public function render(): TemplateNode
    {
        $reflection = new ReflectionObject($this);
        $fileName = $reflection->getFileName();

        if ($fileName === false) {
            throw new ViewRenderException("Cannot locate template for component '{$reflection->getName()}'.");
        }

        $template = dirname($fileName) . DIRECTORY_SEPARATOR . $reflection->getShortName() . ".atom.html";
        if (!is_file($template)) {
            throw new ViewRenderException("Cannot find component template '{$template}'.");
        }

        $source = file_get_contents($template);
        if ($source === false) {
            throw new ViewRenderException("Cannot read component template '{$template}'.");
        }

        return (new ViewParser())->parse($source);
    }
}
