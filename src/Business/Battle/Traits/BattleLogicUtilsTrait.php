<?php
// src/Business/Battle/Traits/BattleLogicUtilsTrait.php
namespace App\Business\Battle\Traits;

use App\Entity\User;
use App\Entity\UserCard;

trait BattleLogicUtilsTrait
{
    /**
     * Releases the cards that have been selected in this battle
     *
     * @param int|null $userId
     * @param bool $isWinner
     * @param bool captured
     * @return void
     */
    protected function releaseCardsSelection(?int $userId = null, $isWinner = true, $captured = false)
    {
        $userCards = [];

        if ($userId === null) {
            $userCards = $this->em->getRepository(UserCard::class)->findBy(['idBattle' => $this->data['battleEnt']]);
        } else {
            $filters = [];

            if (!$isWinner) {
                $filters = ['id' => $this->getUserCardsIdsByUserId($userId, $captured)];
            } else {
                $userEnt = $this->em->getRepository(User::class)->find($userId);
                $filters = ['idBattle' => $this->data['battleEnt'], 'idUser' => $userEnt];
            }

            $userCards = $this->em->getRepository(UserCard::class)->findBy($filters);
        }

        \array_walk($userCards, function ($userCard) {
            $userCard->setIdBattle(null);
        });

        $this->em->flush();
    }

    /**
     * Gets userCard ids that have not been captured
     *
     * @param $userId
     * @param $captured
     * @return array
     */
    protected function getUserCardsIdsByUserId(int $userId, $captured)
    {
        $cardsSelected = \array_values(\array_filter($this->battleData['progress']['main']['cardsSelection'], function ($element) use ($userId) {
            return $element['userId'] === $userId;
        }));

        $userCardsIds = [];
        foreach ($cardsSelected[0]['cards'] as $key => $element) {
            if ($element['captured'] === $captured) {
                $userCardsIds[] = $element['userCardId'];
            }
        }

        return $userCardsIds;
    }
}
