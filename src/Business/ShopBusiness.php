<?php
// src/Business/ShopBusiness.php
namespace App\Business;

use App\Business\Shop\BoosterBusiness;
use App\Business\Shop\Traits\ShopUtilsTrait;
use App\Entitu\User;
use App\Entity\UserCard;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class ShopBusiness
{
    use WsUtilsTrait;
    use ShopUtilsTrait;

    protected $boosterBusiness;

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;

        $this->boosterBusiness = $this->container->get(BoosterBusiness::class);
    }

    /**
     * Gets the available boosters to purchase
     *
     * @param array $data
     * @return void
     *
     * @throws WsException
     */
    public function getAvailableBoosters($data): void
    {
        $this->addWsResponseData($this->boosterBusiness->getAvailablePublicBoostersToPurchase());
    }

    /**
     * Sets boosters for the user if him meets all the requirements
     *
     * @param array $data =>
     *  @param array purchases => @param int id, @param int quantity
     *  @param array userCardIdsToPay
     *
     * @return void
     * @throws WsException
     */
    public function purchaseBoosters($data): void
    {
        $purchases = $data['purchases'] ?? null;
        $userCardIdsToPay = $data['userCardIdsToPay'] ?? null;

        if (!$purchases || !$userCardIdsToPay) {
            $this->throwShopError(WsException::MSG_NOT_VALID_DATA_GENERIC);
        }

        $user = $this->getLoggedUser();
        $userCardsEnt = $this->em->getRepository(UserCard::class)->findBy(['id' => $userCardIdsToPay, 'idUser' => $user, 'idBattle' => null]);

        if (\count($userCardsEnt) !== \count($userCardIdsToPay)) {
            $this->throwShopError(WsException::MSG_GENERIC_SECURITY_ERROR);
        }

        $cardsToPayTotal = $this->boosterBusiness->getTotalToPayByPurchase($purchases);

        if (\count($userCardsEnt) < $cardsToPayTotal) {
            $this->throwShopError(WsException::MSG_INSUFFICIENT_FUNDS_ERROR);
        } else if (\count($userCardsEnt) > $cardsToPayTotal) {
            $this->throwShopError(WsException::MSG_ABOVE_FUNDS_ERROR);
        }

        $this->addWsResponseData($this->boosterBusiness->pay($purchases, $userCardsEnt));
    }

    /**
     * Gets users's boosters
     *
     * @return void
     */
    public function getUserBoosters(): void
    {
        $this->addWsResponseData($this->boosterBusiness->getUserBoosters());
    }

    /**
     * Opens a user's booster
     *
     * @param array $data => @param int id
     *
     * @return void
     * @throws WsException
     */
    public function openUserBooster($data): void
    {
        $id = $data['id'] ?? null;
        if (!$id) {
            $this->throwShopError(WsException::MSG_NOT_VALID_DATA_GENERIC);
        }

        $this->addWsResponseData($this->boosterBusiness->openUserBooster($id));
    }

    /**
     * Checks if the user daily booster is available
     *
     * @return void
     */
    public function checkUserDailyBooster(): void
    {
        $this->addWsResponseData($this->boosterBusiness->checkUserDailyBooster());
    }

    /**
     * Gets the daily free Booster
     *
     * @return void
     */
    public function getDailyFreeBooster(): void
    {
        $this->addWsResponseData($this->boosterBusiness->getDailyFreeBooster());
    }
}
