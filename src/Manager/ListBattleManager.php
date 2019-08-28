<?php
// src/Manager/ListBattleManager.php
namespace App\Manager;

use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\UserBattle;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class ListBattleManager
{
    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $validFilters = ['type'];
    protected $userId = null;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets active battles by filters
     *
     * @param int $userId
     * @param array filters
     *
     * @return array $data
     */
    public function getActiveUserListByFilters($userId, $filters): array
    {
        $this->userId = $userId;
        $cleanFilters = $this->cleanFilters($filters);
        $data = [];

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('ub')
            ->from(UserBattle::class, 'ub')
            ->join('ub.battle', 'b', 'WITH', 'b.battleType = :battleType and b.battleStatus != :battleStatus')
            ->where('ub.user = :userId')
            ->setParameters([
                'userId' => $userId,
                'battleType' => $cleanFilters['type'],
                'battleStatus' => BattleStatusConstant::FINISHED
            ]);

        $battles = $qb->getQuery()->getResult();
        foreach ($battles as $key => $battle) {
            if ($this->filterBattlesByData($battle)) {
                $data[] = $battle->getBattle()->toArray();
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
    protected function cleanFilters(array $filters): array
    {
        $validFilters = $this->validFilters;

        $cleanFilters = \array_filter($filters, function ($value, $key) use ($validFilters) {
            return \in_array($key, $validFilters);
        }, ARRAY_FILTER_USE_BOTH);

        return $cleanFilters;
    }

    /**
     * Filters battles by its own data
     *
     * @param array $battle
     * @return bool
     */
    protected function filterBattlesByData($battle): bool
    {
        $battleData = \json_decode($battle->getBattle()->getData(), true);

        $battleResult = $battleData['progress']['main']['battleResult'] ?? null;
        if ($battleResult) {
            $winner = $battleResult['winner'] ?? null;
            return $winner && $winner['user']['id'] === $this->userId;
        }

        return true;
    }
}
