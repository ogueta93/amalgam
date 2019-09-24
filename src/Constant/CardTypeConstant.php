<?php
// src/Constant/CardTypeConstant.php
namespace App\Constant;

use App\Base\Constant\AbstractConstant;

class CardTypeConstant extends AbstractConstant
{
    const COMMON = 1;
    const RARE = 2;
    const LEGENDARY = 3;
    const UNIQUE = 4;
}
