<?php

namespace Experteam\ApiBaseBundle\Service\Authenticate;

use Experteam\ApiBaseBundle\Security\User;
use Predis\Client;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Guard\Token\PostAuthenticationGuardToken;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class Authenticate implements AuthenticateInterface
{
    const AUTH_TOKEN = 0;
    const AUTH_APP_KEY = 1;
    
    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Client
     */
    private $predisClient;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage, Client $predisClient)
    {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->tokenStorage = $tokenStorage;
        $this->predisClient = $predisClient;
    }

    /**
     * @param string $username
     * @param string $password
     * @param string $url
     * @return User|null
     */
    public function loginWithUsernamePassword(string $username, string $password, string $url): ?User
    {
        $token = $this->getToken($username, $password, $url);
        if (is_null($token))
            return null;

        $user = $this->getRedisUser($token, self::AUTH_TOKEN);
        if (is_null($user))
            return null;

        $this->setTokenAuthentication($user);

        return $user;
    }

    /**
     * @param string $token
     * @return User|null
     */
    public function loginWithToken(string $token): ?User
    {
        $user = $this->getRedisUser($token, self::AUTH_TOKEN);
        if (is_null($user))
            return null;

        $this->setTokenAuthentication($user);

        return $user;
    }

    /**
     * @param string $appkey
     * @return User|null
     */
    public function loginWithAppKey(string $appKey): ?User
    {
        if (!$this->parameterBag->get('experteam_api_base.appkey')['enabled'])
            return null;

        $user = $this->getRedisUser($appKey, self::AUTH_APP_KEY);
        if (is_null($user))
            return null;

        $this->setTokenAuthentication($user);

        return $user;
    }

    protected function setTokenAuthentication(User $user)
    {
        $this->tokenStorage->setToken(new PostAuthenticationGuardToken(
            $user,
            'main',
            $user->getRoles()
        ));
    }

    /**
     * @param $username
     * @param $password
     * @param $url
     * @return string|null
     */
    protected function getToken($username, $password, $url): ?string
    {
        try {
            $response = $this->httpClient->request(
                'POST',
                $url, [
                'body' => [
                    'username' => $username,
                    'password' => $password
                ]
            ])->toArray(false);
        } catch (Throwable $e) {
            return null;
        }

        return $response['data']['access_token'] ?? null;
    }

    /**
     * @param string $token
     * @return User|null
     */
    protected function getRedisUser(string $credentials, int $authType): ?User
    {
        $user = null;
        $redisKey = $authType == self::AUTH_TOKEN ? 'security.token' : 'security.appkey';
        $data = json_decode($this->predisClient->get("{$redisKey}:{$credentials}"), true);

        if (!is_null($data)) {
            $data[$authType == self::AUTH_TOKEN ? 'token' : 'appkey'] = $credentials;

            if (isset($data['permissions'])) {
                $data['roles'] = [];

                foreach ($data['permissions'] as $permission) {
                    array_push($data['roles'], 'ROLE_' . strtoupper($permission));
                }

                unset($data['permissions']);
            }

            $user = new User($data);
        }

        return $user;
    }
}