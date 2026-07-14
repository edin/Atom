<?php

/** @var \Showcase\Components\ShowcaseLayout $component */
/** @var \Atom\View\Component\ComponentTemplateContext $context */

use Atom\Modules\Framework\Components\Icon;

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
                <a href="/" atom:navigate><?= Icon::from("lucide:layout-dashboard")->render() ?><span>Overview</span></a>
                <span class="showcase-nav-label">Examples</span>
                <a href="/examples/article-admin" atom:navigate><?= Icon::from("lucide:newspaper")->render() ?><span>Article Admin</span></a>
                <span class="showcase-nav-label">Components</span>
                <a href="/components/buttons" atom:navigate><?= Icon::from("lucide:mouse-pointer-click")->render() ?><span>Buttons</span></a>
                <a href="/components/alerts" atom:navigate><?= Icon::from("lucide:circle-alert")->render() ?><span>Alerts</span></a>
                <a href="/components/badges" atom:navigate><?= Icon::from("lucide:badge")->render() ?><span>Badges</span></a>
                <a href="/components/feedback" atom:navigate><?= Icon::from("lucide:message-circle")->render() ?><span>Feedback</span></a>
                <a href="/components/icons" atom:navigate><?= Icon::from("lucide:sparkles")->render() ?><span>Icons</span></a>
                <a href="/components/forms" atom:navigate><?= Icon::from("lucide:clipboard-pen")->render() ?><span>Forms</span></a>
                <a href="/components/surfaces" atom:navigate><?= Icon::from("lucide:panel-top")->render() ?><span>Surfaces</span></a>
                <a href="/components/data" atom:navigate><?= Icon::from("lucide:table-2")->render() ?><span>Data</span></a>
                <a href="/components/dialogs" atom:navigate><?= Icon::from("lucide:message-square-warning")->render() ?><span>Dialogs</span></a>
                <a href="/components/navigation" atom:navigate><?= Icon::from("lucide:route")->render() ?><span>Navigation</span></a>
                <a href="/components/pagination" atom:navigate><?= Icon::from("lucide:list-ordered")->render() ?><span>Pagination</span></a>
                <a href="/components/tabs" atom:navigate><?= Icon::from("lucide:notebook-tabs")->render() ?><span>Tabs</span></a>
                <a href="/components/layout" atom:navigate><?= Icon::from("lucide:panels-top-left")->render() ?><span>Layout</span></a>
                <a href="/components/composition" atom:navigate><?= Icon::from("lucide:blocks")->render() ?><span>Composition</span></a>
                <a href="/components/actions" atom:navigate><?= Icon::from("lucide:bolt")->render() ?><span>Actions</span></a>
            </nav>
        </aside>

        <section class="showcase-main" data-width="<?= $context->encode($component->contentWidth()) ?>" atom:update-root>
            <?= $context->fragment($component->content) ?>
        </section>
    </main>
    <script src="/atom/framework/resources/atom.js?v=9"></script>
    <script src="/atom/framework/resources/morphdom.js?v=2.7.8"></script>
    <script src="/atom/framework/resources/atom-morphdom.js?v=3"></script>
    <script src="/atom/dev/resources/reload.js" data-interval="1000"></script>
</body>
</html>
