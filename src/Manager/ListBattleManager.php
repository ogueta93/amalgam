<?php
// src/Manager/ListBattleManager.php
namespace App\Manager;

use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\UserBattle;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;
use Symfony\Contracts\Translation\TranslatorInterface;

class ListBattleManager
{
    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $validFilters = ['type'];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security, TranslatorInterface $translator)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->translator = $translator;
    }

    /**
     * Gets active battles by filters
     *
     * @param int $userId
     * @param array filters
     *
     * @return array $data
     */
    public function getActiveListByFilters($userId, $filters): array
    {
        $cleanFilters = $this->cleanFilters($filters);

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

        $translator = $this->translator;

        $data = \array_map(function ($battle) use ($translator) {
            return $battle->getBattle()->toArray($translator);
        }, $battles);

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
}
