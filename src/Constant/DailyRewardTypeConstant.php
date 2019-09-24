<?php
// src/Constant/DailyRewardTypeConstant.php
namespace App\Constant;

use App\Base\Constant\AbstractConstant;

class DailyRewardTypeConstant extends AbstractConstant
{
    protected $database = true;

    const DAILY_BOOSTER = 1;
    const WIN_ROW_BOOSTER = 2;
}
