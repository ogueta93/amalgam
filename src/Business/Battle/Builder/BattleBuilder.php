<?php
// src/Business/Battle/Builder/BattleBuilder.php
namespace App\Business\Battle\Builder;

use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Constant\BattleUserStatusConstant;
use App\Entity\BattleStatus;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleBuilder
{
    use WsUtilsTrait;

    /** Constants */
    const FORMAT_DATE = 'Y-m-d H:i:s';

    /** Node Constants */
    const NODE_LAST_CHANGE = 'lastChange';
    const NODE_STATUS = 'status';
    const NODE_USER_ID = 'userId';
    const NODE_CHECKED = 'checked';

    const NODE_PROGRESS = 'progress';
    const NODE_PROGRESS_MAIN = 'main';
    const NODE_MAIN_CARDS_SELECTION = 'cardsSelection';
    const NODE_MAIN_PHASE = 'phase';
    const NODE_MAIN_COIN_THROW = 'cointThrow';

    /**
     * Creates a battle base array structure
     *
     * @param array $battleData
     * @param array $dataHelper
     *
     * @return array
     */
    public function createBattleBase(array $battleData, array $dataHelper): array
    {
        $battleData = [
            'id' => $dataHelper['battle']['id'],
            'type' => $dataHelper['battle']['type'],
            self::NODE_STATUS => $dataHelper['battle']['status'],
            'createdBy' => $dataHelper['User']->toArray(),
            'users' => $this->getUsersNode($dataHelper),
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        return $battleData;
    }

    /**
     * Adds the essential information for accepted current battle
     *
     * @param array $battleData
     * @param BattleStatus $battleStatus
     *
     * @return array
     */
    public function acceptNewBattle(array $battleData, BattleStatus $battleStatus): array
    {
        $battleData[self::NODE_STATUS] = $battleStatus->toArray();
        $battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        foreach ($battleData['users'] as $key => &$user) {
            if ($user['statusId'] == BattleUserStatusConstant::PENDING) {
                $user['statusId'] = BattleUserStatusConstant::ACCEPTED;
                $user['lastChange'] = $this->setLastChange();
            }
        }

        $battleData[self::NODE_PROGRESS] = [
            self::NODE_PROGRESS_MAIN => [self::NODE_MAIN_PHASE => BattleMainProgressPhaseConstant::CARD_SELECTION_PHASE]
        ];

        return $battleData;
    }

    /**
     * Add the user's cards selection to the battleData
     *
     * @param array $battleData
     * @param array $cardsSelected
     *
     * @return array
     */
    public function addUserCardsSelection(array $battleData, array $cardsSelected): array
    {
        $battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        $selection = \array_map(function ($card) {
            return \array_merge(['userCardId' => $card->getId()], $card->getIdCard()->toArray());
        }, $cardsSelected);

        $nodeCardsSelection = $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] ?? [];
        $nodeCardsSelection[] = [
            self::NODE_USER_ID => $this->getLoggedUser()->getId(),
            'cards' => $selection,
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];

        $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] = $nodeCardsSelection;

        if (\count($nodeCardsSelection) === 2) {
            $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::COIN_THROW_PHASE;

            $usersIds = \array_map(function ($element) {
                return $element['userId'];
            }, $nodeCardsSelection);

            $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_COIN_THROW] = $this->getCoinThrowNode($usersIds);
        }

        return $battleData;
    }

    /**
     * Add the user's cards selection to the battleData
     *
     * @param array $battleData
     *
     * @return array
     */
    public function setUserShowCoinThrow($battleData)
    {
        $today = new \DateTime();
        $completeShow = true;

        $battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

        foreach ($battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_COIN_THROW] as $key => &$userCoinThrow) {
            if ($userCoinThrow[self::NODE_USER_ID] === $this->getLoggedUser()->getId()) {
                $userCoinThrow[self::NODE_CHECKED] = true;
                $userCoinThrow[self::NODE_LAST_CHANGE] = $this->setLastChange();
            }

            if ($userCoinThrow[self::NODE_CHECKED] === false) {
                $completeShow = false;
            }
        }

        if ($completeShow) {
            $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::BATTLE_PHASE;
        }

        return $battleData;
    }

    /**
     * Set lastChange date
     *
     * @param array $battleData
     * @return array|string
     */
    protected function setLastChange($battleData = null)
    {
        $today = new \DateTime();

        if (\is_null($battleData)) {
            return $today->format(self::FORMAT_DATE);
        }

        $battleData[self::NODE_LAST_CHANGE] = $today->format(self::FORMAT_DATE);
        return $battleData;
    }

    /**
     * Gets UsersNode
     *
     * @param array $dataHelper
     * @return array
     */
    protected function getUsersNode(array $dataHelper): array
    {
        $usersNode = [];

        $mainUserId = $dataHelper['User']->getId();

        foreach ($dataHelper['userRelations'] as $key => $user) {
            $usersNode[] = [
                'user' => $user->toArray(),
                'statusId' => $user->getId() == $mainUserId ? BattleUserStatusConstant::ACCEPTED : BattleUserStatusConstant::PENDING,
                self::NODE_LAST_CHANGE => $this->setLastChange()
            ];
        }

        return $usersNode;
    }

    /**
     * Gets coinThrowNode
     *
     * @param array $usersIds
     *
     * @return array
     */
    protected function getCoinThrowNode(array $usersIds): array
    {
        $coinThrowNode = [];

        $randomNumber = \random_int(0, 1);
        $coinThrowNode[] = $this->getUserCoinThrowNode($usersIds, $randomNumber);

        $nextNumber = $randomNumber === 0 ? 1 : 0;
        $coinThrowNode[] = $this->getUserCoinThrowNode($usersIds, $nextNumber);

        return $coinThrowNode;
    }

    /**
     * Gets coinThrowNode
     *
     * @param array $usersIds
     * @param int $index
     *
     * @return array
     */
    protected function getUserCoinThrowNode(array $usersIds, int $index): array
    {
        return [
            self::NODE_USER_ID => $usersIds[$index],
            self::NODE_CHECKED => false,
            self::NODE_LAST_CHANGE => $this->setLastChange()
        ];
    }
}
