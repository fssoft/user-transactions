<?php

namespace fssoft\userTransactions;

use yii\base\UnknownClassException;


/**
 * userTransactions module definition class
 */
class Module extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'fssoft\userTransactions\controllers';

    /**
     * Upload the namespace of user's model
     *
     * @var null
     */
    public $userNamespace = null;

    /**
     * Upload the namespace of contestUser's model
     *
     * @var null
     */
    public $contestUserNamespace = null;

    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();

        if ($this->userNamespace) {
            throw new UnknownClassException('userNamespace wasn\'t specified.');
        }

        if ($this->contestUserNamespace) {
            throw new UnknownClassException('contestUserNamespace wasn\'t specified.');
        }
    }
}
