<?php
// src/Business/Battle/Logic/CardsSelectionLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\UserCard;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class CardsSelectionLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;

    const NUM_CARDS_SELECTED = 5;

    /**
     * Proves if the inputData is correct to process
     *
     * @throws BattleException
     * @return void
     */
    protected function proveIt()
    {
        $cardsSelected = $this->inputData['cardsSelected'] ?? null;
        $user = $this->getLoggedUser();

        if (\is_null($cardsSelected) || \count($cardsSelected) !== self::NUM_CARDS_SELECTED) {
            $this->battleException->throwError(BattleException::NOT_VALID_PARAMS);
        }

        if ($this->battleData['status']['id'] !== BattleStatusConstant::STARTED) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        if ($this->battleData['progress']['main']['phase'] !== BattleMainProgressPhaseConstant::CARD_SELECTION_PHASE) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $cardsSelectedIds = \array_map(function ($element) {
            return $element['userCardId'];
        }, $cardsSelected);

        $userCardsSelected = $this->em->getRepository(UserCard::class)->findBy(['id' => $cardsSelectedIds]);
        if (\count($userCardsSelected) !== self::NUM_CARDS_SELECTED) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $this->data['userCardsSelected'] = $userCardsSelected;
    }

    /**
     * Does logic work
     *
     * @return void
     */
    public function doIt()
    {
        $battleBuilder = new BattleBuilder();
        $this->battleData = $battleBuilder->addUserCardsSelection($this->battleData, $this->data['userCardsSelected']);
    }
}
