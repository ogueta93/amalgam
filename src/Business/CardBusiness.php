<?php
// src/Business/CardBusiness.php
namespace App\Business;

use App\Entity\Card;
use App\Entity\UserCard;
use App\Manager\CardManager;
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
     * @param array $content
     * @return void
     */
    public function getUserCards($content)
    {
        $user = $this->getLoggedUser();

        $cardManager = $this->container->get(CardManager::class);
        $data = $cardManager->getByFilters($user->getId(), $content);

        sleep(2);
        $this->addWsResponseData($data);
    }

    /**
     * Add cart to loggedUser
     *
     * @category test
     * @return void
     */
    public function addCard($content)
    {
        $msg = null;
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

                $msg = sprintf('The card with name %s has been added to the user %s', $card->getName(), $user->getEmail());
            }
        }

        $this->addWsResponseData([$msg]);
    }
}
