<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\Url;
use demonId\customGridFilter\CustomDataColumn;
use fssoft\userTransactions\helpers\CommonHelper;
use fssoft\userTransactions\models\UserTransaction;


$this->title = 'User Transactions';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="user-transactions-index">
    <p>
        <?= Html::a('Create User Transactions', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

	<div class="mass-actions">
		<button class="btn btn-mini btn-success approve-all">
			<i class="icon-ok"></i>Approve selected
		</button>
		<button class="btn btn-mini btn-danger decline-all">
			<i class="icon-remove"></i>Decline selected
		</button>
	</div>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
		'pager'=>[
			'class'=>\yii\widgets\LinkPager::className(),
			'maxButtonCount'=>7,
			'firstPageLabel'=>'First',
			'lastPageLabel'=>'Last',
		],
        'columns' => [
			[
				'class' => 'yii\grid\CheckboxColumn',
				'checkboxOptions'=>function($model){
					if(!(UserTransaction::TYPE_WITHDRAW == $model->type && UserTransaction::STATUS_NEW == $model->status)){
						return ['disabled'=>'disabled'];
					}

					return [];
				}
			],
            'id',
            [
				'attribute'=>'user_name',
				'value'=>function($model, $key, $index, $column)
				{
					return $model->user ? Html::encode($model->user->fullName) : '';
				}
			],
			[
				'attribute'=>'type',
				'value'=>function($model, $key, $index, $column)
				{
					return $model->getTypeName();
				},
				'filter'=>$searchModel->typeLabels(),
			],
			[
				'attribute'=>'status',
				'format'=>'raw',
				'value'=>function($model, $key, $index, $column)
				{
					if (UserTransaction::TYPE_WITHDRAW == $model->type && UserTransaction::STATUS_NEW == $model->status) {
						$cell =
							'<div id="status-column-'.$model->id.'">'.
							Html::a('approve', ['approve', 'id'=>$model->id],['class'=>'approve'])
							.' '.
							Html::a('decline', '#', ['class'=>'show-lightbox', 'rel'=>'decline-message', 'data-id'=>$model->id])
							.'</div>';
						return $cell;
					}
					return $model->getStatusName();
				},
				'filter'=>$searchModel->statusLabels(),
			],
			[
				'class'=>CustomDataColumn::className(),
				'attribute'=>'amount',
				'value'=>function($model, $key, $index, $column)
				{
					return (in_array($model->type, [
						UserTransaction::TYPE_WITHDRAW,
						UserTransaction::TYPE_CONTEST_ENTER
					]) ? "-" : "") . CommonHelper::toMoneyFormat($model->amount);
				},
				'filter_type'=>'range',
				'filterOptions'=>array(
					'attribute_from'=>'amount_from',
					'attribute_to'=>'amount_to'
				)
			],
			[
				'class'=>CustomDataColumn::className(),
				'attribute'=>'date_updated',
				'value'=>function($model, $key, $index, $column)
				{
					return date('Y-m-d H:i', strtotime($model->date_updated));
				},
				'filter_type'=>'dateRange',
				'filterOptions'=>array(
					'attribute_from'=>'start_date',
					'attribute_to'=>'end_date',
					'js_date_format'=>'yyyy-MM-dd',
					'php_date_format'=>'Y-m-d'
				)
			],

            [
				'class' => 'yii\grid\ActionColumn',
				'template'=>'{view}',
				'buttons' => [
					'view'=>function($url, $model, $key){
						return Html::a('<span class="glyphicon glyphicon-eye-open"></span>', $url, ['title'=>'View']);
					},
				]
			],
        ],
    ]); ?>

	<div class="mass-actions">
		<button class="btn btn-mini btn-success approve-all"><i class="icon-ok"></i>Approve selected</button>
		<button class="btn btn-mini btn-danger decline-all"><i class="icon-remove"></i>Decline selected</button>
	</div>
</div>

<script>
	ajaxLightbox['decline-all-message'] = {
		url : '<?= Url::to('decline'); ?>',
		afterCallback : function(data) {
			$('#user-transactions-grid').yiiGridView('update');
		},
		cancelCallback: function() {
			toggleLoading();
		}
	};

	ajaxLightbox['decline-message'] = {
		url : '<?= Url::to('decline'); ?>',
		afterCallback : function(data) {
			$('#user-transactions-grid').yiiGridView('update');
		}
	};

	function toggleLoading() {
		$('.mass-actions button').toggleClass('hidden');
		$('.mass-actions .loading').toggleClass('hidden');
	}

	function showLoadingGif(id) {
		//$('#' + id).replaceWith('<img src="<?php //echo Yii::$app->request->baseUrl; ?>/images/ajax.gif" class="m_center" id="status-column-' + id + '" />');
	}

	$(function(){
		$('.approve').on('click', function(){
			showLoadingGif($(this).parent('div').attr('id'));
			$.post($(this).attr('href'), {}, function(data){
				if (data && data.message) {
					alert(data.message);
				}
				$('#user-transactions-grid').yiiGridView('update');
			}, 'json');
			return false;
		});

		$('.approve-all').click(function(){
			var ids = $('#user-transactions-grid').yiiGridView('getChecked', 'user-transactions-grid_c0');
			if (!ids.length) {
				alert('<?= 'Please select transactions to approve'; ?>');
				return false;
			}
			toggleLoading();
			$.post('/admin/userTransactions/approve', {id: ids}, function(data){
				toggleLoading();
				if (data.message) {
					alert(data.message);
				}
				$('#user-transactions-grid').yiiGridView('update');
			}, 'json');
		});

		$('.decline-all').click(function(){
			var ids = $('#user-transactions-grid').yiiGridView('getChecked', 'user-transactions-grid_c0');
			if (!ids.length) {
				alert('<?= tt('Please select transactions to decline'); ?>');
				return false;
			}
			toggleLoading();
			showEditLightBox('decline-all-message', {id: ids});
		});
	});
</script>
