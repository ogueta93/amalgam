<?php
// src/Service/WsServerApp/Exception/WsException.php

namespace App\Service\WsServerApp\Exception;

use App\Base\Exception\DataException;

class WsException extends DataException
{
    /** Constants */
    const MSG_INVALID_CREDENTIALS = 'invalidCredentials';
    const MSG_NOT_VALID_TOKEN = 'noValidToken';
    const MSG_ACTION_NOT_EXISTS = 'actionDoesNotExists';
    const MSG_NOT_VALID_DATA_GENERIC = 'noValidDataGeneric';
    const MSG_NOT_VALID_DATA_ON_WS_SERVICE = 'notValidDataOnWsService';
    const MSG_NOT_VALID_RIGHTS = 'notValidRights';

    const WS_AMALGAN_PHASE_FATAL_ERROR = 1;
}
