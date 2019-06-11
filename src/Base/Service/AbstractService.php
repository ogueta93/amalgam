<?php
// src/Base/Entity/AbstractService.php
namespace App\Base\Service;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Yaml\Yaml;
use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractService
{
    protected $container;
    protected $em;
    protected $security;
    protected $translator;

    protected $configName = null;
    protected $config = null;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em = null, Security $security = null, TranslatorInterface $translator = null)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->translator = $translator;

        $this->setConfigLoader();
        $this->setCustomParams();
    }

    /**
     * Sets config loaders
     */
    protected function setConfigLoader()
    {
        $this->setConfigName();

        if (!is_null($this->configName)) {
            $configDirectories = array($this->container->get('kernel')->getProjectDir() . '/config');
            $fileLocator = new FileLocator($configDirectories);
            $file = $fileLocator->locate($this->getConfigName(), null, false);

            $this->config = Yaml::parseFile($file[0]);
        }
    }

    /**
     * Returns configName
     *
     * @param string $configName
     */
    protected function getConfigName()
    {
        return $this->configName . '.yaml';
    }

    /**
     * Sets config name
     */
    abstract protected function setConfigName();

    /**
     * Sets custom params in the __construct
     */
    abstract protected function setCustomParams();
}
