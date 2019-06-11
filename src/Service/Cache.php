<?php
// src/Service/Cache.php
namespace App\Service;

use App\Base\Service\AbstractService;
use Symfony\Component\Cache\Adapter\MemcachedAdapter;

class Cache extends AbstractService
{
    protected $Client;

    protected $connection;

    /**
     * Gets the memcached client object
     *
     * @return object $cache
     */
    public function getClient()
    {
        $this->Client = MemcachedAdapter::createConnection(
            $this->connection,
            $this->config
        );

        return $this->Client;
    }

    /**
     * Sets config name
     *
     * @return void
     */
    protected function setConfigName()
    {
        $this->configName = 'cache';
    }

    /**
     * Sets custom params
     *
     * @return void
     */
    protected function setCustomParams()
    {
        $this->connection = $this->config['connection'];
        unset($this->config['connection']);
    }
}
