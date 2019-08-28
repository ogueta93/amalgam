<?php
// src/Business/Battle/BattleException.php
namespace App\Business\Battle;

use App\Base\Exception\DataException;
use App\Service\WsServerApp\Exception\WsException;
use Symfony\Component\HttpFoundation\Response;

class BattleException
{
    /** Constants */
    const BATTLE_KEY_WORK = 'battleError_%s';

    const NOT_VALID_PARAMS = 1;
    const GENERIC_SECURITY_ERROR = 2;
    const GENERIC_NOT_FOUND_ELEMENT = 3;
    const ACTION_IS_ALREADY_PERFORMED = 4;

    const REWARD_HAS_EXPIRED = 11;

    /**
     * Throws a DataException Error
     *
     * @param int $battleErrorCode
     * @throws DataException
     */
    public function throwError($battleErrorCode)
    {
        [$errorCode, $errorMessage, $phase] = $this->getErrorData($battleErrorCode);

        throw new WsException($errorCode, [
            'message' => $errorMessage,
            'phase' => $phase
        ]);
    }

    /**
     * Get error data by code
     *
     * @param int $battleErrorCode
     * @return array
     */
    protected function getErrorData($battleErrorCode): array
    {
        $errorMessage = sprintf(self::BATTLE_KEY_WORK, $battleErrorCode);

        switch ($battleErrorCode) {
            case self::NOT_VALID_PARAMS:
                return [Response::HTTP_NOT_ACCEPTABLE, $errorMessage, WsException::WS_AMALGAN_PHASE_BATTLE_ERROR];
            case self::GENERIC_SECURITY_ERROR:
                return [Response::HTTP_FORBIDDEN, $errorMessage, WsException::WS_AMALGAN_PHASE_BATTLE_ERROR];
            case self::REWARD_HAS_EXPIRED:
                return [Response::HTTP_FORBIDDEN, $errorMessage, WsException::WS_AMALGAN_PHASE_REWARD_ERROR];
            default:
                return [Response::HTTP_BAD_REQUEST, $errorMessage, WsException::WS_AMALGAN_PHASE_BATTLE_ERROR];
        }
    }
}
