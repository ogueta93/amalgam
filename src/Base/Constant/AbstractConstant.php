<?php
// src/Base/Constant/AbstractConstant.php
namespace App\Base\Constant;

use App\Base\Exception\DataException;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractConstant
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
