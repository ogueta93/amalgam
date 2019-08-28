<?php
// src/Business/Battle/Logic/ClaimBattleRewardLogic.php
namespace App\Business\Battle\Logic;

use App\Business\Battle\AbstractBattleLogic;
use App\Business\Battle\BattleException;
use App\Business\Battle\Builder\BattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleRewardTypeConstant;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Business\Battle\Traits\BattleLogicUtilsTrait;
use App\Entity\Battle;
use App\Entity\BattleStatus;
use App\Entity\CronEvent;
use App\Entity\User;
use App\Entity\UserCard;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class ClaimBattleRewardLogic extends AbstractBattleLogic
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
        $cronEventEnt = $this->em->getRepository(CronEvent::class)->findOneBy(['keyId' => $this->getCronEvent()]);

        if ($this->battleData['status']['id'] !== BattleStatusConstant::STARTED) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        if ($this->battleData['progress']['main']['phase'] !== BattleMainProgressPhaseConstant::REWARD_PHASE) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $winnerId = $this->battleData['progress']['main']['battleResult']['winner']['user']['id'] ?? null;
        if ($winnerId === null) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }
        if ($winnerId !== $user->getId()) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $expiredTime = new \DateTime($this->battleData['progress']['main']['battleResult']['winner']['rewardExpiredTime']);
        $now = new \DateTime();
        if (!$cronEventEnt && $expiredTime < $now) {
            $this->battleException->throwError(BattleException::REWARD_HAS_EXPIRED);
        }

        $this->data['rewardType'] = $this->battleData['progress']['main']['battleResult']['winner']['rewardType'];

        if ($userCardId !== null) {
            $loserId = $this->battleData['progress']['main']['battleResult']['loser']['user']['id'];

            $rivalCards = \array_values(\array_filter($this->battleData['progress']['main']['cardsSelection'], function ($element) use ($loserId) {
                return $element['userId'] === $loserId;
            }));
            if (!$rivalCards) {
                $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
            }

            $userCard = \array_values(\array_filter($rivalCards[0]['cards'], function ($element) use ($userCardId) {
                return $element['userCardId'] === $userCardId;
            }));
            if (!$userCard || !$userCard[0]['captured']) {
                $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
            }
        } elseif ($this->data['rewardType'] !== BattleRewardTypeConstant::PERFECT_REWARD) {
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
        $userCardId = $this->inputData['userCardId'] ?? null;
        $loserId = $this->battleData['progress']['main']['battleResult']['loser']['user']['id'];

        $battleBuilder = new BattleBuilder($this->battleData);
        $this->battleData = $battleBuilder->takeReward($this->inputData);

        $this->data['battleEnt'] = $this->em->getRepository(Battle::class)->find($this->battleData['id']);
        $battleStatusEnt = $this->em->getRepository(BattleStatus::class)->find(BattleStatusConstant::FINISHED);
        $this->data['battleEnt']->setBattleStatus($battleStatusEnt);
        $this->em->flush();

        $this->releaseCardsSelection($loserId, false, true);

        if ($this->data['rewardType'] !== BattleRewardTypeConstant::PERFECT_REWARD) {
            $this->claimCards($userCardId);
        } else {
            $this->claimCards(null, $loserId);
        }
    }

    /**
     * Claims the cards to the winner id
     *
     * @param int|null $userCardId
     * @param int|null $loserId
     *
     * @return void
     */
    protected function claimCards(?int $userCardId = null, ?int $loserId = null)
    {
        $winnerUser = $this->getLoggedUser();

        if ($userCardId !== null) {
            $userCard = $this->em->getRepository(UserCard::class)->find($userCardId);
            $userCard->setIdUser($winnerUser);
        } else {
            $userCardsIds = [];
            foreach ($this->battleData['progress']['main']['cardsSelection'] as $key => $userCardSelection) {
                if ($userCardSelection['userId'] === $loserId) {
                    foreach ($userCardSelection['cards'] as $keyCard => $card) {
                        $userCardsIds[] = $card['userCardId'];
                    }
                    break;
                }
            }

            $userCards = $this->em->getRepository(UserCard::class)->findBy(['id' => $userCardsIds]);
            \array_walk($userCards, function ($userCard) use ($winnerUser) {
                $userCard->setIdUser($winnerUser);
            });
        }

        $this->em->flush();
    }
}
