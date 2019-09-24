<?php
// src/Business/Battle/Constant/BattleStatusConstant.php
namespace App\Business\Battle\Constant;

use App\Base\Constant\AbstractConstant;

class BattleStatusConstant extends AbstractConstant
{
    protected $database = true;

    const PENDING = 1;
    const STARTED = 2;
    const FINISHED = 3;
}
