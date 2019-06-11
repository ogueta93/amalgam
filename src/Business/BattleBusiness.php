<?php
// src/Business/BattleBusiness.php
namespace App\Business;

use App\Business\Battle\Logic\NewBattleLogic;
use App\Business\Battle\Notification\BattleNotification;
use App\Entity\Battle;
use App\Service\Cache;
use App\Service\Cache\CacheType;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

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
        $this->battleId = (int) $data['battleId'];
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
        $NewBattleLogic = $this->container->get(NewBattleLogic::class);
        $NewBattleLogic->setParams($data, []);

        $this->data = $NewBattleLogic->process();
        $this->battleId = (int) $this->data['id'];

        $this->save();

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
     * Save battle on database and cache
     *
     * @return void
     */
    protected function save()
    {
        $data = \json_encode($this->data);

        if ($this->battleId) {
            $this->battleEnt = $this->battleEnt ?? $this->em->getRepository(Battle::class)->find($this->battleId);
            $this->battleEnt->setData($data);

            $this->cache->set(
                sprintf(CacheType::BATTLE, $this->battleId),
                $data,
                (new \DateTime('tomorrow'))->getTimestamp()
            );

            $this->em->flush();
        }
    }

    /**
     * Gets battle data by the quickets way
     *
     * @return array
     */
    protected function quickBattleData()
    {
        $data = $this->cache->get(sprintf(CacheType::BATTLE, (int) $this->battleId));

        if (!$data) {
            $battleEnt = $this->em->getRepository(Battle::class)->find((int) $this->battleId);
            if ($battleEnt) {
                $data = $battleEnt->getData();
            }
        }

        return \json_decode($data, true);
    }
}
