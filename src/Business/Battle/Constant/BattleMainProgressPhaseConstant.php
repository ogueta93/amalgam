<?php
// src/Business/Battle/Constant/BattleMainProgressPhaseConstant.php
namespace App\Business\Battle\Constant;

use App\Business\Battle\AbstractBattleConstant;

class BattleMainProgressPhaseConstant extends AbstractBattleConstant
{
    const CARD_SELECTION_PHASE = 1;
    const COIN_THROW_PHASE = 2;
    const BATTLE_PHASE = 3;
    const FINISH_PHASE = 4;
}
