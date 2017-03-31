<?php

namespace fssoft\userTransactions\models;

use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * SearchUserTransactions represents the model behind the search form about `common\models\user\UserTransactions`.
 */
class SearchUserTransactions extends UserTransaction
{
	public $user_name;
	public $amount_from;
	public $amount_to;
	public $start_date;
	public $end_date;

	public function init()
	{
		parent::init();
		$this->status = NULL;
	}

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_name', 'start_date', 'end_date'], 'filter', 'filter'=>'trim'],
            [['user_name', 'amount_from', 'amount_to', 'id', 'user_id', 'type', 'subject_id',
				'status', 'amount', 'date_updated'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $userNamespace)
    {
        $query = self::find();

        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            return $dataProvider;
        }

		$query->from(self::tableName().' t');
        $query->joinWith($userNamespace::tableName());
//		$query->leftJoin(
//		    $this->userNamespace::tableName(), [
//		        $this->userNamespace::tableName().'.id'=>'t.user_id'
//        ]);

        // grid filtering conditions
        $query->andFilterWhere([
            't.id' => $this->id,
            't.user_id' => $this->user_id,
            't.type' => $this->type,
            't.subject_id' => $this->subject_id,
            't.status' => $this->status,
            't.amount' => $this->amount,
            't.date_updated' => $this->date_updated,
        ]);

        $query->andFilterWhere(['>=', 't.amount', $this->amount_from])
			->andFilterWhere(['<=', 't.amount', $this->amount_to])
			->andFilterWhere(['>=', 't.date_updated', $this->start_date])
			->andFilterWhere(['<=', 't.date_updated', $this->end_date])
			->andFilterWhere(['like', $userNamespace::getFilterLikeUserName(), $this->user_name]);

		$sort = $dataProvider->getSort();
		$sort->attributes = array_merge($sort->attributes, [
			'user_name' => $userNamespace::getSortByUserName(),
		]);
		$sort->defaultOrder = ['id'=>SORT_DESC];
		$dataProvider->setSort($sort);

		return $dataProvider;
    }
}
