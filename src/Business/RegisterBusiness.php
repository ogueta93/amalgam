<?php
// src/Business/RegisterBusiness.php
namespace App\Business;

use App\Service\WsServerApp\Traits\WsUtilsTrait;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Security\Core\Security;
use App\Service\WsServerApp\Exception\WsException;
use App\Entity\User;
use App\Service\WsServerApp\WsSecurity;
use Symfony\Component\HttpFoundation\Response;

class RegisterBusiness
{
    use WsUtilsTrait;

    /** Symfony Services */
    protected $container;
    protected $em;
    protected $security;

    /** Properties */

    public function __construct(ContainerInterface $container, EntityManagerInterface $em, Security $security)
    {
        $this->container = $container;
        $this->em = $em;
        $this->security = $security;
    }

    /**
     * Register a new user
     *
     * @param array $content => @param string name, @param string lastName, @param string birthday, @param string email, @param string password
     * @return void
     */
    public function register($content)
    {
        $name = $content['name'] ?? null;
        $lastName = $content['lastName'] ?? null;
        $birthday = $content['birthday'] ?? null;
        $email = $content['email'] ?? null;
        $password = $content['password'] ?? null;

        if (!$name || !$lastName || !$birthday || !$email || !$password) {
            throw new WsException(Response::HTTP_FORBIDDEN, [
                'message' => WsException::MSG_NOT_VALID_DATA_ON_WS_SERVICE,
                'phase' => WsException::WS_AMALGAN_PHASE_FATAL_ERROR
            ]);
        }
        
        $today = new \DateTime();
        $passwordEncoder = $this->container->get('security.password_encoder');

        $user = new User();
        $user->setName($name);
        $user->setLastName($lastName);
        $user->setNickName(\uniqid());
        $user->setAge(new \DateTime($birthday));
        $user->setEmail($email);
        $user->setPassword($passwordEncoder->encodePassword($user, $password));
        $user->setCreatedAt($today);
        $user->setUpdatedAt($today);
        $user->setDeletedAt(null);

        $validator = $this->container->get('validator');
        $errors = $validator->validate($user); 

        if (\count($errors) > 0) {
            throw new WsException(Response::HTTP_FORBIDDEN, [
                'message' => WsException::MSG_NOT_VALID_DATA_GENERIC,
                'phase' => WsException::WS_AMALGAN_PHASE_FATAL_ERROR
            ]);
        }
        
        $this->em->flush();
        $this->em->persist($user);
    
        /** Update NickName */
        $user->setNickname(\sprintf('%s%s', $user->getName(), $user->getId()));
        $this->em->flush();

        /** Make login */
        $wsSecurity =  $this->container->get(WsSecurity::class);
        $wsSecurity->login(['email' => $email, 'password'=> $password]);
    }
}