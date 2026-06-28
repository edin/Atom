<?php

/** @var \App\Components\Layout $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $context->encode($component->title()) ?></title>
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <a class="brand" href="/">Atom Sample</a>
            <nav class="nav" aria-label="Main navigation">
                <a href="/">Home</a>
                <a href="/articles">Articles</a>
                <a href="/articles/new">New article</a>
                <a href="/counter">Counter</a>
            </nav>
        </header>

        <?= $context->fragment($component->content) ?>
    </main>
    <script src="/atom/framework/resources/atom.js?v=2"></script>
</body>
</html>
