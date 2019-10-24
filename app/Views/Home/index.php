<?php $view->extend("layout"); ?>

<div style="border:1px solid #333;padding:3px">
<pre><?php print_r($post) ?></pre>
</div>

<h2>Some items</h2>
<?php if ($items): ?>
<table class="table">
    <?php foreach ($items as $item): ?>
    <tr>
        <td><?= $item->id ?></td>
        <td><?= $item->username ?></td>
        <td><?= $item->email ?></td>
        <td>
            <div class="float-right">
                <a class="btn btn-sm btn-primary" href="<?= $baseUrl ?>item">Detail</a>
            </div>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>