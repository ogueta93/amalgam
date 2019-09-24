<?php
// src/Business/DailyReward\DailyRewardType\DailyWinRowBooster.php
namespace App\Business\DailyReward\DailyRewardType;

use App\Business\DailyReward\DailyRewardType\DailyRewardAbstract;
use App\Constant\AppVariableConstant;
use App\Entity\AppVariable;
use App\Entity\DailyRewardType;
use App\Entity\User;
use App\Entity\UserAppVariable;
use Doctrine\ORM\EntityManagerInterface;

class DailyWinRowBooster extends DailyRewardAbstract
{
    protected $winRowCountEnt;

    public function __construct(int $id, User $user, EntityManagerInterface $em)
    {
        parent::__construct($id, $user, $em);
        $this->em->clear(UserAppVariable::class);

        $appVariableType = $this->em->getRepository(AppVariable::class)->find(AppVariableConstant::WIN_ROW_COUNT);
        $this->winRowCountEnt = $this->em->getRepository(UserAppVariable::class)->findOneBy(['user' => $this->user, 'appVariable' => $appVariableType]);
    }

    /**
     * Gets the win row count on the current day
     *
     * @return int
     */
    public function getWinRowCount()
    {
        if ($this->winRowCountEnt === null) {
            return 0;
        }

        $today = new \DateTime();
        $data = \json_decode($this->winRowCountEnt->getData(), true);
        $winRowDate = new \DateTime($data['date']) ?? null;

        if ($winRowDate && ($winRowDate->diff($today))->days > 0) {
            return 0;
        }

        return $data['count'] ?? 0;
    }

    /**
     * Adds one victory on the win row count
     *
     * @return void
     */
    public function addWinRowCount()
    {
        $data = [];
        $today = new \DateTime();

        if ($this->winRowCountEnt) {
            $data = \json_decode($this->winRowCountEnt->getData(), true);

            $winRowDate = new \DateTime($data['date']) ?? null;
            $data['date'] = $today->format('Y-m-d H:i:s');

            if ($winRowDate && ($winRowDate->diff($today))->days > 0) {
                $data['count'] = 1;
            } else {
                $data['count'] = $data['count'] + 1;
            }

            $this->winRowCountEnt->setData(\json_encode($data));
        } else {
            $appVariableType = $this->em->getRepository(AppVariable::class)->find(AppVariableConstant::WIN_ROW_COUNT);
            $data = [
                'date' => $today->format('Y-m-d H:i:s'),
                'count' => 1
            ];

            $winRowCountEnt = new UserAppVariable();
            $winRowCountEnt
                ->setUser($this->user)
                ->setAppVariable($appVariableType)
                ->setData(\json_encode($data))
                ->setCreatedAt($today)
                ->setUpdatedAt($today)
                ->setDeletedAt(null);

            $this->em->persist($winRowCountEnt);
        }

        $this->em->flush();
    }
}
