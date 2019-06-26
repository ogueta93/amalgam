<?php
// src/Security/LoginAuthenticator.php
namespace App\Security;

use App\Entity\User;
use App\Service\JWToken;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class LoginAuthenticator extends AbstractGuardAuthenticator
{
    private $entityManager;
    private $router;
    private $csrfTokenManager;
    private $passwordEncoder;
    private $container;

    protected $JWToken;
    protected $token;

    public function __construct(EntityManagerInterface $entityManager, RouterInterface $router, CsrfTokenManagerInterface $csrfTokenManager, UserPasswordEncoderInterface $passwordEncoder, ContainerInterface $container)
    {
        $this->entityManager = $entityManager;
        $this->router = $router;
        //  $this->csrfTokenManager = $csrfTokenManager;
        $this->passwordEncoder = $passwordEncoder;
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

    public function getCredentials(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        $credentials = [
            'email' => $data['email'] ?? null,
            'password' => $data['password'] ?? null
            //'csrf_token' => $request->request->get('_csrf_token')
        ];

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        /*
        $token = new CsrfToken('authenticate', $credentials['csrf_token']);
        if (!$this->csrfTokenManager->isTokenValid($token)) {
        throw new InvalidCsrfTokenException();
        }
         */

        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => $credentials['email']]);

        if (!$user) {
            // fail authentication with a custom error
            throw new CustomUserMessageAuthenticationException('Login error');
        } else {
            $this->setToken($credentials);
        }

        return $user;
    }

    public function checkCredentials($credentials, UserInterface $user)
    {
        return $this->passwordEncoder->isPasswordValid($user, $credentials['password']);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        $data = [
            'token' => $this->token
        ];

        return new JsonResponse($data);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        $data = [
            'message' => strtr($exception->getMessageKey(), $exception->getMessageData()),
            'phase' => 'default'
        ];

        return new JsonResponse($data, Response::HTTP_FORBIDDEN);
    }

    /**
     * Called when authentication is needed, but it's not sent
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        $data = [
            'message' => 'Authentication Required'
        ];

        return new JsonResponse($data, Response::HTTP_UNAUTHORIZED);
    }

    public function supportsRememberMe()
    {
        return false;
    }

    /**
     * Sets Token
     *
     * @param array $credentials
     *
     * @return void
     */
    protected function setToken($credentials)
    {
        $this->token = $this->JWToken->create($credentials['email']);
    }
}
