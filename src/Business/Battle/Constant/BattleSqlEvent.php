<?php
// src/Business/Battle/Constant/BattleSqlEvent.php
namespace App\Business\Battle\Constant;

use App\Business\Battle\AbstractBattleConstant;

class BattleSqlEvent extends AbstractBattleConstant
{
    protected $database = true;

    const REWARD_EVENT = 'battle_reward_event';
    const REWARD_EVENT_TIME_MINUTES = 1;
}
