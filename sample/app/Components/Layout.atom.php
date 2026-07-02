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
    <link rel="stylesheet" href="/atom/framework/resources/atom.css?v=1">
    <link rel="stylesheet" href="/style/style.css">
</head>
<body>
    <main class="shell">
        <header class="topbar">
            <a class="brand" href="/" atom:navigate>Atom Sample</a>
            <nav class="nav" aria-label="Main navigation">
                <a href="/" atom:navigate>Home</a>
                <a href="/articles" atom:navigate>Articles</a>
                <a href="/articles/new" atom:navigate>New article</a>
                <a href="/counter" atom:navigate>Counter</a>
                <a href="/components" atom:navigate>Components</a>
            </nav>
        </header>

        <section atom:update-root>
            <?= $context->fragment($component->content) ?>
        </section>
    </main>
    <script src="/atom/framework/resources/atom.js?v=7"></script>
    <script src="/atom/framework/resources/morphdom.js?v=2.7.8"></script>
    <script src="/atom/framework/resources/atom-morphdom.js?v=3"></script>
</body>
</html>
