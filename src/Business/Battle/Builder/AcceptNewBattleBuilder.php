<?php
// src/Business/Battle/Builder/NewBattleBuilder.php
namespace App\Business\Battle\Builder;

use App\Business\Battle\AbstractBattleBuilder;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;

class AcceptNewBattleBuilder extends AbstractBattleBuilder
{
    /** Constants */

    /**
     * Makes the array data from $inputData
     *
     * @return void
     */
    protected function makeIt()
    {
        $this->data = $this->battleData;

        $this->data['progress'] = $this->getMainProgress();
    }

    /**
     * Sets main battle progress
     *
     * @return array
     */
    protected function getMainProgress()
    {
        return [
            'main' => [
                'phase' => BattleMainProgressPhaseConstant::CARD_SELECTION_PHASE
            ]
        ];
    }
}
