<?php

use yii\widgets\DetailView;


$this->title = $model->id;
$this->params['breadcrumbs'][] = ['label' => 'User Transactions', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
$this->params['adminActiveMenuRoute'] = 'user-transactions/default/index';
?>
<div class="user-transactions-view">

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
			[
				'attribute'=>'user_id',
				'value' => $model->getUserName()
			],
			[
				'attribute'=>'type',
				'value' => $model->getTypeName(),
			],
			[
				'attribute'=>'status',
				'value' => $model->getStatusName(),
			],
			[
				'attribute'=>'subject_id',
				'value' => $model->getContestName(),
				'format' => 'raw',
			],
			'amount',
			'date_updated',
        ],
    ]) ?>

</div>
