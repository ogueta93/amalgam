<?php
// src/Service/WsServerApp/Exception/WsException.php

namespace App\Service\WsServerApp\Exception;

use App\Base\Exception\DataException;

class WsException extends DataException
{
    /** Constants */
    const WS_AMALGAN_PHASE_FATAL_ERROR = 1;
}
