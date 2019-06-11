<?php
// src/Business/UserBusiness.php
namespace App\Business;

use App\Manager\UserManager;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class UserBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Properties */

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets users
     *
     * @param array $content
     * @return void
     */
    public function getUsers($content)
    {
        $user = $this->getLoggedUser();

        $userManager = $this->container->get(UserManager::class);
        $data = $userManager->getByFilters($user->getId(), $content);

        $this->addWsResponseData($data);
    }
}
