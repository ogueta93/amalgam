<?php
// src/Business/Battle/AbstractBattleBuilder.php
namespace App\Business\Battle;

use App\Business\Battle;

abstract class AbstractBattleBuilder
{
    /** Properties */
    protected $data = [];
    protected $inputData = [];
    protected $battleData = [];

    /**
     * Makes the array data from $inputData
     *
     * @return void
     */
    abstract protected function makeIt();

    /**
     * Sets inputData and battleData properties
     *
     * @param array $inputData
     * @param array $battleData
     *
     * @return void
     */
    public function setParams(array $inputData, array $battleData)
    {
        $this->inputData = $inputData;
        $this->battleData = $battleData;
        $this->data = [];
    }

    /**
     * Process all inputData and returns the builded data
     *
     * @return array
     */
    public function work(): array
    {
        $this->makeIt();

        return $this->getData();
    }

    /**
     * Gets data
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }
}
