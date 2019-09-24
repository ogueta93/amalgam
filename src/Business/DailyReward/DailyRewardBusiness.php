<?php
// src/Business/DailyReward\DailyRewardBusiness.php
namespace App\Business\DailyReward;

use App\Business\DailyReward\DailyRewardType\DailyFreeBooster;
use App\Business\DailyReward\DailyRewardType\DailyRewardAbstract;
use App\Business\DailyReward\DailyRewardType\DailyWinRowBooster;
use App\Constant\DailyRewardTypeConstant;
use App\Entity\User;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class DailyRewardBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets a Daily Reward by int identificator
     *
     * @param int $id
     * @param User $user
     *
     * @return DailyRewardAbstract
     */
    public function getDailyRewardByType(int $id, User $user = null): DailyRewardAbstract
    {
        $user = $user ?? $this->getLoggedUser();

        switch ($id) {
            case DailyRewardTypeConstant::DAILY_BOOSTER:
                return new DailyFreeBooster($id, $user, $this->em);
                break;
            case DailyRewardTypeConstant::WIN_ROW_BOOSTER:
                return new DailyWinRowBooster($id, $user, $this->em);
                break;
            default:
                return null;
                break;
        }
    }
}
