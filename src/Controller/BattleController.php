<?php
// src/Controller/BattleController.php
namespace App\Controller;

use App\Base\Controller\AbstractController;
use App\Business\BattleBusiness;
use Symfony\Component\HttpFoundation\Request;

class BattleController extends AbstractController
{

    /**
     * Creates a new battle and return json battle data
     *
     * @return json
     */
    public function newBattle(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $Battle = $this->container->get(BattleBusiness::class);
        $Battle->newBattle($data);

        return $this->json($Battle->getData());
    }

    /**
     * Gets public data of a battle
     *
     * @return json
     */
    public function getData(Request $request)
    {
        $battleId = $request->get('id');

        $Battle = $this->container->get(BattleBusiness::class);
        $Battle->findBattle($battleId);

        return $this->json($Battle->getData());
    }
}
