<?php
// src/Business/Battle/Traits/UtilsTrait.php
namespace App\Business\Battle\Traits;

use App\Business\Battle\BattleException;
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
     * Gets battle data by the quickets way
     *
     * @return array
     */
    protected function quickBattleData(): array
    {
        $battleEnt = $this->em->getRepository(Battle::class)->find((int) $this->battleId);
        if (!$battleEnt) {
            $this->battleException->throwError(BattleException::GENERIC_NOT_FOUND_ELEMENT);
        }

        $userBattle = $this->em->getRepository(UserBattle::class)->findBy(['user' => $this->getLoggedUser(), 'battle' => $battleEnt]);
        if (!$userBattle) {
            $this->battleException->throwError(BattleException::GENERIC_SECURITY_ERROR);
        }

        $this->battleEnt = $battleEnt;

        $data = $this->cache->get(sprintf(CacheType::BATTLE, (int) $this->battleId));
        if (!$data) {
            $data = $battleEnt->getData();
        }

        return \json_decode($data, true);
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

            if ($this->battleEnt) {
                $this->battleEnt->setData($data);

                if ($cache) {
                    $this->cache->set(
                        sprintf(CacheType::BATTLE, $this->battleId),
                        $data,
                        (new \DateTime('tomorrow'))->getTimestamp()
                    );
                }

                $this->em->flush();
            }
        }
    }
}
