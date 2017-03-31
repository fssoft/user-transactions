<?php

namespace fssoft\userTransactions\models;

use yii\base\Exception;
use yii\base\UnknownClassException;
use yii\base\InvalidCallException;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

/**
 * This is the model class for table "user_transaction".
 *
 * @property integer $id
 * @property integer $user_id
 * @property integer $type
 * @property integer $subject_id
 * @property integer $status
 * @property string $amount
 * @property string $date_updated
 * @property string $date_created
 *
 * @property User $user
 */
class UserTransaction extends \yii\db\ActiveRecord
{
    const TYPE_DEPOSIT = 1;
    const TYPE_WITHDRAW = 2;
    const TYPE_CONTEST_ENTER = 3;
    const TYPE_CONTEST_WIN = 4;
    const TYPE_CONTEST_CANCEL = 5;
    const TYPE_CONTEST_LEAVE = 6;
    const TYPE_PROMO_CODE = 7;
    const TYPE_THRESHOLD = 8;
    const TYPE_DEPOSIT_BONUS = 9;

    const STATUS_NEW = 0;               // new transaction, meaningful for TYPE_WITHDRAW, all other are SUCCESS by default
    const STATUS_SUCCESS = 1;           // admin approved withdraw request for type TYPE_WITHDRAW, success for other types
    const STATUS_DECLINED = 2;          // admin declined withdraw request
    const STATUS_CANCELLED = 3;         // user cancelled his withdraw request
    const STATUS_RETURNED_BONUS = 4;

    public $userNamespace;
    public $contestUserNamespace;

