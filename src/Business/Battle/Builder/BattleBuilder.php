<?php
// src/Business/Battle/Builder/BattleBuilder.php
namespace App\Business\Battle\Builder;

use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleBuilder
{
    use WsUtilsTrait;

    /** Constants */
    const NODE_PROGRESS = 'progress';
    const NODE_PROGRESS_MAIN = 'main';
    const NODE_MAIN_CARDS_SELECTION = 'cardsSelection';
    const NODE_MAIN_PHASE = 'phase';

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

        return $battleData;
    }
}
