<?php
// src/Business/Battle/Notification/BattleNotification.php
namespace App\Business\Battle\Notification;

use App\Business\AbstractNotification;
use App\Entity\Battle;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use App\Service\WsServerApp\WsRouter\WsResponse;

class BattleNotification extends AbstractNotification
{
    use WsUtilsTrait;

    const NEW_BATTLE = 'newBattle';
    const ACCEPT_BATTLE = 'acceptBattle';
    const BATTLE_TURN_MOVEMENT = 'battleTurnMovement';
    const BATTLE_REWARD_CLAIMED = 'battleRewardClaimed';

    /**
     * Notifies the event to the Ws Service about the event's value
     *
     * @return void
     */
    public function notify()
    {
        switch ($this->event) {
            case self::NEW_BATTLE:
                $data = $this->getNewBattleNotificationData();
                break;
            case self::ACCEPT_BATTLE:
                $data = $this->getAcceptBattleNotificationData();
                break;
            case self::BATTLE_TURN_MOVEMENT:
                $data = $this->getBattleTurnMovementData();
                break;
            case self::BATTLE_REWARD_CLAIMED:
                $data = $this->getBattleRewardClaimedData();
                break;

            default:
                $data = $this->data;
                break;
        }

        $msgField = WsResponse::MSG_FIELD;
        $clients = \array_map(function ($client) use ($msgField, $data) {
            return [
                'id' => $client['id'],
                $msgField => $data
            ];
        }, $this->users);

        $this->sendNotification(null, $clients, self::GENERIC_NOTIFICATION);
    }

    /**
     * Gets data for the new battle event
     *
     * @return array $data
     */
    protected function getNewBattleNotificationData(): array
    {
        return [
            'battleId' => $this->data['id'],
            'createdBy' => $this->data['createdBy'],
            'battleType' => $this->data['type'],
            'type' => self::NEW_BATTLE
        ];
    }

    /**
     * Gets data for the new battle event
     *
     * @return array $data
     */
    protected function getAcceptBattleNotificationData(): array
    {
        return [
            'battleId' => $this->data['id'],
            'acceptedBy' => $this->data['acceptedBy'],
            'battleType' => $this->data['type'],
            'type' => self::ACCEPT_BATTLE
        ];
    }

    /**
     * Gets data for indicate the battle turn movement
     *
     * @return array $data
     */
    protected function getBattleTurnMovementData(): array
    {
        return [
            'battleId' => $this->data['id'],
            'createdBy' => $this->data['createdBy'],
            'battleType' => $this->data['type'],
            'battleResult' => $this->data['progress']['main']['battleResult'] ?? null,
            'user' => $this->getLoggedUser()->toArray(),
            'type' => self::BATTLE_TURN_MOVEMENT
        ];
    }

    /**
     * Gets data for indicate the claimed reward
     *
     * @return array $data
     */
    protected function getBattleRewardClaimedData(): array
    {
        return [
            'battleId' => $this->data['id'],
            'battleType' => $this->data['type'],
            'rewardType' => $this->data['progress']['main']['battleResult']['winner']['rewardType'],
            'cards' => $this->data['progress']['main']['battleResult']['winner']['rewardedCards'],
            'user' => $this->getLoggedUser()->toArray(),
            'type' => self::BATTLE_REWARD_CLAIMED
        ];
    }
}
