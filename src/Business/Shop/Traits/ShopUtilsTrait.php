<?php
// src/Business/Shop/Traits/ShopUtilsTrait.php
namespace App\Business\Shop\Traits;

use App\Service\WsServerApp\Exception\WsException;
use Symfony\Component\HttpFoundation\Response;

trait ShopUtilsTrait
{

    /**
     * Throws a shop error
     *
     * @param string $msg
     *
     * @throws WsException
     */
    protected function throwShopError(string $msg)
    {
        $code = null;
        switch ($msg) {
            case WsException::MSG_GENERIC_SECURITY_ERROR:
            case WsException::MSG_INSUFFICIENT_FUNDS_ERROR:
            case WsException::MSG_ABOVE_FUNDS_ERROR:
            case wsException::MSG_CAN_NOT_BUY_MORE_THAN_ONE_ROW_BOOSTER_ERROR:
                $code = Response::HTTP_FORBIDDEN;
                break;
            default:
                $code = Response::HTTP_BAD_REQUEST;
                break;
        }

        throw new WsException($code, [
            'message' => $msg,
            'phase' => WsException::WS_AMALGAN_PHASE_SHOP_ERROR
        ]);
    }
}
