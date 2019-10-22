<?php
// src/Business/Battle/Traits/UtilsTrait.php
namespace App\Business\Battle\Traits;

use App\Business\Battle\BattleException;
use App\Business\Battle\Excluder\BattleExcluder;
use App\Entity\Battle;
use App\Entity\UserBattle;
use App\Service\Cache\CacheType;

trait BattleUtilsTrait
{
    /** Object Properties */
    protected $battleEnt;

    /** Properties */
    protected $battleId;
    protected $data;

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
     * Get battle rivas from data
     *
     * @return array $clients
     */
    protected function getRivalsFromData(): array
    {
        $clients = [];

        foreach ($this->data['users'] as $key => $user) {
            if ($this->getLoggedUser()->getId() != $user['user']['id']) {
                $clients[] = ['id' => $user['user']['id']];
            }
        }

        return $clients;
    }

    /**
     * Gets filtered data by the user
     *
     * @param int $userId
     * @return array
     */
    protected function getFilteredData(int $userId = null): array
    {
        $battleExcluder = new BattleExcluder($this->data);
        return $battleExcluder->exclude(['cardsSelection'], $userId);
    }

    /**
     * Gets data filtered by rivals
     *
     * @param int $userId
     * @return array
     */
    protected function getFilteredDataFromRivals()
    {
        $clientsData = $this->getRivalsFromData();
        foreach ($clientsData as $key => &$clientData) {
            $clientData['msg'] = $this->getFilteredData($clientData['id']);
        }

        return $clientsData;
    }

    /**
     * Gets battle data by the quickets way
     *
     * @return array
     */
    protected function quickBattleData(): array
    {
        $data = \json_decode($this->cache->get(sprintf(CacheType::BATTLE, (int) $this->battleId)), true);

        if ($data) {
            $userRelation = \array_filter($data['users'], function ($element) {
                return $this->getLoggedUser()->getId() === $element['user']['id'];
            });

            if (!$userRelation) {
                $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
            }
        } else {
            $battleEnt = $this->em->getRepository(Battle::class)->find((int) $this->battleId);
            if (!$battleEnt) {
                $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
            }

            $userBattle = $this->em->getRepository(UserBattle::class)->findBy(['user' => $this->getLoggedUser(), 'battle' => $battleEnt]);
            if (!$userBattle) {
                $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
            }

            $this->battleEnt = $battleEnt;

            $data = \json_decode($battleEnt->getData(), true);
        }

        return $data;
    }

    /**
     * Save battle on database and cache
     *
     * @param bool $cache
     * @return void
     */
    protected function save($cache = true)
    {
        $data = \json_encode($this->data);

        if ($this->battleId) {
            $this->battleEnt = $this->battleEnt ?? $this->em->getRepository(Battle::class)->find($this->battleId);

            if (!$this->battleEnt) {
                $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
            }

            $this->battleEnt->setData($data);

            if ($cache) {
                $this->cache->set(
                    sprintf(CacheType::BATTLE, $this->battleId),
                    $data,
                    86400
                );
            }

            $this->em->flush();
        }
    }
}
