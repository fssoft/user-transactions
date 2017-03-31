<?php

$this->title = 'Create User Transactions';
$this->params['breadcrumbs'][] = ['label' => 'User Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-transactions-create">

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
