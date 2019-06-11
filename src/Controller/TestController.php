<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Base\Controller\AbstractController;
use App\Entity\Heroe;
use App\Entity\User;
use Symfony\Component\HttpFoundation\Request;

class TestController extends AbstractController
{
    public function index(Request $request)
    {
        $id = $request->get('id');

        $HeroeEnt = $this->getDoctrine()->getRepository(Heroe::class);

        return $this->json($HeroeEnt->find($id));
    }

    public function orm()
    {
        $User = $this->getDoctrine()->getRepository(User::class);

        $results = [];
        foreach ($User->findAll() as $key => $value) {
            $results[] = $value;
        }

        return $this->json($User->findAll());
    }
}
