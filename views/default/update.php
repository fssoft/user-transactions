<?php

$this->title = 'Update User Transactions: ' . $model->id;
$this->params['breadcrumbs'][] = ['label' => 'User Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->id, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
$this->params['adminActiveMenuRoute'] = 'user-transactions/default/index';
?>
<div class="user-transactions-update">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