    /**
     * @throws UnknownClassException
     */
    public function init()
    {
        parent::init();

        if (!$this->userNamespace) {
            throw new UnknownClassException('userNamespace wasn\'t specified.');
        }

        if (!$this->contestUserNamespace) {
            throw new UnknownClassException('contestUserNamespace wasn\'t specified.');
        }

        $this->status = self::STATUS_NEW;
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'user_transaction';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'type', 'subject_id', 'status', 'amount'], 'required'],
            [['user_id', 'type', 'subject_id', 'status'], 'integer'],
            [['amount'], 'number'],
            [['date_updated', 'date_created'], 'safe'],
        ];
    }

    /**
     * @return array
     */
    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'createdAtAttribute' => 'date_created',
                'updatedAtAttribute' => 'date_updated',
                'value' => new Expression('NOW()'),
            ],
        ];
    }

    /**
     * @return array
     */
	public function typeLabels()
	{
		return [
			self::TYPE_DEPOSIT=>'Deposit',
			self::TYPE_WITHDRAW=>'Withdraw',
			self::TYPE_CONTEST_ENTER=>'Enter Contest',
			self::TYPE_CONTEST_LEAVE=>'Leave Contest',
			self::TYPE_CONTEST_WIN=>'Win Contest',
			self::TYPE_CONTEST_CANCEL=>'Contest Cancelled',
			self::TYPE_PROMO_CODE=>'Promo Code',
			self::TYPE_THRESHOLD=>'Threshold',
			self::TYPE_DEPOSIT_BONUS=>'Deposit Bonus',
		];
	}

    /**
     * @return mixed|null
     */
	public function getTypeName()
	{
		$types = $this->typeLabels();
		return isset($types[$this->type]) ? $types[$this->type] : null;
	}

    /**
     * @return array
     */
	public function statusLabels()
	{
		return [
			self::STATUS_NEW=>'New',
			self::STATUS_SUCCESS=>'Success',
			self::STATUS_DECLINED=>'Declined',
			self::STATUS_CANCELLED=>'Canceled',
			self::STATUS_RETURNED_BONUS=>'Returned',
		];
	}

    /**
     * @return mixed|null
     */
	public function getStatusName()
	{
		$statuses = $this->statusLabels();
		return isset($statuses[$this->status]) ? $statuses[$this->status] : null;
	}

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'user_id' => 'User ID',
            'type' => 'Type',
            'subject_id' => 'Subject ID',
            'status' => 'Status',
            'amount' => 'Amount',
            'date_updated' => 'Date Updated',
            'date_created' => 'Date Created',
        ];
    }

    /**
     * @param $modelName
     * @return \yii\db\ActiveQuery
     */
    public function getUser($modelName)
    {
        return static::hasOne($modelName::tableName(), ['id'=>'user_id']);
    }

    /**
     * @param $modelName
     * @return \yii\db\ActiveQuery
     */
    public function getContestUser($modelName)
    {
        return static::hasOne($modelName::tableName(), ['id'=>'subject_id']);
    }

    /**
     * @param $cUser
     * @return bool
     */
    public function win($cUser)
    {
        if (!$cUser instanceof $this->contestUserNamespace) {
            throw new UnknownClassException('Given object isn\'t instance of ' . $this->contestUserNamespace);
        }
        if (!$this->isNewRecord || $cUser->isNewRecord) {
            throw new InvalidCallException();
        }
        if (self::getWin($cUser)) {
            throw new Exception('Win transaction already exists');
        }

        $this->type = self::TYPE_CONTEST_WIN;
        $this->subject_id = $cUser->id;
        $this->user_id = $cUser->user_id;
        $this->status = self::STATUS_SUCCESS;
        $this->amount = $cUser->prize;

        if (!$this->save()) {
            return false;
        }

        return $cUser->user->credit($this);
    }

    /**
     * @param $cUser
     * @return bool
     * @throws Exception
     * @throws UnknownClassException
     */
    public function enter($cUser)
    {
        if (!$cUser instanceof $this->contestUserNamespace) {
            throw new UnknownClassException('Given object isn\'t instance of ' . $this->contestUserNamespace);
        }
        if (!$this->isNewRecord || $cUser->isNewRecord) {
            throw new InvalidCallException();
        }
        if (self::getEnter($cUser)) {
            throw new Exception('Enter transaction already exists');
        }

        $this->type = self::TYPE_CONTEST_ENTER;
        $this->subject_id = $cUser->id;
        $this->user_id = $cUser->user_id;
        $this->status = self::STATUS_SUCCESS;
        $this->amount = $cUser->getEntryFee();

        if (!$this->save()) {
            return false;
        }

        return $cUser->user->debit($this);
    }

    /**
     * @param $cUser
     * @return bool
     * @throws Exception
     * @throws UnknownClassException
     */
    public function leave($cUser)
    {
        if (!$cUser instanceof $this->contestUserNamespace) {
            throw new UnknownClassException('Given object isn\'t instance of ' . $this->contestUserNamespace);
        }
        if (!$this->isNewRecord || $cUser->isNewRecord) {
            throw new InvalidCallException();
        }
        if (self::getLeave($cUser)) {
            throw new Exception('Leave transaction already exists');
        }
        if (!$enterTr = self::getEnter($cUser)) {
            throw new Exception('Enter contest transaction not found');
        }

        $this->type = self::TYPE_CONTEST_LEAVE;
        $this->subject_id = $cUser->id;
        $this->user_id = $cUser->user_id;
        $this->status = self::STATUS_SUCCESS;
        $this->amount = $enterTr->amount;

        if (!$this->save()) {
            return false;
        }

        return $cUser->user->credit($this);
    }

    /**
     * @param $cUser
     * @return bool
     * @throws Exception
     * @throws UnknownClassException
     */
    public function terminate($cUser)
    {
        if (!$cUser instanceof $this->contestUserNamespace) {
            throw new UnknownClassException('Given object isn\'t instance of ' . $this->contestUserNamespace);
        }
        if (!$this->isNewRecord || $cUser->isNewRecord) {
            throw new InvalidCallException();
        }
        if (self::getTerminate($cUser)) {
            throw new Exception('Terminate transaction already exists');
        }
        if (!$enterTr = self::getEnter($cUser)) {
            throw new Exception('Enter contest transaction not found');
        }

        $this->type = self::TYPE_CONTEST_CANCEL;
        $this->subject_id = $cUser->id;
        $this->user_id = $cUser->user_id;
        $this->status = self::STATUS_SUCCESS;
        $this->amount = $enterTr->amount;

        if (!$this->save()) {
            return false;
        }

        return $cUser->user->credit($this);
    }

    /**
     * @return bool
     * @throws Exception
     */
    public function cancel()
    {
        if ($this->isNewRecord) {
            throw new InvalidCallException();
        }
        if (self::STATUS_CANCELLED == $this->status) {
            return true;
        }
        $this->status = self::STATUS_CANCELLED;
        if (!$this->save(false)) {
            return false;
        }
        switch ($this->type) {
            case self::TYPE_CONTEST_WIN:
                return $this->user->debit($this);
            default:
                throw new Exception('Not implemented for ' . $this->type);
        }
    }

    /**
     * @param $cUser
     * @param int $status
     * @return array|null|\yii\db\ActiveRecord
     * @throws UnknownClassException
     */
    private static function getEnter($cUser, $status = UserTransaction::STATUS_SUCCESS)
    {
        return self::getTransaction([
            'user_id' => $cUser->user_id,
            'subject_id' => $cUser->id,
            'type' => self::TYPE_CONTEST_ENTER,
            'status' => $status,
        ]);
    }

    /**
     * @param $cUser
     * @param int $status
     * @return array|null|\yii\db\ActiveRecord
     * @throws UnknownClassException
     */
    private static function getWin($cUser, $status = UserTransaction::STATUS_SUCCESS)
    {
        return self::getTransaction([
            'user_id' => $cUser->user_id,
            'subject_id' => $cUser->id,
            'type' => self::TYPE_CONTEST_WIN,
            'status' => $status,
        ]);
    }

    /**
     * @param $cUser
     * @param int $status
     * @return array|null|\yii\db\ActiveRecord
     * @throws UnknownClassException
     */
    private static function getLeave($cUser, $status = UserTransaction::STATUS_SUCCESS)
    {
        return self::getTransaction([
            'user_id' => $cUser->user_id,
            'subject_id' => $cUser->id,
            'type' => self::TYPE_CONTEST_LEAVE,
            'status' => $status,
        ]);
    }

    /**
     * @param $cUser
     * @param int $status
     * @return array|null|\yii\db\ActiveRecord
     * @throws UnknownClassException
     */
    private static function getTerminate($cUser, $status = UserTransaction::STATUS_SUCCESS)
    {
        return self::getTransaction([
            'user_id' => $cUser->user_id,
            'subject_id' => $cUser->id,
            'type' => self::TYPE_CONTEST_CANCEL,
            'status' => $status,
        ]);
    }

    /**
     * @param array $params
     * @return array|null|\yii\db\ActiveRecord
     */
    private static function getTransaction(array $params)
    {
        return self::find()
            ->andWhere($params)
            ->one();
    }
}
