<?php
// src/Business/BattleBusiness.php
namespace App\Business;

use App\Business\Battle\BattleException;
use App\Business\Battle\Logic\NewBattleLogic;
use App\Business\Battle\Logic\CardsSelectionLogic;
use App\Business\Battle\Notification\BattleNotification;
use App\Entity\Battle;
use App\Entity\UserBattle;
use App\Service\Cache;
use App\Service\Cache\CacheType;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;
use App\Business\Battle\Constant\BattleMainProgressPhaseConstant;

class BattleBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $battleEnt;
    protected $cache;

    /** Properties */
    protected $battleId;
    protected $data;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->cache = $this->container->get(Cache::class)->getClient();
    }

    /**
     * Resets fundamental properties
     *
     * @return void
     */
    public function reset()
    {
        $this->data = null;
        $this->battleId = null;
        $this->battleEnt = null;
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

        $clients = [];
        foreach ($this->data['users'] as $key => $user) {
            if ($this->getLoggedUser()->getId() != $user['user']['id']) {
                $clients[] = ['id' => $user['user']['id']];
            }
        }

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
            $clients = [];
            foreach ($this->data['users'] as $key => $user) {
                if ($this->getLoggedUser()->getId() != $user['user']['id']) {
                    $clients[] = ['id' => $user['user']['id']];
                }
            }
        }

        $this->addWsResponseData($this->data, $clients);
    }

    /**
     * Save battle on database and cache
     *
     * @param bool $cache
     * @return void
     */
    protected function save($cache = true)
    {
        $data = \json_encode($this->data);

        if ($this->battleId) {
            $this->battleEnt = $this->battleEnt ?? $this->em->getRepository(Battle::class)->find($this->battleId);

            if ($this->battleEnt) {
                $this->battleEnt->setData($data);

                if ($cache) {
                    $this->cache->set(
                        sprintf(CacheType::BATTLE, $this->battleId),
                        $data,
                        (new \DateTime('tomorrow'))->getTimestamp()
                    );
                }

                $this->em->flush();
            }
        }
    }

    /**
     * Gets battle data by the quickets way
     *
     * @return array
     */
    protected function quickBattleData()
    {
        $battleEnt = $this->em->getRepository(Battle::class)->find((int) $this->battleId);
        if (!$battleEnt) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        $userBattle = $this->em->getRepository(UserBattle::class)->findBy(['user' => $this->getLoggedUser(), 'battle' => $battleEnt]);
        if (!$userBattle) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $this->battleEnt = $battleEnt;

        $data = $this->cache->get(sprintf(CacheType::BATTLE, (int) $this->battleId));
        if (!$data) {
            $data = $battleEnt->getData();
        }

        return \json_decode($data, true);
    }
}
