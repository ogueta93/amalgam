<?php
// src/Business/Battle/Constant/BattleMainProgressPhaseConstant.php
namespace App\Business\Battle\Constant;

use App\Base\Constant\AbstractConstant;

class BattleMainProgressPhaseConstant extends AbstractConstant
{
    const CARD_SELECTION_PHASE = 1;
    const COIN_THROW_PHASE = 2;
    const BATTLE_PHASE = 3;
    const REWARD_PHASE = 4;
    const FINISH_PHASE = 5;
}
