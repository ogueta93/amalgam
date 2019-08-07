<?php
// src/Business/Battle/Logic/CardsSelectionLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\UserCard;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleMovementLogic extends AbstractBattleLogic
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
        $userCardId = $this->inputData['userCardId'] ?? null;
        $coordinates = $this->inputData['coordinates'] ?? null;

        if (!$userCardId || !$coordinates) {
            $this->battleException->throwError(BattleException::NOT_VALID_PARAMS);
        }

        if ($this->battleData['status']['id'] !== BattleStatusConstant::STARTED) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        if ($this->battleData['progress']['main']['phase'] !== BattleMainProgressPhaseConstant::BATTLE_PHASE) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        /** Checks if is the user's turn */
        $lastTurn = \end($this->battleData['progress']['turns']);
        if ($lastTurn['userId'] !== $user->getId()) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $cardsSelected = \array_values(\array_filter($this->battleData['progress']['main']['cardsSelection'], function ($element) use ($user) {
            return $element['userId'] === $user->getId();
        }));
        if (!$cardsSelected) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        $userCard = \array_values(\array_filter($cardsSelected[0]['cards'], function ($element) use ($userCardId) {
            return $element['userCardId'] === $userCardId;
        }));
        if (!$userCard || $userCard[0]['placed']) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $row = $coordinates['row'];
        $field = $coordinates['field'];
        if (\count($this->battleData['progress']['battleField']['board'][$row][$field]) !== 0) {
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
        $this->battleData = $battleBuilder->makeCardMovement($this->inputData);

        $battleFinished = $this->battleData['progress']['main']['battleResult'] ?? null;
        if (!\is_null($battleFinished)) {
            $battleEnt = $this->em->getRepository(Battle::class)->find($this->battleData['id']);
            $battleStatusEnt = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::FINISHED);
            $battleEnt->setBattleStatus($battleStatusEnt);

            $this->releaseCardsSelection($battleEnt);

            $this->em->flush();
        }
    }

    /**
     * Releases the cards that have been selected in this battle
     *
     * @param Battle $battleEnt
     * @return void
     */
    protected function releaseCardsSelection(Battle $battleEnt)
    {
        $userCards = $this->em->getRepository(UserCard::class)->findBy(['idBattle' => $battleEnt]);
        \array_walk($userCards, function ($userCard) {
            $userCard->setIdBattle(null);
        });
    }
}
