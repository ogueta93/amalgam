<?php
// src/Business/ListBattleBusiness.php
namespace App\Business;

use App\Business\Battle\BattleException;
use App\Business\Battle\Constant\BattleStatusConstant;
use App\Business\Battle\Logic\AcceptNewBattleLogic;
use App\Business\Battle\Notification\BattleNotification;
use App\Entity\Battle;
use App\Entity\UserBattle;
use App\Manager\ListBattleManager;
use App\Service\Cache;
use App\Service\Cache\CacheType;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class ListBattleBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;
    protected $cache;

    /** Properties */

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->battleException = new BattleException();
        $this->cache = $this->container->get(Cache::class)->getClient();
    }

    /**
     * Gets user list battle
     *
     * @param array $content => @param int type
     * @return void
     */
    public function getUserBattleList($content)
    {
        $user = $this->getLoggedUser();

        $listBattleManager = $this->container->get(ListBattleManager::class);
        $data = $listBattleManager->getActiveListByFilters($user->getId(), $content);

        $this->addWsResponseData($data);
    }

    /**
     * Refuses a batttle that it not started yet
     *
     * @param array $content => @param int battleId
     * @return void
     */
    public function refuseBattle($content)
    {
        $user = $this->getLoggedUser();
        $rivalId = null;

        $battleEnt = $this->em->getRepository(Battle::class)->find($content['battleId']);
        if (!$battleEnt) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        if ($battleEnt->getBattleStatus()->getId() !== BattleStatusConstant::PENDING) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $userBattle = $this->em->getRepository(UserBattle::class)->findBy(['user' => $user, 'battle' => $battleEnt]);
        if (!$userBattle) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $battleRelations = $this->em->getRepository(UserBattle::class)->findBy(['battle' => $battleEnt]);
        foreach ($battleRelations as $battleRelation) {
            $this->em->remove($battleRelation);

            if ($battleRelation->getUser()->getId() !== $user->getId()) {
                $rivalId = $battleRelation->getUser()->getId();
            }
        }

        $this->em->remove($battleEnt);
        $this->em->flush();

        $this->addWsResponseData(['id' => $content['battleId']], [['id' => $rivalId]]);
    }

    /**
     * Accepts a batttle that it not started yet
     *
     * @param array $content => @param int battleId
     * @return void
     */
    public function acceptBattle($content)
    {
        $NewBattleLogic = $this->container->get(AcceptNewBattleLogic::class);
        $NewBattleLogic->setParams($content, []);
        $data = $NewBattleLogic->process();

        $battleEnt = $this->em->getRepository(Battle::class)->find($content['battleId']);
        $battleEnt->setData(\json_encode($data));
        $this->em->flush();

        $this->cache->set(
            sprintf(CacheType::BATTLE, $battleEnt->getId()),
            \json_encode($data),
            (new \DateTime('tomorrow'))->getTimestamp()
        );

        $clients = [];
        foreach ($data['users'] as $key => $user) {
            if ($this->getLoggedUser()->getId() != $user['user']['id']) {
                $clients[] = ['id' => $user['user']['id']];
            }
        }

        $this->addWsResponseData($data, $clients);

        $notificationData = [
            'id' => $data['id'],
            'acceptedBy' => $this->getLoggedUser()->toArray(),
            'type' => $data['type']
        ];
        $battleNotification = new BattleNotification($notificationData, $clients, BattleNotification::ACCEPT_BATTLE);
        $battleNotification->notify();
    }
}
