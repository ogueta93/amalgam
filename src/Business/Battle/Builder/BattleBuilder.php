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

    const NODE_PROGRESS = 'progress';
    const NODE_PROGRESS_MAIN = 'main';
    const NODE_MAIN_CARDS_SELECTION = 'cardsSelection';
    const NODE_MAIN_PHASE = 'phase';

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
        $selection = [];
        \array_walk($cardsSelected, function ($card) use (&$selection) {
            $selection[] = \array_merge(['userCardId' => $card->getId()], $card->getIdCard()->toArray());
        });

        $nodeCardsSelection = $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] ?? [];
        $nodeCardsSelection[] = [$this->getLoggedUser()->getId() => $selection];

        $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_CARDS_SELECTION] = $nodeCardsSelection;

        if (\count($nodeCardsSelection) === 2) {
            $battleData[self::NODE_PROGRESS][self::NODE_PROGRESS_MAIN][self::NODE_MAIN_PHASE] = BattleMainProgressPhaseConstant::COIN_THROW_PHASE;
        }

        $battleData[self::NODE_LAST_CHANGE] = $this->setLastChange();

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
    protected function getUsersNode($dataHelper): array
    {
        $usersNode = [];

        $mainUserId = $dataHelper['User']->getId();

        foreach ($dataHelper['userRelations'] as $key => $user) {
            $usersNode[] = [
                'user' => $user->toArray(),
                'statusId' => $user->getId() == $mainUserId ? BattleUserStatusConstant::ACCEPTED : BattleUserStatusConstant::PENDING,
                'lastChange' => $this->setLastChange()
            ];
        }

        return $usersNode;
    }
}
