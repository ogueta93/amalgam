<?php
// src/Business/Shop/Boosters/BoosterObject.php
namespace App\Business\Shop\Boosters;

use App\Business\DailyReward\DailyRewardBusiness;
use App\Business\Shop\Boosters\WinRowBoosterObject;
use App\Business\Shop\Traits\ShopUtilsTrait;
use App\Constant\BoosterTypeConstant;
use App\Constant\CardTypeConstant;
use App\Entity\BoosterType;
use App\Entity\Card;
use App\Entity\CardType;
use App\Entity\User;
use App\Entity\UserBooster;
use App\Entity\UserCard;
use App\Service\WsServerApp\Exception\WsException;
use Doctrine\ORM\EntityManagerInterface;

class BoosterObject
{
    use ShopUtilsTrait;

    const DEFAULT_BOOSTER_CARDS_COST = 6;

    const CARDS_BY_BOOSTER = 5;
    const CARD_COMMON_PROBABILITY = 93;
    const CARD_RARE_PROBABILITY = 5;
    const CARD_LEGENDARY_PROBABILITY = 2;

    protected $id;
    protected $cost;
    protected $commonMod = 0;
    protected $rareMod = 0;
    protected $legendaryMod = 0;

    /**
     * Returs the correct BoosterObjec by id
     *
     * @param int $id
     * @param EntityManagerInterface|null $em
     * @param User|null $user
     * @param DailyRewardBusiness $dailyRewardBusiness
     */
    public static function getBoosterById(int $id, ?EntityManagerInterface $em = null, User $user, DailyRewardBusiness $dailyRewardBusiness = null)
    {
        switch ($id) {
            case BoosterTypeConstant::SPECIAL:
            case BoosterTypeConstant::LEGENDARY:
                return new WinRowBoosterObject($id, $em, $user, $dailyRewardBusiness);
                break;
            default:
                return new self($id, $em, $user);
                break;
        }
    }

    /**
     * Default constructor method
     *
     * @param int $id
     * @param EntityManagerInterface|null $em
     * @param User|null $user
     */
    public function __construct(int $id, ?EntityManagerInterface $em = null, User $user)
    {
        $this->id = $id;
        $this->em = $em;
        $this->user = $user;
        $this->cost = self::DEFAULT_BOOSTER_CARDS_COST;
    }

    /**
     * Gets the cost of the booster
     *
     * @return int
     */
    public function getCost(): int
    {
        return $this->cost;
    }

    /**
     * Returs if is availble to purchase by special conditions
     *
     * @return bool
     */
    public function isAvailableToPurchase(): bool
    {
        return true;
    }

    /**
     * Buys a booster and assign it to a user
     *
     * @param int $quantity
     * @param array $userCards
     * @return void
     *
     * @throws WsException
     */
    public function buy(int $quantity, array &$userCards)
    {
        for ($i = 0; $i < $quantity; $i++) {

            for ($e = 0; $e < $this->cost; $e++) {
                if (!isset($userCards[$e])) {
                    $this->throwShopError(WsException::MSG_INSUFFICIENT_FUNDS_ERROR);
                    break;
                }

                $this->em->remove($userCards[$e]);
                unset($userCards[$e]);
            }
            $userCards = \array_values($userCards);
            $this->addBooster();
        }

        $this->afterBuy();
        $this->em->flush();
    }

    /**
     * Add booster to the user
     *
     * @param User|null $user
     * @param bool $autoFlush
     */
    public function addBooster($user = null, $autoFlush = false): void
    {
        $today = new \DateTime();
        $user = $user ?? $this->user;

        $boosterTypeEnt = $this->em->getRepository(BoosterType::class)->find($this->id);
        $userBooster = new UserBooster();
        $userBooster->setBoosterType($boosterTypeEnt);
        $userBooster->setUser($user);
        $userBooster->setCreatedAt($today);
        $userBooster->setUpdatedAt($today);
        $userBooster->setOpened(null);
        $userBooster->setDeletedAt(null);

        $this->em->persist($userBooster);
        if ($autoFlush) {
            $this->em->flush();
        }
    }

