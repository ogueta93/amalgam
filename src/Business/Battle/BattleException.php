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
    const BATTLE_PHASE = 'battle';

    const NOT_VALID_PARAMS = 1;
    const GENERIC_SECURITY_ERROR = 2;
    const GENERIC_NOT_FOUND_ELEMENT = 3;
    const ACTION_IS_ALREADY_PERFORMED = 4;

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
                return [Response::HTTP_NOT_ACCEPTABLE, $errorMessage, self::BATTLE_PHASE];
            case self::GENERIC_SECURITY_ERROR:
                return [Response::HTTP_FORBIDDEN, $errorMessage, self::BATTLE_PHASE];
            default:
                return [Response::HTTP_BAD_REQUEST, $errorMessage, self::BATTLE_PHASE];
        }
    }
}
