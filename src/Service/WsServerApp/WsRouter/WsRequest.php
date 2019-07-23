<?php
// src/Service/WsServerApp/WsRouter\WsRequest.php
namespace App\Service\WsServerApp\WsRouter;

use App\Service\WsServerApp\Exception\WsException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Security;

class WsRequest
{
    /** Symfony properties */
    protected $container;
    protected $em;
    protected $security;

    protected $data;

    protected $action;
    protected $content;
    protected $token;
    protected $environment;

    const AVAILABLE_FIELDS = ['a', 'c', 't', 'ev'];
    const ENVIRONMENT_AVAILABLE_FIELDS = ['l'];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em = null, Security $security = null)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Sets data
     *
     * @param string $data
     * @return void
     */
    public function setData(string $data)
    {
        $this->data = \json_decode(base64_decode($data), true);

        $this->action = $this->data['a'];
        $this->content = $this->data['c'] ?? [];
        $this->token = $this->data['t'] ?? null;
        $this->environment = $this->data['ev'] ?? null;

        $this->checkValidParams();
    }

    /**
     * Gets data
     *
     * @return array $data
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * Gets action
     *
     * @return string $action
     */
    public function getAction(): string
    {
        return $this->action;
    }

    /**
     * Gets content
     *
     * @return array $content
     */
    public function getContent(): array
    {
        return $this->content;
    }

    /**
     * Gets token
     *
     * @return string $token
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * Gets environment
     *
     * @return string $environment
     */
    public function getEnvironment()
    {
        return $this->environment;
    }

    /**
     * Checks valid params for the request
     *
     * @return void
     */
    protected function checkValidParams(): void
    {
        $dataKeys = \array_keys($this->data);

        $injectedParam = false;
        foreach ($dataKeys as $key => $value) {
            if (!\in_array($value, self::AVAILABLE_FIELDS)) {
                $injectedParam = true;
                break;
            }
        }

        if ($injectedParam || !isset($this->data['a'])) {
            throw new WsException(Response::HTTP_BAD_REQUEST, [
                'message' => WsException::MSG_NOT_VALID_DATA_ON_WS_SERVICE
            ]);
        }

        $envInjectedParam = false;
        if (isset($this->data['ev'])) {
            $envDataKeys = \array_keys($this->data['ev']);

            foreach ($envDataKeys as $key => $value) {
                if (!\in_array($value, self::ENVIRONMENT_AVAILABLE_FIELDS)) {
                    $envInjectedParam = true;
                    break;
                }
            }

            if ($envInjectedParam) {
                throw new WsException(Response::HTTP_BAD_REQUEST, [
                    'message' => WsException::MSG_NOT_VALID_DATA_ON_WS_SERVICE
                ]);
            }
        }
    }
}
