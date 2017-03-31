<?php

namespace fssoft\userTransactions\models;

/**
 * Interface IUserTransaction
 * @package fssoft\userTransactions\models
 */
interface IUserTransaction
{
    /**
     * @param UserTransaction $model
     * @return mixed
     */
    public function credit(UserTransaction $model);

    /**
     * @param UserTransaction $model
     * @return mixed
     */
    public function debit(UserTransaction $model);
}