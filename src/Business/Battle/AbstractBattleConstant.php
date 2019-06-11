<?php
// src/Business/Battle/AbstractBattleConstant.php
namespace App\Business\Battle;

use App\Base\Exception\DataException;
use App\Business\Battle;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractBattleConstant
{
    /** Properties */
    protected $database = false;

    /**
     * Gets class constants
     *
     * @return array
     */
    public function getConstants()
    {
        if ($this->database) {
            throw new DataException(Response::HTTP_FORBIDDEN, [
                'message' => 'getConstantsOnlyVirtual',
                'phase' => 'main'
            ]);
        }

        $reflectionClass = new ReflectionClass($this);
        return $reflectionClass->getConstants();
    }
}
