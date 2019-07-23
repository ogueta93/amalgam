<?php
// src/Business/Battle/Excluder/BattleExcluder.php
namespace App\Business\Battle\Excluder;

use App\Service\WsServerApp\Traits\WsUtilsTrait;

class BattleExcluder
{
    use WsUtilsTrait;

    /** constants */
    const SUFFIX_METHOD = 'Exclusion';

    const AVAILABLE_FILTERS = [
        'cardsSelection'
    ];

    /** Properties */
    protected $battleData;
    protected $user;

    /**
     * Default Constructor
     *
     * @param array $battleData
     */
    public function __construct(array $battleData = [])
    {
        $this->battleData = $battleData;
        $this->user = $this->getLoggedUser();
    }

    /**
     * Excludes data from the battle data
     *
     * @param array $filters
     * @param int $userId
     *
     * @return array
     */
    public function exclude(array $filters, int $userId = null)
    {
        if (!$this->checkFilters($filters)) {
            return $this->battleData;
        }

        $userId = \is_null($userId) ? $this->user->getId() : $userId;

        foreach ($filters as $key => $filter) {
            $method = \sprintf('%s%s', $filter, self::SUFFIX_METHOD);
            $this->$method($userId);
        }

        return $this->battleData;
    }

    /**
     * Excludes data by the cardsSelection
     *
     * @param int $userId
     */
    protected function cardsSelectionExclusion(int $userId)
    {
        $cardSelectionToFilter = $this->battleData['progress']['main']['cardsSelection'] ?? null;
        if (!\is_null($cardSelectionToFilter)) {
            foreach ($this->battleData['progress']['main']['cardsSelection'] as $key => &$cardSelection) {
                if ($cardSelection['userId'] !== $userId) {
                    foreach ($cardSelection['cards'] as $key => &$card) {
                        $card = [
                            'placed' => $card['placed'] ?? false,
                            'captured' => $card['captured'] ?? false
                        ];
                    }
                    break;
                }
            }
        }
    }

    /**
     * Checks if the inputs filters are valid
     *
     * @param array $filters
     * @return boolean
     */
    protected function checkFilters(array $filters)
    {
        $result = true;

        foreach ($filters as $key => $filter) {
            if (!\in_array($filter, self::AVAILABLE_FILTERS)) {
                $result = false;
                break;
            }
        }

        return $result;
    }
}
