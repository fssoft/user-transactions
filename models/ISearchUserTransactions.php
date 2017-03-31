<?php

namespace fssoft\userTransactions\models;

/**
 * Interface ISearchUserTransactions
 * @package fssoft\userTransactions\models
 */
interface ISearchUserTransactions
{
    /**
     * @return mixed
     */
    public static function getFilterLikeUserName();

    /**
     * @return mixed
     */
    public static function getSortByUserName();
}