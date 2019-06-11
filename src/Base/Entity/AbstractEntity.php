<?php
// src/Base/Entity/AbstractEntity.php
namespace App\Base\Entity;

use Symfony\Contracts\Translation\TranslatorInterface;

abstract class AbstractEntity
{
    /**
     * Gets the entity data in array
     *
     * @param TranslatorInterface $translator
     * @return array
     */
    abstract public function toArray(TranslatorInterface $translator);

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
