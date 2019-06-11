<?php
// src/Manager/UserManager.php
namespace App\Manager;

use App\Entity\User;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class UserManager
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $validFilters = ['nickName', 'options'];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets users by filters
     *
     * @param int $userId
     * @param array filters
     *
     * @return array $data
     */
    public function getByFilters($userId, $filters): array
    {
        $cleanFilters = $this->cleanFilters($filters);
        $options = $cleanFilters['options'] ?? null;

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('u')
            ->from(User::class, 'u')
            ->where('u.id != :id and u.nickname like :nickname')
            ->setParameters([
                'id' => $userId,
                'nickname' => '%' . $cleanFilters['nickName'] . '%'
            ]);

        $users = $qb->getQuery()->getResult();

        $data = \array_map(function ($user) {
            return $user->toArray();
        }, $users);

        if ($options) {
            $online = \in_array('online', $options);

            if ($online) {
                $class = $this;
                $data = \array_filter($data, function ($user) use ($class) {
                    return $class->getWsClientConnectionStatus($user['id']);
                });
            }
        }

        return $data;
    }

    /**
     * Cleans filters
     *
     * @param array $filters
     * @return array $cleanFilters
     */
    protected function cleanFilters(array $filters)
    {
        $validFilters = $this->validFilters;

        $cleanFilters = \array_filter($filters, function ($value, $key) use ($validFilters) {
            return \in_array($key, $validFilters);
        }, ARRAY_FILTER_USE_BOTH);

        return $cleanFilters;
    }
}
