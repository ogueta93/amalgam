<?php
// src/Command/Traits/WsClientCommandTrait.php
namespace App\Command\Traits;

use App\Business\Battle\Constant\BattleRewardTypeConstant;

trait WsClientCommandTrait
{
    /**
     * Gets the body data from the battle Reward process
     *
     * @param array $data
     * @return array
     */
    protected function getBattleRewardEventBody(array $data): array
    {
        $content = [
            'battleId' => $data['battleId']
        ];

        if ($data['rewardType'] === BattleRewardTypeConstant::PERFECT_REWARD) {
            $content['userCardId'] = null;
        } else {
            $randonNumber = \rand(0, \count($data['userCardsIds']) - 1);
            $content['userCardId'] = $data['userCardsIds'][$randonNumber];
        }

        return [
            'a' => 'claimBattleReward',
            'c' => $content
        ];
    }
}
