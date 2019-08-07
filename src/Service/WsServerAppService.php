<?php
// src/Service/WsServerApp.php
namespace App\Service;

use App\Service\WsServerApp\WsManager;
use Psr\Container\ContainerInterface;
use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class WsServerAppService implements MessageComponentInterface
{
    /** Symfony Services */
    protected $container;

    protected $manager;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
        $this->manager = $this->container->get(WsManager::class);
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->manager->addOutsider($conn);
        print_r("Connection start \n");
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $this->manager->processMessage($from, $msg);
        print_r("Message complete \n");
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->manager->closeConnection($conn);
        print_r("Bye \n");
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        $this->manager->closeConnection($conn);
        print_r("Error {$e->getMessage()}\n");
    }
}
