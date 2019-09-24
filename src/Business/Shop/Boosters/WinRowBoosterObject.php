<?php
// src/Business/Shop/Boosters/WinRowBoosterObject.php
namespace App\Business\Shop\Boosters;

use App\Business\DailyReward\DailyRewardBusiness;
use App\Business\Shop\Boosters\BoosterObject;
use App\Business\Shop\Traits\ShopUtilsTrait;
use App\Constant\BoosterTypeConstant;
use App\Constant\CardTypeConstant;
use App\Constant\DailyRewardTypeConstant;
use App\Entity\User;
use App\Service\WsServerApp\Exception\WsException;
use Doctrine\ORM\EntityManagerInterface;

class WinRowBoosterObject extends BoosterObject
{
    use ShopUtilsTrait;

    const SPECIAL_WIN_ROW_COUNT = 3;
    const LEGENDARY_WIN_ROW_COUNT = 5;

    const CARD_COMMON_PROBABILITY = 93;
    const CARD_RARE_PROBABILITY = 5;
    const CARD_LEGENDARY_PROBABILITY = 2;

    const CARD_RARE_PROBABILITY_LAST_CHANCE = 90;
    const CARD_LEGENDARY_PROBABILITY_LAST_CHANCE = 10;

    protected $dailyRewardBusiness;

    /**
     * Default constructor method
     *
     * @param int $id
     * @param EntityManagerInterface|null $em
     * @param User|null $user
     * @param DailyRewardBusiness $dailyRewardBusiness
     */
    public function __construct(int $id, ?EntityManagerInterface $em = null, User $user, DailyRewardBusiness $dailyRewardBusiness)
    {
        parent::__construct($id, $em, $user);
        $this->dailyRewardBusiness = $dailyRewardBusiness;
    }

    /**
     * Gets the cost of the booster
     *
     * @param bool
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
        $dailyReward = $this->dailyRewardBusiness->getDailyRewardByType(DailyRewardTypeConstant::WIN_ROW_BOOSTER);
        if (!$dailyReward->canBeClaimed()) {
            $this->throwShopError(WsException::MSG_DAILY_REWARD_HAS_NOT_EXPIRED_ERROR);
        }

        if (!$this->isWinRowCountAllowable($dailyReward->getWinRowCount())) {
            $this->throwShopError(WsException::MSG_WIN_ROW_COUNT_IS_NOT_ENOUGH_ERROR);
        }

        return true;
    }

    /**
     * Gets the number of required victories
     *
     * @return int
     */
    public function getWinRowCountRequirement(): int
    {
        $dailyReward = $this->dailyRewardBusiness->getDailyRewardByType(DailyRewardTypeConstant::WIN_ROW_BOOSTER);
        $count = $dailyReward->getWinRowCount();

        return $this->id === BoosterTypeConstant::SPECIAL ? self::SPECIAL_WIN_ROW_COUNT - $count : self::LEGENDARY_WIN_ROW_COUNT - $count;
    }

    /**
     * Adds extra data to the booster array
     *
     * @return array
     */
    public function addExtraData(array $booster): array
    {
        $booster = parent::addExtraData($booster);
        $booster['winRowCost'] = $this->id === BoosterTypeConstant::SPECIAL ? self::SPECIAL_WIN_ROW_COUNT : self::LEGENDARY_WIN_ROW_COUNT;

        return $booster;
    }

    /**
     * Adds availability
     *
     * @return array
     */
    protected function addAvailability(array $booster): array
    {
        $available = true;
        $msg = null;

        try {
            $this->isAvailableToPurchase();
        } catch (\Throwable $th) {
            $msg = $th->getMessage();
            $available = false;
        }

        $booster['available'] = $available;
        if ($msg) {
            $booster['msg'] = $msg;

            if ($msg === WsException::MSG_WIN_ROW_COUNT_IS_NOT_ENOUGH_ERROR) {
                $booster['leftCount'] = $this->getWinRowCountRequirement();
            }
        }

        return $booster;
    }

    /**
     * Returns it the win row count is enough
     *
     * @param int $count
     * @return bool
     */
    protected function isWinRowCountAllowable(int $count): bool
    {
        switch ($this->id) {
            case BoosterTypeConstant::SPECIAL:
                return $count >= self::SPECIAL_WIN_ROW_COUNT;
                break;
            case BoosterTypeConstant::LEGENDARY:
                return $count >= self::LEGENDARY_WIN_ROW_COUNT;
                break;
            default:
                return false;
                break;
        }
    }

    /**
     * Modify the card type probabilities
     *
     * @return void
     */
    protected function modifyProbabilities(): void
    {
        $this->commonMod -= 2;
        $this->rareMod += 2;
    }

    /**
     * Gets the last chande probabilities
     *
     * @return array|null
     */
    protected function getLastChanceProbabilities(): ?array
    {
        if ($this->id !== BoosterTypeConstant::LEGENDARY) {
            return null;
        }

        return [
            CardTypeConstant::RARE => self::CARD_RARE_PROBABILITY_LAST_CHANCE,
            CardTypeConstant::LEGENDARY => self::CARD_LEGENDARY_PROBABILITY_LAST_CHANCE
        ];
    }

    /**
     * Does more logic after buy a booster
     */
    protected function afterBuy(): void
    {
        $dailyReward = $this->dailyRewardBusiness->getDailyRewardByType(DailyRewardTypeConstant::WIN_ROW_BOOSTER);
        $dailyReward->setClaimed();
    }
}
