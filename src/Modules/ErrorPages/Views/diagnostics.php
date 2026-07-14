<?php

declare(strict_types=1);

/** @var Closure(string): string $escape */
/** @var array<string, int|string> $diagnostics */
?>
<section class="diagnostics">
    <h2>Diagnostics</h2>
    <dl>
        <?php foreach ($diagnostics as $name => $value): ?>
            <?php if ($name !== "trace"): ?>
                <dt><?= $escape(ucfirst($name)) ?></dt>
                <dd><?= $escape((string) $value) ?></dd>
            <?php endif; ?>
        <?php endforeach; ?>
    </dl>
    <?php if (isset($diagnostics["trace"])): ?>
        <pre><?= $escape((string) $diagnostics["trace"]) ?></pre>
    <?php endif; ?>
</section>
