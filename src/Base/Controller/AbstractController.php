<?php
// src/Base/Controller/AbstractController.php
namespace App\Base\Controller;

use App\Base\Entity\AbstractEntity;
use App\Service\JWToken;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\JsonResponse;

abstract class AbstractController extends Controller
{
    /**
     * Returns a JsonResponse that uses the serializer component if enabled, or json_encode.
     *
     * @param mixed $data    The response data
     * @param int   $status  The status code to use for the Response
     * @param array $headers Array of extra headers to add
     * @param array $context Context to pass to serializer when using serializer component
     *
     * @return JsonResponse
     */
    protected function json($data, $status = 200, $headers = [], $context = []): JsonResponse
    {
        if (is_subclass_of($data, AbstractEntity::class)) {
            $data = $data->toArray();
        }

        return new JsonResponse($data, $status, $this->getCustomHeaders(), $context);
    }

    protected function getCustomHeaders()
    {
        $headers = [];
        $user = $this->getUser();

        if ($user) {
            $JWToken = $this->container->get(JWToken::class);
            $headers['X-AUTH-TOKEN'] = $JWToken->create($user->getEmail());
        }

        return $headers;
    }
}
