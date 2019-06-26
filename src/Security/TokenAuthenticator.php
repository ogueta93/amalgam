<?php
// src/Security/TokenAuthenticator.php
namespace App\Security;

use App\Entity\User;
use App\Service\JWToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class TokenAuthenticator extends AbstractGuardAuthenticator
{
    private $em;
    private $container;

    protected $JWToken;

    public function __construct(EntityManagerInterface $em, ContainerInterface $container)
    {
        $this->em = $em;
        $this->container = $container;
        $this->JWToken = $this->container->get(JWToken::class);
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning false will cause this authenticator
     * to be skipped.
     */
    public function supports(Request $request)
    {
        return true;
    }

    /**
     * Called on every request. Return whatever credentials you want to
     * be passed to getUser() as $credentials.
     */
    public function getCredentials(Request $request)
    {
        if (!$request->headers->has('X-AUTH-TOKEN')) {
            $this->throwCustomError();
        }

        return [
            'token' => $request->headers->get('X-AUTH-TOKEN')
        ];
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        try {
            $payLoad = $this->JWToken->decode($credentials['token']);
        } catch (\Throwable $th) {
            $this->throwCustomError();
        }

        if ($payLoad) {
            // if a User object, checkCredentials() is called
            $user = $this->em->getRepository(User::class)->findOneBy(['email' => $payLoad['uid']]);
            if ($user) {
                return $user;
            }
        }

        $this->throwCustomError();
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // on success, let the request continue
        return null;
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'phase' => 'main'
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            // you might translate this message
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Throws custom message error
     *
     * @return CustomUserMessageAuthenticationException $error
     */
    public function throwCustomError()
    {
        throw new CustomUserMessageAuthenticationException(
            'valid token is needed'
        );
    }
}
