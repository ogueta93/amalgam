<?php
// src/Business/Battle/Logic/AcceptNewBattleLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\UserBattle;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class AcceptNewBattleLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;

    /**
     * Proves if the inputData is correct to process
     *
     * @throws BattleException
     */
    protected function proveIt()
    {
        $battleId = $this->inputData['battleId'] ?? null;
        $user = $this->getLoggedUser();

        if (\is_null($battleId)) {
            $this->battleException->throwError(BattleException::NOT_VALID_PARAMS);
        }

        if ($this->battleData['status']['id'] !== BattleStatusConstant::PENDING) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $battleEnt = $this->em->getRepository(Battle::class)->find($battleId);
        $battleRelations = $this->em->getRepository(UserBattle::class)->findBy(['battle' => $battleEnt]);
        if (\count($battleRelations) < 2) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        if ($this->battleData['createdBy']['id'] == $user->getId()) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $this->data['battleEnt'] = $battleEnt;
    }

    /**
     * Does logic work
     *
     * @return void
     */
    public function doIt()
    {
        $battleStatusEnt = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::STARTED);
        $this->data['battleEnt']->setBattleStatus($battleStatusEnt);
        $this->em->flush();

        $battleBuilder = new BattleBuilder();
        $this->battleData = $battleBuilder->acceptNewBattle($this->battleData, $battleStatusEnt);
    }
}
