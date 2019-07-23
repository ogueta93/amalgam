<?php
// src/Business/Battle/Logic/CardsSelectionLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class showCoinThrowLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;

    /**
     * Proves if the inputData is correct to process
     *
     * @throws BattleException
     * @return void
     */
    protected function proveIt()
    {
        $user = $this->getLoggedUser();

        if ($this->battleData['status']['id'] !== BattleStatusConstant::STARTED) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        if ($this->battleData['progress']['main']['phase'] !== BattleMainProgressPhaseConstant::COIN_THROW_PHASE) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        if (\count($this->battleData['progress']['main']['cointThrow']) < 2) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }
    }

    /**
     * Does logic work
     *
     * @return void
     */
    public function doIt()
    {
        $battleBuilder = new BattleBuilder($this->battleData);
        $this->battleData = $battleBuilder->setUserShowCoinThrow();
    }
}
