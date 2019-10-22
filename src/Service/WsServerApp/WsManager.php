<?php
// src/Service/WsServerApp/WsManager.php
namespace App\Service\WsServerApp;

use App\Base\Service\AbstractService;
use App\Entity\User;
use App\Service\Cache;
use App\Service\Cache\CacheType;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\Router;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use App\Service\WsServerApp\WsRouter\WsResponse;
use Ratchet\ConnectionInterface;

class WsManager extends AbstractService
{
    use WsUtilsTrait;

    protected $router;

    protected static $cache;
    protected static $conn;
    protected static $clients = [];
    protected static $outsiders = [];

    const ID_FIELD = 'id';
    const USER_FIELD = 'user';
    const DATA_FIELD = 'data';

    /**
     * Sets clients data
     *
     * @param User $user
     * @param array $data
     * @param bool $anonymous
     * @return void
     */
    public static function setClient(User $user, $data = null, $anonymous = false)
    {
        foreach (self::$outsiders as $key => $outsider) {
            if ($outsider == self::$conn) {
                unset(self::$outsiders[$key]);
                break;
            }
        }

        if (!$anonymous) {
            self::$clients[$user->getId()] = self::$conn;
        }

        $clientData = [
            self::ID_FIELD => $user->getId(),
            self::USER_FIELD => $user->toArray(),
            self::DATA_FIELD => $data
        ];

        self::$cache->set(
            sprintf(CacheType::WS_CLIENT, $user->getId()),
            \json_encode($clientData),
            (new \DateTime('tomorrow'))->getTimestamp()
        );
    }

    /**
     * Gets connection
     *
     * @return ConnectionInterface
     */
    public static function getConn()
    {
        return self::$conn;
    }

    /**
     * Gets clients id and checks if he is connected
     *
     * @param int $clientId
     * @return bool
     */
    public static function getClientConnectionStatus($clientId): bool
    {
        $status = false;

        $clientData = \json_decode(self::$cache->get(sprintf(CacheType::WS_CLIENT, $clientId)), true) ?? null;
        if ($clientData) {
            $clientConnection = self::$clients[$clientData[self::ID_FIELD]] ?? null;
            if ($clientConnection) {
                $status = true;
            }
        }

        return $status;
    }

    /**
     * Gets clients ids and checks if they are connected in the ws
     *
     * @param array $clientIds
     * @return array
     */
    public static function getClientsConnectedByReference($clientIds): array
    {
        $connections = [];

        foreach ($clientIds as $id) {
            $clientData = \json_decode(self::$cache->get(sprintf(CacheType::WS_CLIENT, $id)), true) ?? null;
            if ($clientData) {
                $clientConnection = self::$clients[$clientData[self::ID_FIELD]] ?? null;
                if ($clientConnection) {
                    $connections[] = $id;
                }
            }
        }

        return $connections;
    }

    /**
     * Manages the message process
     *
     * @param ConnectionInterface $conn
     * @param string msg
     * @return void
     */
    public function processMessage(ConnectionInterface $conn, string $msg)
    {
        self::$conn = $conn;
        $response = null;

        try {
            $response = $this->router->process($msg);
        } catch (WsException $th) {
            $response = $this->router->processError($th);
        }

        $this->sendMessage($response);
    }

    /**
     * Sets temporal outsider client
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function addOutsider(ConnectionInterface $conn)
    {
        self::$outsiders[] = $conn;
    }

    /**
     * Close connection from the ws
     *
     * @param ConnectionInterface $conn
     * @return void
     */
    public function closeConnection(ConnectionInterface $conn)
    {
        self::$conn = $conn;

        $this->removeOutsider();
        $this->removeClientConnection();
    }

    /**
     * Sets config name
     *
     * @return void
     */
    protected function setConfigName()
    {}

    /**
     * Sets custom params
     *
     * @return void
     */
    protected function setCustomParams()
    {
        self::$cache = $this->container->get(Cache::class)->getClient();
        $this->router = $this->container->get(WsRouter::class);
    }

    /**
     * Manages the response and sends the message to the necesary clients
     *
     * @param WsResponse $response
     * @return void
     */
    protected function sendMessage(WsResponse $response)
    {
        if ($userMsg = $response->getMsg()) {
            self::$conn->send($userMsg);
        }

        $clients = $response->getUsersToSend();
        if ($clients) {
            foreach ($clients as $key => $clientId) {
                $client = $this->getClientConn($clientId);
                if ($client) {
                    $client->send($response->getMsg($clientId));
                }
            }
        }
    }

    /**
     * Gets decoded client data
     *
     * @param int $clientId
     * @return mixed
     */
    protected function getDecodedClientData($clientId)
    {
        return \json_decode(self::$cache->get(sprintf(CacheType::WS_CLIENT, $clientId)), true) ?? null;
    }

    /**
     * Gets client connection
     *
     * @param int $clientId
     * @return mixed
     */
    protected function getClientConn($clientId)
    {
        $clientConnection = null;

        $clientData = $this->getDecodedClientData($clientId);
        if ($clientData) {
            $clientConnection = self::$clients[$clientData[self::ID_FIELD]] ?? null;
        }

        return $clientConnection;
    }

    /**
     * Removes and outsider connection
     *
     * @return void
     */
    protected function removeOutsider()
    {
        foreach (self::$outsiders as $key => $outsider) {
            if ($outsider == self::$conn) {
                unset(self::$outsiders[$key]);
                break;
            }
        }
    }

    /**
     * If ther is a client connection remove his data from constant variable and stored data in memcache
     *
     * @return void
     */
    protected function removeClientConnection()
    {
        $clientId = null;

        foreach (self::$clients as $key => $client) {
            if ($client == self::$conn) {
                $clientId = $key;
                unset(self::$clients[$key]);
                break;
            }
        }

        if ($clientId) {
            self::$cache->delete(sprintf(CacheType::WS_CLIENT, $clientId));
        }
    }
}
