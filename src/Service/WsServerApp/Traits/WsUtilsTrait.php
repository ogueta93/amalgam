<?php
// src/Service/WsServerApp/Trait/WsUtilsTrait.php
namespace App\Service\WsServerApp\Traits;

use App\Entity\User;
use App\Service\WsServerApp\WsManager;
use App\Service\WsServerApp\WsRouter;

trait WsUtilsTrait
{
    /**
     * Sets logged user
     *
     * @param User $user
     * @return void
     */
    protected function setLoggedUser(User $user)
    {
        WsRouter::setUser($user);
    }

    /**
     * Sets response data
     *
     * @param mixed $data
     * @param mixed $users
     * @param mixed $event
     * @return void
     */
    protected function addWsResponseData($data = null, $users = null, $event = null)
    {
        WsRouter::addWsResponseData($data, $users, $event);
    }

    /**
     * Sets token
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    protected function setResponseToken(User $user, string $token)
    {
        WsRouter::setWsResponseToken($user, $token);
    }

    /**
     * Gets logger user
     *
     * @return mixed
     */
    protected function getLoggedUser()
    {
        return WsRouter::getUser();
    }

    /**
     * Adds client data on memcache
     *
     * @param User $user
     * @param array $data
     * @return void
     */
    protected function setClientUserData(User $user, $data = null)
    {
        WsManager::setClient($user, $data);
    }

    /**
     * Gets client connection status
     *
     * @param int $clientId
     * @return bool
     */
    public static function getWsClientConnectionStatus($clientId): bool
    {
        return WsManager::getClientConnectionStatus($clientId);
    }

    /**
     * Gets clients ids and checks if they are connected in the ws
     *
     * @param array $clientIds
     * @return array
     */
    public static function getWsClientsConnectedByReference($clientIds): array
    {
        return WsManager::getClientsConnectedByReference($clientIds);
    }
}
