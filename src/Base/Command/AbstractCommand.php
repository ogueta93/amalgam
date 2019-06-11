<?php
// src/Base/Command/AbstractCommand.php
namespace App\Base\Command;

use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Security\Core\Security;

abstract class AbstractCommand extends Command
{
    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;

        parent::__construct();
    }
}
