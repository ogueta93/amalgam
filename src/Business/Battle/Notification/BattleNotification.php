<?php
// src/Business/Battle/Notification/BattleNotification.php
namespace App\Business\Battle\Notification;

use App\Business\AbstractNotification;
use App\Entity\Battle;
use App\Service\WsServerApp\WsRouter\WsResponse;

class BattleNotification extends AbstractNotification
{
    const NEW_BATTLE = 'newBattle';
    const ACCEPT_BATTLE = 'acceptBattle';

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
}
