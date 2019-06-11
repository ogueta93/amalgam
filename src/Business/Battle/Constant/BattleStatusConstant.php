<?php
// src/Business/Battle/Constant/BattleStatusConstant.php
namespace App\Business\Battle\Constant;

use App\Business\Battle\AbstractBattleConstant;

class BattleStatusConstant extends AbstractBattleConstant
{
    protected $database = true;

    const PENDING = 1;
    const STARTED = 2;
    const FINISHED = 3;
}
