<?php
// src/Business/Battle/AbstractBattleLogic.php
namespace App\Business\Battle;

use App\Business\Battle;
use App\Business\Battle\BattleException;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

abstract class AbstractBattleLogic
{
    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Properties */
    protected $data = [];
    protected $inputData = [];
    protected $battleData = [];

    /** Object Properties */
    protected $battleException;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->battleException = new BattleException();
    }

    /**
     * Proves if the inputData is correct to process
     *
     * @throws BattleException
     */
    abstract protected function proveIt();

    /**
     * Does logic work
     *
     * @return void
     */
    abstract protected function doIt();

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
        $this->data = [];
        $this->inputData = $inputData;
        $this->battleData = $battleData;
    }

    /**
     * Process all the logic and return the battleData
     *
     * @return array
     */
    public function process(): array
    {
        $this->proveIt();
        $this->doIt();
        
        return $this->getBattleData();
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

    /**
     * Gets battleData
     *
     * @return array
     */
    public function getBattleData(): array
    {
        return $this->battleData;
    }
}
