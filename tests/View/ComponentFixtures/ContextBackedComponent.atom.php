<?php

/** @var \Atom\Tests\View\ContextBackedComponent $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<article<?= $context->attributes([
    "class" => $context->classes(["card", "is-active" => true, "is-hidden" => false]),
    "data-id" => 42,
]) ?>><h1><?= $context->encode($component->title) ?></h1><?= $context->fragment($component->content) ?></article>
