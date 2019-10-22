<?php
// src/Service/WsServerApp/WsSecurity.php
namespace App\Service\WsServerApp;

use App\Base\Service\AbstractService;
use App\Entity\User;
use App\Service\JWToken;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Symfony\Component\HttpFoundation\Response;

class WsSecurity extends AbstractService
{
    use WsUtilsTrait;

    protected $jwToken;

    /**
     * Logins a user with email and password
     *
     * @param array $data
     * @return void
     */
    public function login(array $data): void
    {
        $user = $this->em->getRepository(User::class)->findOneBy(['email' => $data['email']]);
        $encoder = $this->container->get('security.password_encoder');

        if (!$user || !$encoder->isPasswordValid($user, $data['password'])) {
            throw new WsException(Response::HTTP_FORBIDDEN, [
                'message' => WsException::MSG_INVALID_CREDENTIALS
            ]);
        }

        $this->loggerUser($user);
        $this->addWsResponseData(['user' => $user->toArray()]);
        $this->setResponseToken($user, $this->jwToken->create($data['email']));
    }

    /**
     * Route for check if the connection is safe
     *
     * @return void
     */
    public function checkSecureConnection(): void
    {
        $this->addWsResponseData($this->getLoggedUser()->toArray());
    }

    /**
     * Responses the heart beat
     *
     * @return void
     */
    public function hearBeat(): void
    {
        $this->addWsResponseData(true);
    }

    /**
     * Checks the token provided by argument and updates the token if is valid
     *
     * @param string $token
     * @return void
     */
    public function checkToken($token): void
    {
        $notValidToken = false;
        $payLoad = false;

        try {
            $payLoad = $this->jwToken->decode($token);
        } catch (\Throwable $th) {
            $notValidToken = true;
        }

        if ($payLoad && !$notValidToken) {
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $payLoad['uid']]);
            if ($user) {
                $this->loggerUser($user);
                $this->setResponseToken($user, $this->jwToken->create($payLoad['uid']));
            }
        } else {
            throw new WsException(Response::HTTP_FORBIDDEN, [
                'message' => WsException::MSG_NOT_VALID_TOKEN,
                'phase' => WsException::WS_AMALGAN_PHASE_FATAL_ERROR
            ]);
        }
    }

    /**
     * Sets logged user
     *
     * @param $user
     * @return void
     */
    protected function loggerUser(User $user): void
    {
        $this->setLoggedUser($user);
        $this->setClientUserData($user, null, $this->checkAnonymousUser());
    }

    /**
     * Check if the user must be nonymous
     *
     * @return bool
     */
    protected function checkAnonymousUser(): bool
    {
        if ($this->getCronEvent()) {
            return true;
        }

        return false;
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
        $this->jwToken = $this->container->get(JWToken::class);
    }
}
