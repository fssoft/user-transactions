<?php

namespace fssoft\userTransactions\helpers;

/**
 * Class CommonHelper
 * @package fssoft\userTransactions\helpers
 */
class CommonHelper
{
    /**
     * @param $digit
     * @return string
     */
	public static function toMoneyFormat($digit)
	{
		return sprintf("%01.2f", (($digit*100)/100));
	}
}