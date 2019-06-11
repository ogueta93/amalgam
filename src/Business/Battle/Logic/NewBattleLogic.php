<?php
// src/Business/Battle/Logic/NewBatteLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\NewBattleBuilder;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\BattleType;
use App\Entity\User;
use App\Entity\UserBattle;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class NewBattleLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;

    /**
     * Proves if the inputData is correct to process
     *
     * @throws BattleException
     */
    protected function proveIt()
    {
        $users = $this->inputData['users'] ?? null;

        if (!$users) {
            $this->battleException->throwError(BattleException::NOT_VALID_PARAMS);
        }

        $loggedUser = $this->getLoggedUser();
        if (in_array($loggedUser->getId(), $users)) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $this->data['User'] = $loggedUser;
        $this->data['userRelations'] = [$loggedUser];

        foreach ($users as $key => $value) {
            $User = $this->em->getRepository(User::class)->find($value);
            if (!$User) {
                $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
            }

            $this->data['userRelations'][] = $User;
        }

        $this->data['BattleType'] = $this->em->getRepository(BattleType::class)->find($this->inputData['type'] ?? null);
        if (!$this->data['BattleType']) {
            $this->battleException->throwError(BattleException::NOT_VALID_PARAMS);
        }

        $this->data['BattleStatus'] = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::PENDING);
    }

    /**
     * Does logic work
     *
     * @return void
     */
    public function doIt()
    {
        $this->data['today'] = new \DateTime();

        /** Creating Battle */
        $newBattle = new Battle();
        $newBattle->setBattleType($this->data['BattleType']);
        $newBattle->setBattleStatus($this->data['BattleStatus']);
        $newBattle->setData(\json_encode([]));
        $newBattle->setCreatedAt($this->data['today']);
        $newBattle->setUpdatedAt($this->data['today']);
        $newBattle->setDeletedAt(null);

        $this->em->persist($newBattle);
        $this->em->flush();

        /** Creating User Battle Relations */
        foreach ($this->data['userRelations'] as $key => $user) {
            $userBattle = new UserBattle();
            $userBattle->setUser($user);
            $userBattle->setBattle($newBattle);
            $userBattle->setCreatedAt($this->data['today']);
            $userBattle->setUpdatedAt($this->data['today']);
            $userBattle->setDeletedAt(null);

            $this->em->persist($userBattle);
            $this->em->flush();
        }

        $this->data['battle'] = [
            'id' => $newBattle->getId(),
            'type' => [
                'id' => $this->data['BattleType']->getId(),
                'name' => $this->data['BattleType']->getName()
            ],
            'status' => [
                'id' => $this->data['BattleStatus']->getId(),
                'name' => $this->data['BattleStatus']->getName()
            ]
        ];
    }

    /**
     * Builts data
     *
     * @return void
     */
    protected function buildIt()
    {
        $newBattleBuilder = new NewBattleBuilder();
        $newBattleBuilder->setParams($this->data, []);

        $this->battleData = $newBattleBuilder->work();
    }
}
