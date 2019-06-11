<?php
// src/Service/JWToken.php
namespace App\Service;

use App\Base\Service\AbstractService;
use Namshi\JOSE\SimpleJWS;
use Symfony\Component\Config\FileLocator;

class JWToken extends AbstractService
{
    protected $privatePem;
    protected $publicPem;

    const ALG = 'RS256';

    /**
     * Creates a token and returns the token string
     *
     * @param string $uid
     * @return string $token
     */
    public function create($uid): string
    {
        $date = new \DateTime('+1 hour');

        $jws = new SimpleJWS([
            'alg' => self::ALG
        ]);
        $jws->setPayload([
            'uid' => $uid,
            'exp' => $date->format('U')
        ]);

        $privateKey = openssl_pkey_get_private(file_get_contents($this->privatePem[0]), $this->config['secretKey']);
        $jws->sign($privateKey);

        return $jws->getTokenString();
    }

    /**
     * Decodes a token into array
     *
     * @param string $token
     * @return array|bool $result
     */
    public function decode($token)
    {
        $result = false;

        $jws = SimpleJWS::load($token);
        $publicKey = openssl_pkey_get_public(file_get_contents($this->publicPem[0]));

        if ($jws->isValid($publicKey, self::ALG)) {
            $result = $jws->getPayload();
        }

        return $result;
    }

    /**
     * Sets config name
     *
     * @return void
     */
    protected function setConfigName()
    {
        $this->configName = 'jwt';
    }

    /**
     * Sets custom params
     *
     * @return void
     */
    protected function setCustomParams()
    {
        $configDirectories = array($this->container->get('kernel')->getProjectDir() . '/config/jwt');
        $fileLocator = new FileLocator($configDirectories);

        $this->privatePem = $fileLocator->locate($this->config['privateKey'], null, false);
        $this->publicPem = $fileLocator->locate($this->config['publicKey'], null, false);
    }
}
