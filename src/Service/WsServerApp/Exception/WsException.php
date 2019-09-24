<?php
// src/Service/WsServerApp/Exception/WsException.php

namespace App\Service\WsServerApp\Exception;

use App\Base\Exception\DataException;

class WsException extends DataException
{
    /** Constants */
    const MSG_ACTION_NOT_EXISTS = 'actionDoesNotExists';

    const MSG_ABOVE_FUNDS_ERROR = 'aboveFundsError';

    const MSG_BOOSTER_CARD_PROBABILITIES_ERROR = "msgBoosterCardProbabilities";

    const MSG_CAN_NOT_BUY_MORE_THAN_ONE_ROW_BOOSTER_ERROR = 'canNotBuyMoreThanOneRowBoosterError';

    const MSG_DAILY_REWARD_HAS_NOT_EXPIRED_ERROR = 'dailyRewardHasNotExpiredError';

    const MSG_GENERIC_SECURITY_ERROR = 'genericSecurityError';

    const MSG_INVALID_CREDENTIALS = 'invalidCredentials';
    const MSG_INSUFFICIENT_FUNDS_ERROR = 'insufficientFundsError';

    const MSG_NOT_VALID_TOKEN = 'noValidToken';
    const MSG_NOT_VALID_DATA_GENERIC = 'noValidDataGeneric';
    const MSG_NOT_VALID_DATA_ON_WS_SERVICE = 'notValidDataOnWsService';
    const MSG_NOT_VALID_RIGHTS = 'notValidRights';

    const MSG_THE_BOOSTER_CAN_NOT_OPEN_ERROR = 'theBoosterCanNotOpenError';

    const MSG_WIN_ROW_COUNT_IS_NOT_ENOUGH_ERROR = 'winRowCountIsNotEnoughError';

    const WS_AMALGAN_PHASE_FATAL_ERROR = 1;
    const WS_AMALGAN_PHASE_COMMOM_ERROR = 2;
    const WS_AMALGAN_PHASE_BATTLE_ERROR = 3;
    const WS_AMALGAN_PHASE_REWARD_ERROR = 4;
    const WS_AMALGAN_PHASE_SHOP_ERROR = 5;
}
