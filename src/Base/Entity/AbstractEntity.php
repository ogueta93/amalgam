<?php
// src/Base/Entity/AbstractEntity.php
namespace App\Base\Entity;

abstract class AbstractEntity
{
    /**
     * Gets the entity data in array
     *
     * @return array
     */
    abstract public function toArray();

    /**
     * Gets a transformDate with format
     *
     * @param \DateTime|null $date
     * @param string $format
     *
     * @return string
     */
    protected function transformDate($date, $format = "Y-m-d H:i:s")
    {
        if (!$date) {
            return null;
        }

        return $date->format($format);
    }
}
