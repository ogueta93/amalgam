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

        /** Checks if the action is not performed yet */
        $cardSelectionNode = $this->battleData['progress']['main']['cardsSelection'] ?? [];
        $findedUserSelection = \array_filter($cardSelectionNode, function ($cardSelection) use ($user) {
            return $cardSelection['userId'] === $user->getId();
        });
        if (\count($findedUserSelection) > 0) {
            $this->battleException->throwError(BattleException::ACTION_IS_ALREADY_PERFORMED);
        }

        $cardsSelectedIds = \array_map(function ($element) {
            return $element['userCardId'];
        }, $cardsSelected);

        $userCardsSelected = $this->em->getRepository(UserCard::class)->findBy(['id' => $cardsSelectedIds, 'idBattle' => null]);
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
        $battleEnt = $this->em->getRepository(Battle::class)->find((int) $this->battleData['id']);
        \array_walk($this->data['userCardsSelected'], function ($userCard) use ($battleEnt) {
            $userCard->setIdBattle($battleEnt);
        });

        $this->em->flush();

        $battleBuilder = new BattleBuilder($this->battleData);
        $this->battleData = $battleBuilder->addUserCardsSelection($this->data['userCardsSelected']);
    }
}
