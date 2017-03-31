<?php

namespace fssoft\userTransactions\controllers;

use Yii;
use backend\controllers\Controller;
use fssoft\userTransactions\models\{
    UserTransaction,
    DetailUserTransaction,
    SearchUserTransactions
};
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;


/**
 * DefaultController implements the CRUD actions for UserTransactions model.
 */
class DefaultController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all UserTransactions models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new SearchUserTransactions;
        $dataProvider = $searchModel->search(
            Yii::$app->request->queryParams,
            $this->module->userNamespace
        );

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single DetailUserTransaction model.
     * @param string $id
     * @return mixed
     */
    public function actionView($id)
    {
        if (!$model = DetailUserTransaction::findOne($id)) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $this->render('view', [
            'model' => $model
        ]);
    }

    /**
     * Creates a new UserTransactions model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new UserTransaction();

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing UserTransaction model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param string $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing UserTransaction model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param string $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

	public function actionApprove()
	{
		$r = [];

		$transactions = $this->findModel(Yii::$app->request->get('id'));
		if (!is_array($transactions)) {
			$transactions = [$transactions];
		}
		$payParams = [];
		$index = 0;
		foreach ($transactions as $model) {
			if (UserTransaction::TYPE_WITHDRAW == $model->type && UserTransaction::STATUS_NEW == $model->status) {
				$payParams["receiverList.receiver($index).email"] = $model->user->paypal_email;
				$payParams["receiverList.receiver($index).amount"] = $model->amount;
				$payParams["receiverList.receiver($index).invoiceId"] = $model->id;
				++$index;

				$model = $this->findModel($model->id);
				$model->updateStatus(UserTransaction::STATUS_SUCCESS, '');
			}
		}
		if (!$payParams) {
			Yii::$app->end(json_encode(array('message'=>'Nothing to approve')));
		}

		/** Code for Paypal transactions
		 * $payParams['cancelUrl'] = $this->createAbsoluteUrl('site/index'); // dummy url
		$payParams['returnUrl'] = $this->createAbsoluteUrl('site/index'); // dummy url
		$r = array();
		try {
		$paykey = Yii::app()->paypal->systemPay($payParams);
		$details = Yii::app()->paypal->paymentDetails($paykey);
		if ('COMPLETED' == $details['status']) {
		$details = Yii::app()->paypal->groupResponse($details);
		EmailTemplate::model()->beginBatchSend();
		foreach ($details['paymentInfoList_paymentInfo'] as $info) {
		// TODO maybe we need to check for pending and other types of payment statuses
		$model = $this->loadModel($info['receiver_invoiceId']);
		$model->updateStatus(UserTransaction::STATUS_SUCCESS, $info);

		$emailData = array(
		'withdraw_amount'=>('£' . $info['receiver_amount']),
		'username'=>$model->user->username
		);
		EmailTemplate::model()->sendTemplateMessage($model->user->email, 'withdrawApproved', $emailData);
		}
		EmailTemplate::model()->endBatchSend();
		}
		} catch (PaypalHTTPException $e) {
		$r['message'] = $e->getMessage() . ' Try again later';
		} catch (PaypalResponseException $e) {
		$r['message'] = $e->getMessage();
		//			$r['log'] = print_r($e->response, true);
		}*/

		Yii::app()->end(json_encode($r));
	}

	public function actionDecline()
	{
		$r['isErrors'] = true;
		$transactions = $this->findModel(Yii::$app->request->getParam('id'));
		if (!is_array($transactions)) {
			$transactions = array($transactions);
		}
		$declineForm = new DeclineWithdrawForm();
		if (isset($_POST['DeclineWithdrawForm'])) {
			$declineForm->attributes = $_POST['DeclineWithdrawForm'];
			if ($declineForm->validate()) {
				//EmailTemplate::model()->beginBatchSend();
				foreach ($transactions as $model) {
					if (UserTransaction::TYPE_WITHDRAW == $model->type && UserTransaction::STATUS_NEW == $model->status) {
						$model->user->addBalance($model->amount);
						$model->updateStatus(UserTransaction::STATUS_DECLINED, array(
							'message'=>$declineForm->message,
						));
						$emailData = array(
							'reason'=>$declineForm->message,
							'username'=>$model->user->username,
							'amount'=> $model->amount
						);
						EmailTemplate::model()->sendTemplateMessage($model->user->email, 'withdrawDeclined', $emailData);
					}
				}
				//EmailTemplate::model()->endBatchSend();
				$r['isErrors'] = false;
			}
		}
		if ($r['isErrors']) {
			$this->viewVars = array(
				'lbox_id'=>Yii::app()->request->getParam('lbox_id'),
				'declineForm'=>$declineForm,
				'transaction_id'=>Yii::app()->request->getParam('id'),
			);
			$r['content'] = $this->renderPartial('_decline', array(), true);
		}
		Yii::app()->end(json_encode($r));
	}

    /**
     * Finds the UserTransaction model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param string $id
     * @return UserTransaction the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = UserTransaction::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
