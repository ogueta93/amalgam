<?php
// src/Service/WsServerApp/WsRouter.php
namespace App\Service\WsServerApp;

use App\Base\Service\AbstractService;
use App\Entity\User;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\WsRouter\WsRequest;
use App\Service\WsServerApp\WsRouter\WsResponse;
use App\Service\WsServerApp\WsSecurity;
use Symfony\Component\HttpFoundation\Response;

class WsRouter extends AbstractService
{
    protected $routes;
    protected $service;
    protected $method;
    protected $wsRequest;

    protected static $wsResponse;
    protected static $user;

    const SERVICE = 'service';
    const METHOD = 'method';
    const TOKEN_SECURITY = 'tokenSecurity';

    /**
     * Sets wsResponse data
     *
     * @param mixed $data
     * @param mixed $usersData
     * @param mixed $event
     * @return void
     */
    public static function addWsResponseData($data = null, $usersData = null, $event = null)
    {
        self::$wsResponse->addData($data, $usersData, $event);
    }

    /**
     * Sets logged user
     *
     * @param User $token
     * @return void
     */
    public static function setUser(User $user)
    {
        self::$user = $user;
    }

    /**
     * Gets logged user
     *
     * @param User
     * @return mixed
     */
    public static function getUser()
    {
        return self::$user;
    }

    /**
     * Sets wsResponse token
     *
     * @param User $user
     * @param string $token
     * @return void
     */
    public static function setWsResponseToken(User $user, string $token)
    {
        self::$wsResponse->setToken($user, $token);
    }

    /**
     * Processes the request and does the router navigation
     *
     * @param string $msg
     * @return WsResponse
     */
    public function process(string $msg)
    {
        $this->setWsRequestData($msg);

        if (!\array_key_exists($this->wsRequest->getAction(), $this->routes)) {
            throw new WsException(Response::HTTP_BAD_REQUEST, [
                'message' => $this->translator->trans('actionDoesNotExists')
            ]);
        }

        $this->setRouterParams();

        return $this->execute();
    }

    /**
     * Processes a WsException
     *
     * @param WsException $th
     * @return WsResponse
     */
    public function processError(WsException $th)
    {
        self::$wsResponse = new WsResponse();
        self::$wsResponse->setAction($this->wsRequest->getAction());
        self::$wsResponse->setErrorData($th);

        return self::$wsResponse;
    }

    /**
     * Sets wsRequest data
     *
     * @param string $data
     * @return void
     */
    protected function setWsRequestData(string $data)
    {
        $this->wsRequest = $this->container->get(WsRequest::class);
        $this->wsRequest->setData($data);
    }

    /**
     * Sets config name
     *
     * @return void
     */
    protected function setConfigName()
    {
        $this->configName = 'wsRouter';
    }

    /**
     * Sets custom params
     *
     * @return void
     */
    protected function setCustomParams()
    {
        $this->routes = $this->config;
        $this->wsSecurity = $this->container->get(WsSecurity::class);
    }

    /**
     * Sets router params
     *
     * @return void
     */
    protected function setRouterParams()
    {
        self::$user = null;
        self::$wsResponse = new WsResponse();

        $this->setRouterEnvironment($this->wsRequest->getEnvironment());

        $actionData = $this->routes[$this->wsRequest->getAction()];

        $class = new \ReflectionClass($actionData[self::SERVICE]);

        $this->service = $this->container->get($class->getName());
        $this->method = $actionData[self::METHOD];
        $this->hasTokenSecurity = $actionData[self::TOKEN_SECURITY] ?? true;

        self::$wsResponse->setAction($this->wsRequest->getAction());
    }

    /**
     * Sets router process environment
     *
     * @param array|null $environment
     * @return void
     */
    protected function setRouterEnvironment($data)
    {
        $locale = $data['l'] ?? null;

        if ($locale) {
            \Locale::setDefault($locale);
            $this->translator->setLocale($locale);
        }
    }

    /**
     * Executes the service method and returns the encode msg
     *
     * @return WsResponse
     */
    protected function execute(): WsResponse
    {
        $method = $this->method;

        if ($this->hasTokenSecurity) {
            $this->wsSecurity->checkToken($this->wsRequest->getToken());
        }

        if (\method_exists($this->service, 'reset')) {
            $this->service->reset();
        }
        $this->service->$method($this->wsRequest->getContent());

        return self::$wsResponse;
    }
}
