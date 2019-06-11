<?php
// src/Business/Battle/Logic/AcceptNewBattleLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\AcceptNewBattleBuilder;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Business\Battle\Constant\BattleUserStatusConstant;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\UserBattle;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class AcceptNewBattleLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;

    const FORMAT_DATE = 'Y-m-d H:i:s';

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

        $battleEnt = $this->em->getRepository(Battle::class)->find($battleId);
        if (!$battleEnt) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        if ($battleEnt->getBattleStatus()->getId() !== BattleStatusConstant::PENDING) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $userBattle = $this->em->getRepository(UserBattle::class)->findBy(['user' => $user, 'battle' => $battleEnt]);
        if (!$userBattle) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $battleRelations = $this->em->getRepository(UserBattle::class)->findBy(['battle' => $battleEnt]);
        if (count($battleRelations) < 2) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        $this->battleData = \json_decode($battleEnt->getData(), true);
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
        $today = new \DateTime();

        $battleStatusEnt = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::STARTED);
        $this->data['battleEnt']->setBattleStatus($battleStatusEnt);
        $this->em->flush();

        $this->battleData['status'] = $battleStatusEnt->toArray();
        $this->battleData['lastChange'] = $today->format(self::FORMAT_DATE);

        foreach ($this->battleData['users'] as $key => &$user) {
            if ($user['statusId'] == BattleUserStatusConstant::PENDING) {
                $user['statusId'] = BattleUserStatusConstant::ACCEPTED;
                $user['lastChange'] = $today->format(self::FORMAT_DATE);
            }
        }
    }

    /**
     * Builts data
     *
     * @return void
     */
    protected function buildIt()
    {
        $newBattleBuilder = new AcceptNewBattleBuilder();
        $newBattleBuilder->setParams($this->data, $this->battleData);

        $this->battleData = $newBattleBuilder->work();
    }
}
