<?php
// src/Business/Battle/Logic/BattleMovementLogic.php
namespace App\Business\Battle\Logic;

use App\Base\Constant\CronEventConstant;
use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Business\Battle\Traits\BattleLogicUtilsTrait;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\CronEvent;
use App\Entity\CronEventType;
use App\Entity\User;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleMovementLogic extends AbstractBattleLogic
{
    use WsUtilsTrait;
    use BattleLogicUtilsTrait;

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
        $finishPhase = $this->battleData['progress']['main']['phase'] === BattleMainProgressPhaseConstant::FINISH_PHASE ? true : false;

        $this->data['battleEnt'] = $this->em->getRepository(Battle::class)->find($this->battleData['id']);

        if (!\is_null($battleFinished) && $finishPhase) {
            /** Battle finished on draw */
            $battleStatusEnt = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::FINISHED);
            $this->data['battleEnt']->setBattleStatus($battleStatusEnt);

            $this->releaseCardsSelection();

        } else if (!\is_null($battleFinished)) {
            /** Battle finished with a winner, Reward process must to start */
            $winnerId = $this->battleData['progress']['main']['battleResult']['winner']['user']['id'];
            $winnerEmail = $this->battleData['progress']['main']['battleResult']['winner']['user']['email'];
            $loserId = $this->battleData['progress']['main']['battleResult']['loser']['user']['id'];

            $this->releaseCardsSelection($winnerId);
            $this->releaseCardsSelection($loserId, false);
            $this->makeRewardEvent($loserId, $winnerEmail);
        }

        $this->em->flush();
    }

    /**
     * Makes the reward event with a time limit
     *
     * @param int $userId
     * @param string $winnerEmail
     * @return void
     */
    protected function makeRewardEvent(int $userId, string $winnerEmail)
    {
        $today = new \DateTime();
        $expiredTime = $this->battleData['progress']['main']['battleResult']['winner']['rewardExpiredTime'];
        $expiredDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $expiredTime);
        $expiredDateTime->modify(\sprintf('+%s minutes', CronEventConstant::BATTLE_REWARD_EVENT_TIME_MINUTES));

        $keyId = \md5(\sprintf('%s%s%s', CronEventConstant::BATTLE_REWARD_EVENT, $this->battleData['id'], \uniqid()));
        $data = [
            'battleId' => $this->battleData['id'],
            'userCardsIds' => $this->getUserCardsIdsByUserId($userId, true),
            'rewardType' => $this->battleData['progress']['main']['battleResult']['winner']['rewardType'],
            'loggedEmail' => $winnerEmail
        ];

        $cronEventTypeEnt = $this->em->getRepository(CronEventType::class)->find(CronEventConstant::BATTLE_REWARD_EVENT);

        $cronEvent = new CronEvent();
        $cronEvent->setData(\json_encode($data));
        $cronEvent->setExpiredDateTime($expiredDateTime);
        $cronEvent->setKeyId($keyId);
        $cronEvent->setCronEventType($cronEventTypeEnt);
        $cronEvent->setCreatedAt($today);
        $cronEvent->setUpdatedAt($today);
        $cronEvent->setDeletedAt(null);

        $this->em->persist($cronEvent);
        $this->em->flush();
    }
}
