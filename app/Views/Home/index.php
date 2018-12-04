<?php $view->extend("layout"); ?>

<h2>Some items</h2>
<table n:if="$items" class="table">
    <tr n:foreach="$items as $item">
        <td>{$item->id}</td>
        <td>{$item->username}</td>
        <td>{$item->email}</td>
        <td>
            <div class="float-right">
                <a class="btn btn-sm btn-primary" href="{$baseUrl|noescape}item">Detail</a>
            </div>
        </td>
    </tr>
</table>
