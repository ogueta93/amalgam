<?php
// src/Business/Shop\BoosterBusiness.php
namespace App\Business\Shop;

use App\Business\DailyReward\DailyRewardBusiness;
use App\Business\Shop\BoosterBusiness;
use App\Business\Shop\Boosters\BoosterObject;
use App\Business\Shop\Traits\ShopUtilsTrait;
use App\Constant\BoosterTypeConstant;
use App\Constant\DailyRewardTypeConstant;
use App\Entity\BoosterType;
use App\Entity\UserBooster;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class BoosterBusiness
{
    use WsUtilsTrait;
    use ShopUtilsTrait;

    protected $em;
    protected $dailyRewardBusiness;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
        $this->dailyRewardBusiness = $this->container->get(DailyRewardBusiness::class);
    }

    /**
     * Gets the available public boosters to purchase
     *
     * @return array
     */
    public function getAvailablePublicBoostersToPurchase(): array
    {
        $boosterTypeEnt = $this->em->getRepository(BoosterType::class)->findAll();

        $boosters = \array_map(function ($element) {
            return $element->toArray();
        }, $boosterTypeEnt);

        foreach ($boosters as $key => &$booster) {
            $boosterObj = $this->getBoosterObjById($booster['id']);
            $booster = $boosterObj->addExtraData($booster);
        }

        return $boosters;
    }

    /**
     * Gets the total amount of cards to pay
     *
     * @param array $purchase
     * @return int
     *
     * @throws WsException
     */
    public function getTotalToPayByPurchase($purchase)
    {
        $total = 0;

        $rowBoostersId = BoosterTypeConstant::getWinRowBoosters();
        $rowBooster = \array_filter($purchase, function ($element) use ($rowBoostersId) {
            return \in_array($element['id'], $rowBoostersId);
        });

        if (\count($rowBooster) > 1) {
            $this->throwShopError(WsException::MSG_CAN_NOT_BUY_MORE_THAN_ONE_ROW_BOOSTER_ERROR);
        }

        foreach ($purchase as $key => $element) {
            $booster = $this->getBoosterObjById($element['id']);
            $total += $booster->getCost() * $element['quantity'];
        }

        return $total;
    }

    /**
     * Gets the user's boosters
     *
     * @return array
     */
    public function getUserBoosters(): array
    {
        $userBoosterEnt = $this->em->getRepository(UserBooster::class)->findBy(['user' => $this->getLoggedUser(), 'opened' => null]);

        $userBoosters = \array_map(function ($element) {
            return $element->toArray();
        }, $userBoosterEnt);

        return $userBoosters;
    }

    /**
     * Makes the purchases
     *
     * @param array $purchases
     * @param array $userCards
     * @return bool
     *
     * @throws WsException
     */
    public function pay(array $purchases, array $userCards): bool
    {
        foreach ($purchases as $key => $purchase) {
            $booster = $this->getBoosterObjById($purchase['id']);
            if ($booster->isAvailableToPurchase()) {
                $booster->buy($purchase['quantity'], $userCards);
            }
        }

        return true;
    }

    /**
     * Opens the booster by its id
     *
     * @param int $id
     * @return array
     *
     * @throws WsException
     */
    public function openUserBooster(int $id): array
    {
        $boosterTypeEnt = $this->em->getRepository(BoosterType::class)->find($id);
        $userBoosterEnt = $this->em->getRepository(UserBooster::class)->findOneBy(['boosterType' => $boosterTypeEnt, 'user' => $this->getLoggedUser(), 'opened' => null]);
        if ($userBoosterEnt === null) {
            $this->throwShopError(WsException::MSG_THE_BOOSTER_CAN_NOT_OPEN_ERROR);
        }

        $boosterObj = $this->getBoosterObjById($id);
        $userCards = $boosterObj->open();

        $today = new \DateTime();
        $userBoosterEnt->setOpened($today);
        $this->em->flush();

        return $userCards;
    }

    /**
     * Returns if the user can get a free daily basic booster
     *
     * @return bool
     */
    public function checkUserDailyBooster(): bool
    {
        $dailyReward = $this->dailyRewardBusiness->getDailyRewardByType(DailyRewardTypeConstant::DAILY_BOOSTER);
        return $dailyReward->canBeClaimed();
    }

    /**
     * Get the daily free boster to the user
     *
     * @return bool
     */
    public function getDailyFreeBooster(): bool
    {
        $dailyReward = $this->dailyRewardBusiness->getDailyRewardByType(DailyRewardTypeConstant::DAILY_BOOSTER);
        if (!$dailyReward->canBeClaimed()) {
            $this->throwShopError(WsException::MSG_DAILY_REWARD_HAS_NOT_EXPIRED_ERROR);
        }
        $dailyReward->setClaimed();

        $boosterObj = $this->getBoosterObjById(BoosterTypeConstant::NORMAL);
        $boosterObj->addBooster(null, true);

        return true;
    }

    /**
     * Gets a booster object by id
     *
     * @param int $id
     * @return BoosterObject|null
     *
     * @throws WsException
     */
    public function getBoosterObjById(int $id): ?BoosterObject
    {
        return BoosterObject::getBoosterById($id, $this->em, $this->getLoggedUser(), $this->dailyRewardBusiness);
    }
}
