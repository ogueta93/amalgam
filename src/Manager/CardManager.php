<?php
// src/Manager/CardManager.php
namespace App\Manager;

use App\Entity\Card;
use App\Entity\UserCard;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;

class CardManager
{
    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Object Properties */
    protected $validFilters = ['cardName', 'cardType'];

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Gets UserCards by filters
     *
     * @param int $userId
     * @param array filters
     *
     * @return array $data
     */
    public function getUserCardsByFilters(int $userId, array $filters): array
    {
        $cleanFilters = $this->cleanFilters($filters);

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('uc')
            ->from(UserCard::class, 'uc')
            ->join('uc.idCard', 'c', 'WITH', 'c.name like :nameCard')
            ->where('uc.idUser = :userId')
            ->setParameters([
                'userId' => $userId,
                'nameCard' => '%' . $cleanFilters['cardName'] . '%'
            ]);

        if ($cleanFilters['cardType'] > 0) {
            $qb
                ->join('c.type', 'ct', 'WITH', 'ct.id = :cardType')
                ->setParameter('cardType', $cleanFilters['cardType']);
        }

        $cards = $qb->getQuery()->getResult();

        $data = \array_map(function ($card) {
            return \array_merge(['userCardId' => $card->getId()], $card->getIdCard()->toArray());
        }, $cards);

        return $data;
    }

    /**
     * Gets cards by filters
     *
     * @param int $userId
     * @param array filters
     *
     * @return array $data
     */
    public function getCardsByFilters(array $filters): array
    {
        $cleanFilters = $this->cleanFilters($filters);

        $qb = $this->em->createQueryBuilder();
        $qb
            ->select('c')
            ->from(Card::class, 'c')
            ->where('c.name like :nameCard')
            ->setParameters([
                'nameCard' => '%' . $cleanFilters['cardName'] . '%'
            ]);

        if ($cleanFilters['cardType'] > 0) {
            $qb
                ->join('c.type', 'ct', 'WITH', 'ct.id = :cardType')
                ->setParameter('cardType', $cleanFilters['cardType']);
        }

        $cards = $qb->getQuery()->getResult();

        $data = \array_map(function ($card) {
            return $card->toArray();
        }, $cards);

        return $data;
    }

    /**
     * Cleans filters
     *
     * @param array $filters
     * @return array $cleanFilters
     */
    protected function cleanFilters(array $filters): array
    {
        $validFilters = $this->validFilters;

        $cleanFilters = \array_filter($filters, function ($value, $key) use ($validFilters) {
            return \in_array($key, $validFilters);
        }, ARRAY_FILTER_USE_BOTH);

        return $cleanFilters;
    }
}
