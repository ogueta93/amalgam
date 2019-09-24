<?php
// src/Business/DailyReward\DailyRewardType\DailyRewardAbstract.php
namespace App\Business\DailyReward\DailyRewardType;

use App\Entity\DailyRewardType;
use App\Entity\User;
use App\Entity\UserDailyReward;
use Doctrine\ORM\EntityManagerInterface;

abstract class DailyRewardAbstract
{
    /** Properties */
    protected $id;
    protected $user;
    protected $em;

    public function __construct(int $id, User $user, EntityManagerInterface $em)
    {
        $this->id = $id;
        $this->user = $user;
        $this->em = $em;

        $this->em->clear(UserDailyReward::class);
    }

    /**
     * Returs a boolean value if the daily is claimed
     *
     * @return bool
     */
    public function canBeClaimed(): bool
    {
        $dailyRewardTypeEnt = $this->em->getRepository(DailyRewardType::class)->find($this->id);
        $dailyFreeBoosterEnt = $this->em->getRepository(UserDailyReward::class)->findOneBy(['user' => $this->user, 'dailyRewardType' => $dailyRewardTypeEnt]);

        $today = new \DateTime();
        if ($dailyFreeBoosterEnt && $dailyFreeBoosterEnt->getClaimed() && ($dailyFreeBoosterEnt->getClaimed()->diff($today))->days < 1) {
            return false;
        }

        return true;
    }

    /**
     * Sets the claimed date
     *
     * @return void
     */
    public function setClaimed(): void
    {
        $today = new \DateTime();
        $dailyRewardTypeEnt = $this->em->getRepository(DailyRewardType::class)->find($this->id);
        $dailyFreeBoosterEnt = $this->em->getRepository(UserDailyReward::class)->findOneBy(['user' => $this->user, 'dailyRewardType' => $dailyRewardTypeEnt]);

        if ($dailyFreeBoosterEnt) {
            $dailyFreeBoosterEnt->setClaimed($today);
        } else {
            $dailyFreeBoosterEnt = new UserDailyReward();
            $dailyFreeBoosterEnt
                ->setDailyRewardType($dailyRewardTypeEnt)
                ->setUser($this->user)
                ->setClaimed($today)
                ->setCreatedAt($today)
                ->setUpdatedAt($today)
                ->setDeletedAt(null);

            $this->em->persist($dailyFreeBoosterEnt);
        }
        $this->em->flush();
    }
}
