<?php
// src/Controller/LuckyController.php
namespace App\Controller;

use App\Base\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class LoginController extends AbstractController
{
    public function login(Request $request)
    {
        $number = random_int(0, $request->get('max'));

        return new Response(
            '<html><body>Lucky number: ' . $number . '</body></html>'
        );
    }
}
