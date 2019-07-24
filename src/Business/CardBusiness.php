<?php
// src/Business/CardBusiness.php
namespace App\Business;

use Symfony\Component\HttpFoundation\Response;
use App\Base\Constant\AppConstant;
use App\Entity\Card;
use App\Entity\UserCard;
use App\Manager\CardManager;
use App\Service\WsServerApp\Exception\WsException;
use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class CardBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Properties */

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets user cards
     *
     * @param array @param array filters
     * @return void
     */
    public function getUserCards(array $content)
    {
        $user = $this->getLoggedUser();

        $cardManager = $this->container->get(CardManager::class);
        $data = $cardManager->getUserCardsByFilters($user->getId(), $content);

        $this->addWsResponseData($data);
    }

    /**
     * Gets game cards
     *
     * @param array $content => @param array filters
     * @return void
     */
    public function getGameCards(array $content)
    {
        $cardManager = $this->container->get(CardManager::class);
        $data = $cardManager->getCardsByFilters($content);

        $this->addWsResponseData($data);
    }

    /**
     * Add cart to loggedUser
     *
     * @param array $content => @param int id
     * @return void
     */
    public function addCard(array $content)
    {
        if ($this->container->get('kernel')->getEnvironment() !== AppConstant::DEV_ENVIRONMENT) {
            throw new WsException(Response::HTTP_FORBIDDEN, [
                'message' => WsException::MSG_NOT_VALID_RIGHTS,
                'phase' => WsException::WS_AMALGAN_PHASE_FATAL_ERROR
            ]);
        }

        $cardId = $content['id'] ?? null;
        if ($cardId) {
            $user = $this->getLoggedUser();
            $card = $this->em->getRepository(Card::class)->find($cardId);

            if ($card) {
                $today = new \DateTime();

                $userCard = new UserCard();
                $userCard->setIdUser($user);
                $userCard->setIdCard($card);
                $userCard->setCreatedAt($today);
                $userCard->setUpdatedAt($today);
                $userCard->setDeletedAt(null);

                $this->em->persist($userCard);
                $this->em->flush();

                $this->addWsResponseData([true]);
            }
        }
    }
}
