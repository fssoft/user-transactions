<?php

namespace fssoft\userTransactions\models;

/**
 * Class DetailUserTransaction
 * @package fssoft\userTransactions\models
 */
class DetailUserTransaction extends UserTransaction
{
    /**
     * @param string $default
     * @return string
     */
    public function getUserName($default = '')
    {
        if ($user = self::getUser($this->userNamespace)) {
            return $user->getName();
        }
        return $default;
    }

    /**
     * @param string $default
     * @return string
     */
    public function getContestName($default = 'n/a')
    {
        if (in_array($this->type, [
            UserTransaction::TYPE_CONTEST_CANCEL,
            UserTransaction::TYPE_CONTEST_ENTER,
            UserTransaction::TYPE_CONTEST_LEAVE,
            UserTransaction::TYPE_CONTEST_WIN
        ])) {

            $contestName = 'Contest';
            if ($contestUser = self::getContestUser($this->contestUserNamespace)) {
                $contestName = ' ' . $contestUser->contest->title;
            }
            return $contestName;
        }

        return $default;
    }
}
