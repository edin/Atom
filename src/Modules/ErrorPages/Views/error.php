<?php

declare(strict_types=1);

use Atom\Modules\ErrorPages\ErrorPage;

/** @var ErrorPage $page */
/** @var bool $debug */
/** @var array<string, int|string> $diagnostics */

$escape = static fn(string $value): string => htmlspecialchars(
    $value,
    ENT_QUOTES | ENT_SUBSTITUTE,
    "UTF-8"
);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title><?= $escape((string) $page->status) ?> · <?= $escape($page->title) ?></title>
    <style>
        :root { color-scheme: light dark; font-family: Inter, ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif; }
        * { box-sizing: border-box; }
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1.25rem; background: #f4f5f7; color: #20242c; }
        main { width: min(100%, 48rem); }
        .card { overflow: hidden; border: 1px solid #dfe2e7; border-radius: 1rem; background: #fff; box-shadow: 0 1rem 3rem rgba(31, 39, 51, .08); }
        .summary { padding: clamp(1.5rem, 5vw, 3rem); }
        .status { margin: 0 0 .6rem; color: #667085; font-size: .75rem; font-weight: 750; letter-spacing: .12em; text-transform: uppercase; }
        h1 { margin: 0; font-size: clamp(1.75rem, 5vw, 2.75rem); line-height: 1.08; letter-spacing: -.04em; }
        .message { max-width: 38rem; margin: .8rem 0 0; color: #5a6270; font-size: .98rem; line-height: 1.55; }
        .error-id { margin: 1.25rem 0 0; color: #7a8290; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; font-size: .72rem; }
        .diagnostics { border-top: 1px solid #e6e8ec; background: #f8f9fb; padding: 1.25rem clamp(1.25rem, 4vw, 2rem) 1.5rem; }
        .diagnostics h2 { margin: 0 0 .8rem; font-size: .78rem; letter-spacing: .08em; text-transform: uppercase; }
        dl { display: grid; grid-template-columns: minmax(6.5rem, auto) 1fr; gap: .45rem 1rem; margin: 0; font-size: .8rem; }
        dt { color: #737b89; }
        dd { min-width: 0; margin: 0; overflow-wrap: anywhere; font-family: ui-monospace, SFMono-Regular, Consolas, monospace; }
        pre { overflow: auto; margin: 1rem 0 0; padding: .85rem; border: 1px solid #dfe2e7; border-radius: .65rem; background: #fff; font: .73rem/1.5 ui-monospace, SFMono-Regular, Consolas, monospace; white-space: pre-wrap; overflow-wrap: anywhere; }
        @media (prefers-color-scheme: dark) {
            body { background: #111318; color: #f5f7fa; }
            .card { border-color: #30343c; background: #1a1d23; box-shadow: none; }
            .status, .error-id { color: #9ca3af; }
            .message, dt { color: #b3bac5; }
            .diagnostics { border-color: #30343c; background: #15181d; }
            pre { border-color: #343943; background: #1d2128; }
        }
    </style>
</head>
<body>
    <main>
        <article class="card">
            <section class="summary">
                <p class="status">Error <?= $escape((string) $page->status) ?></p>
                <h1><?= $escape($page->title) ?></h1>
                <p class="message"><?= $escape($page->message) ?></p>
                <?php if ($page->id !== null): ?>
                    <p class="error-id">Reference: <?= $escape($page->id) ?></p>
                <?php endif; ?>
            </section>
            <?php if ($debug): ?>
                <?php require __DIR__ . "/diagnostics.php"; ?>
            <?php endif; ?>
        </article>
    </main>
</body>
</html>
