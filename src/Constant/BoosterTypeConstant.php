<?php
// src/Constant/BoosterTypeConstant.php
namespace App\Constant;

use App\Base\Constant\AbstractConstant;

class BoosterTypeConstant extends AbstractConstant
{
    protected $database = true;

    const NORMAL = 1;
    const SPECIAL = 2;
    const LEGENDARY = 3;

    /**
     * Gets the boosters's ids that they are row booster
     *
     * @return array
     */
    public static function getWinRowBoosters(): array
    {
        return [
            self::SPECIAL, self::LEGENDARY
        ];
    }
}
