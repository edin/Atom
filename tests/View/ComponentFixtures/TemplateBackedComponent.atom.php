<?php

/** @var \Atom\Tests\View\TemplateBackedComponent $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<section><h1><?= $context->encode($component->title) ?></h1><?= $context->fragment($component->content, ["name" => "Ada"]) ?></section>
