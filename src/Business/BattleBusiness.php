<?php
// src/Business/BattleBusiness.php
namespace App\Business;

use App\Business\Battle\BattleException;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;
use App\Business\Battle\Logic\BattleMovementLogic;
use App\Business\Battle\Logic\CardsSelectionLogic;
use App\Business\Battle\Logic\NewBattleLogic;
use App\Business\Battle\Logic\showCoinThrowLogic;
use App\Business\Battle\Notification\BattleNotification;
use App\Business\Battle\Traits\BattleUtilsTrait;
use App\Entity\Battle;
use App\Service\Cache;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class BattleBusiness
{
    use BattleUtilsTrait;
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $cache;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->battleException = new BattleException();
        $this->cache = $this->container->get(Cache::class)->getClient();
    }

    /**
     * Gets battleId
     *
     * @return int battleId
     */
    public function getBattleId()
    {
        return $this->battleId;
    }

    /**
     * Finds battle in cache and database
     *
     * @param array $data => @param int battleId
     * @return void
     */
    public function findBattle($data)
    {
        $this->battleId = (int) $data['battleId'] ?? null;
        $this->data = $this->quickBattleData();

        $this->save();
        $this->addWsResponseData($this->data);
    }

    /**
     * Gets battle data
     *
     * @return array $data
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Creates a new battle
     *
     * @param array $data => @param array users, @param int type
     *
     * @return void
     */
    public function newBattle(array $data)
    {
        $newBattleLogic = $this->container->get(NewBattleLogic::class);
        $newBattleLogic->setParams($data, []);

        $this->data = $newBattleLogic->process();
        $this->battleId = (int) $this->data['id'];

        $this->save(false);

        $clients = $this->getRivalsFromData();
        $this->addWsResponseData($this->data, $clients);

        $battleNotification = new BattleNotification($this->data, $clients, BattleNotification::NEW_BATTLE);
        $battleNotification->notify();
    }

    /**
     * Set cards for a battle
     *
     * @param array $data => @param int battleId, @param array cardsSelected
     *
     * @return void
     */
    public function setCardsSelection(array $data)
    {
        $this->battleId = (int) $data['battleId'] ?? null;
        $this->data = $this->quickBattleData();

        $cardsSelectionLogic = $this->container->get(CardsSelectionLogic::class);
        $cardsSelectionLogic->setParams($data, $this->data);

        $this->data = $cardsSelectionLogic->process();
        $this->save();

        $clients = null;
        if ($this->data['progress']['main']['phase'] === BattleMainProgressPhaseConstant::COIN_THROW_PHASE) {
            $clients = $this->getRivalsFromData();
        }

        $this->addWsResponseData($this->data, $clients);
    }

    /**
     * Set cards for a battle
     *
     * @param array $data => @param int battleId
     *
     * @return void
     */
    public function showThrowAnnouncement($data)
    {
        $this->battleId = (int) $data['battleId'] ?? null;
        $this->data = $this->quickBattleData();

        $showCoinThrowLogic = $this->container->get(showCoinThrowLogic::class);
        $showCoinThrowLogic->setParams($data, $this->data);

        $this->data = $showCoinThrowLogic->process();
        $this->save();

        $clients = null;
        if ($this->data['progress']['main']['phase'] === BattleMainProgressPhaseConstant::BATTLE_PHASE) {
            $clients = $this->getRivalsFromData();
        }

        $this->addWsResponseData($this->data, $clients);
    }

    /**
     * Does a battle movement
     *
     * @param array $data => @param int battleId, @param int userCardId, @param array coordinates
     *
     * @return void
     */
    public function battleMovement($data)
    {
        $this->battleId = (int) $data['battleId'] ?? null;
        $this->data = $this->quickBattleData();

        $battleMovementLogic = $this->container->get(BattleMovementLogic::class);
        $battleMovementLogic->setParams($data, $this->data);

        $this->data = $battleMovementLogic->process();
        $this->save();

        $this->addWsResponseData($this->data, $this->getRivalsFromData());
    }
}
