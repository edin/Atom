<?php

/** @var \Showcase\Components\ShowcaseLayout $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $context->encode($component->title()) ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fortawesome/fontawesome-free@7.3.0/css/all.min.css">
    <link rel="stylesheet" href="/atom/framework/resources/atom.css?v=1">
    <link rel="stylesheet" href="/style.css">
</head>
<body>
    <main class="showcase-shell">
        <aside class="showcase-sidebar">
            <a class="showcase-brand" href="/" atom:navigate>Atom Showcase</a>
            <nav class="showcase-nav" aria-label="Showcase navigation">
                <a href="/" atom:navigate>Overview</a>
                <a href="/components/buttons" atom:navigate>Buttons</a>
                <a href="/components/alerts" atom:navigate>Alerts</a>
                <a href="/components/badges" atom:navigate>Badges</a>
                <a href="/components/feedback" atom:navigate>Feedback</a>
                <a href="/components/icons" atom:navigate>Icons</a>
                <a href="/components/forms" atom:navigate>Forms</a>
                <a href="/components/surfaces" atom:navigate>Surfaces</a>
                <a href="/components/data" atom:navigate>Data</a>
                <a href="/components/dialogs" atom:navigate>Dialogs</a>
                <a href="/components/navigation" atom:navigate>Navigation</a>
                <a href="/components/pagination" atom:navigate>Pagination</a>
                <a href="/components/tabs" atom:navigate>Tabs</a>
                <a href="/components/layout" atom:navigate>Layout</a>
                <a href="/components/composition" atom:navigate>Composition</a>
                <a href="/components/actions" atom:navigate>Actions</a>
            </nav>
        </aside>

        <section class="showcase-main" atom:update-root>
            <?= $context->fragment($component->content) ?>
        </section>
    </main>
    <script src="/atom/framework/resources/atom.js?v=7"></script>
    <script src="/atom/framework/resources/morphdom.js?v=2.7.8"></script>
    <script src="/atom/framework/resources/atom-morphdom.js?v=3"></script>
</body>
</html>
