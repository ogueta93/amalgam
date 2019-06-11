<?php
// src/Business/AbstractNotification.php
namespace App\Business;

use App\Service\WsServerApp\Traits\WsUtilsTrait;

abstract class AbstractNotification
{
    use WsUtilsTrait;

    const GENERIC_NOTIFICATION = 'gameNotification';

    protected $data;
    protected $users;
    protected $event;

    public function __construct(array $data, array $users, $event = null)
    {
        $this->data = $data;
        $this->users = $users;
        $this->event = $event ?? self::GENERIC_NOTIFICATION;
    }

    /**
     * Notifies the event to the Ws Service about the event's value
     *
     * @return void
     */
    abstract public function notify();

    /**
     * Sents the notification to the Ws Service
     *
     * @param mixed $data
     * @param array $clients
     * @param string $event
     *
     * @return void
     */
    protected function sendNotification($data = null, array $clients, string $event)
    {
        $this->addWsResponseData($data, $clients, $event);
    }
}