    /**
     * Opens the booster and asign the new cards to the user
     *
     * @return array
     */
    public function open(): array
    {
        $userCards = [];
        $noCommon = false;

        for ($i = 0; $i < self::CARDS_BY_BOOSTER; $i++) {
            $lastChance = $i < self::CARDS_BY_BOOSTER - 1 ? false : true;
            $randomType = \random_int(1, 100);
            $chooseType = null;

            foreach ($this->getCardTypeProbabilities($lastChance) as $key => $value) {
                if ($randomType <= $value) {
                    $chooseType = $key;
                    break;
                }
            }

            $cardTypeEnt = $this->em->getRepository(CardType::class)->find($chooseType);
            $cardsEnt = $this->em->getRepository(Card::class)->findBy(['type' => $cardTypeEnt]);

            $randomCard = \random_int(0, \count($cardsEnt) - 1);
            $cardEnt = $cardsEnt[$randomCard];
            $userCards[] = $this->addCardToUser($cardEnt);

            if (!$noCommon && $chooseType !== CardTypeConstant::COMMON) {
                $noCommon = true;
            } else if (!$noCommon && !$lastChance) {
                $this->modifyProbabilities();
            }
        }

        $data = \array_map(function ($userCard) {
            return \array_merge(['userCardId' => $userCard->getId()], $userCard->getIdCard()->toArray());
        }, $userCards);

        return $data;
    }

    /**
     * Adds the card to the user
     *
     * @return UserCard
     */
    public function addCardToUser(Card $card): UserCard
    {
        $today = new \DateTime();

        $userCard = new UserCard();
        $userCard->setIdUser($this->user);
        $userCard->setIdCard($card);
        $userCard->setCreatedAt($today);
        $userCard->setUpdatedAt($today);
        $userCard->setDeletedAt(null);

        $this->em->persist($userCard);
        $this->em->flush();

        return $userCard;
    }

    /**
     * Adds extra data to the booster array
     *
     * @return array
     */
    public function addExtraData(array $booster): array
    {
        $booster['cost'] = $this->cost;
        $booster = $this->addAvailability($booster);

        return $booster;
    }

    /**
     * Adds availability
     *
     * @return array
     */
    protected function addAvailability(array $booster): array
    {
        $booster['available'] = true;

        return $booster;
    }

    /**
     * Gets the card type Probabilities
     *
     * @param bool $lastChance
     * @return array
     */
    protected function getCardTypeProbabilities($lastChance = false): array
    {
        $probabilities = [
            CardTypeConstant::COMMON => self::CARD_COMMON_PROBABILITY + $this->commonMod,
            CardTypeConstant::RARE => self::CARD_RARE_PROBABILITY + $this->rareMod,
            CardTypeConstant::LEGENDARY => self::CARD_LEGENDARY_PROBABILITY + $this->legendaryMod
        ];

        if ($lastChance) {
            $probabilities = $this->getLastChanceProbabilities() ?? $probabilities;
        }

        $sum = 0;
        \array_walk($probabilities, function ($element) use (&$sum) {
            $sum += $element;
        });

        if ($sum !== 100) {
            $this->throwShopError(WsException::MSG_BOOSTER_CARD_PROBABILITIES_ERROR);
        }

        $max = 0;
        foreach ($probabilities as $key => $value) {
            $probabilities[$key] = $max += $value;
        }

        return $probabilities;
    }

    /**
     * Modify the card type probabilities
     *
     * @return void
     */
    protected function modifyProbabilities(): void
    {
        $this->commonMod -= 1;
        $this->rareMod += 1;
    }

    /**
     * Gets the last chande probabilities
     *
     * @return array|null
     */
    protected function getLastChanceProbabilities(): ?array
    {
        return null;
    }

    /**
     * Does more logic after buy a booster
     */
    protected function afterBuy(): void
    {}
}
