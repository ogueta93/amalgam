<?php
// src/Business/Battle/Builder/NewBattleBuilder.php
namespace App\Business\Battle\Builder;

use App\Business\Battle\AbstractBattleBuilder;
use App\Business\Battle\Constant\BattleUserStatusConstant;

class NewBattleBuilder extends AbstractBattleBuilder
{
    /** Constants */
    const FORMAT_DATE = 'Y-m-d H:i:s';

    /**
     * Makes the array data from $inputData
     *
     * @return void
     */
    protected function makeIt()
    {
        $this->data = [
            'id' => $this->inputData['battle']['id'],
            'type' => $this->inputData['battle']['type'],
            'status' => $this->inputData['battle']['status'],
            'createdBy' => $this->inputData['User']->toArray(),
            'users' => $this->getUsersNode(),
            'lastChange' => $this->inputData['today']->format(self::FORMAT_DATE)
        ];
    }

    /**
     * Gets UsersNode
     *
     * @return array
     */
    protected function getUsersNode(): array
    {
        $usersNode = [];

        $mainUserId = $this->inputData['User']->getId();

        foreach ($this->inputData['userRelations'] as $key => $user) {
            $usersNode[] = [
                'user' => $user->toArray(),
                'statusId' => $user->getId() == $mainUserId ? BattleUserStatusConstant::ACCEPTED : BattleUserStatusConstant::PENDING,
                'lastChange' => $this->inputData['today']->format(self::FORMAT_DATE)
            ];
        }

        return $usersNode;
    }
}
