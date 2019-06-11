<?php
// src/Service/WsServerApp/WsRouter\WsResponse.php
namespace App\Service\WsServerApp\WsRouter;

use App\Entity\User;
use App\Service\WsServerApp\Exception\WsException;

class WsResponse
{
    const LOCAL_PREFFIX = '-local';

    const DATA_PRIMARY_USER_FIELD = 'primaryUser';
    const DATA_USERS_FIELD = 'usersToSent';

    const ID_FIELD = 'id';
    const MSG_FIELD = 'msg';

    protected $data = [];
    protected $users = [];
    protected $action;
    protected $token;
    protected $error;

    public function __construct()
    {}

    /**
     * Gets json message encoded on base64
     *
     * @param mixed userId
     * @return string
     */
    public function getMsg($userId = null): string
    {
        if (\is_null($userId)) {
            $data = $this->getPrimaryUserMsg();
        } else {
            $data = $this->getUserMsg((int) $userId);
        }

        return $this->encodeResponseData($data);
    }

    /**
     * Gets users on the response
     *
     * @return mixed
     */
    public function getUsersToSend()
    {
        return $this->users;
    }

    /**
     * Sets response action
     *
     * @param string $action
     * @return void
     */
    public function setAction(string $action)
    {
        $this->action = $action;
    }

    /**
     * Sets response data
     *
     * @param mixed $data
     * @param mixed $usersData
     * @param mixed $event
     * @return void
     */
    public function addData($data = null, $usersData = null, $event = null)
    {
        $eventName = \is_null($event) ? $this->action : $event;
        $users = !\is_null($usersData) ? \array_filter($usersData) : $usersData;

        if (!is_null($usersData)) {
            $this->setUsersToSend($users);
        }

        $this->data[$eventName] = [
            self::DATA_PRIMARY_USER_FIELD => $data,
            self::DATA_USERS_FIELD => $users
        ];
    }

    /**
     * Sets token
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    public function setToken(User $user, string $token)
    {
        $this->token = [
            't' => ['user' => $user->toArray(), 'token' => $token]
        ];
    }

    /**
     * Sets users for send the response
     *
     * @param array $user
     * @return void
     */
    public function setUsersToSend(array $users)
    {
        $idField = self::ID_FIELD;

        $users = \array_map(function ($user) use ($idField) {
            return $user[$idField];
        }, $users);

        $this->users = \array_unique(\array_merge($this->users, $users));
    }

    /**
     * Sets error data
     *
     * @param WsException $th
     * @return void
     */
    public function setErrorData(WsException $th)
    {
        $this->error = ['e' => $th->getData()];
    }

    /**
     * Gets the primary user message
     *
     * @return array $data
     */
    protected function getPrimaryUserMsg(): array
    {
        $data = null;

        if (\is_null($this->error)) {
            foreach ($this->data as $action => $value) {
                $msg = $value[self::DATA_PRIMARY_USER_FIELD];
                if (!\is_null($msg)) {
                    $data['c'][] = \array_merge(['ra' => $action], ['m' => $msg]);
                }
            }

            if ($this->token) {
                $data = \array_merge($data, $this->token);
            }
        } else {
            $data['c'][] = \array_merge(['ra' => $this->action], $this->error);
        }

        return $data;
    }

    /**
     * Gets the user message by his id
     *
     * @param int $id
     * @return array $data
     */
    protected function getUserMsg($id): array
    {
        $data = [];

        if (\is_null($this->error)) {
            foreach ($this->data as $action => $value) {
                $usersData = $value[self::DATA_USERS_FIELD];

                if (!\is_null($usersData)) {
                    $idField = self::ID_FIELD;
                    $user = \array_filter($usersData, function ($userData) use ($id, $idField) {
                        return $userData[$idField] == $id;
                    });

                    if ($user) {
                        $userMsg = $user[0][self::MSG_FIELD] ?? $value[self::DATA_PRIMARY_USER_FIELD];

                        $data['c'][] = \array_merge(['ra' => $action], ['m' => $userMsg ?? []]);
                    }
                }
            }
        } else {
            $data['c'][] = \array_merge(['ra' => $this->action], $this->error);
        }

        return $data;
    }

    /**
     * Encodes array in base64 json string
     *
     * @param array $data
     * @return string
     */
    protected function encodeResponseData(array $data)
    {
        return base64_encode(\json_encode($data));
    }
}
