<?php
// src/Business/Battle/Constant/BattleTypeConstant.php
namespace App\Business\Battle\Constant;

use App\Business\Battle\AbstractBattleConstant;

class BattleTypeConstant extends AbstractBattleConstant
{
    protected $database = true;

    const SIMPLE = 1;
}
