<?php $this->block("sidebar") ?>
foreach($models as $model)
<table>
    <tr>
        <td>@{$model->firstName ?}</td>
        <td>@{$model->firstName ?}</td>
        <td>@{$model->firstName ?}</td>
        <td>@{$model->firstName ?}</td>
        <td>@{$model->firstName ?}</td>
        <td>@{$model->firstName ?}td>
        <td><?php echo $model->firstName ?></td>
        <td><?php echo $model->firstName ?></td>
    </tr>
</table
@endforeach
<?php $this->endBlock() ?>


Content