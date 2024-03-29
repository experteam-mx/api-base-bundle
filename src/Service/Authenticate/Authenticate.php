<?php

namespace Experteam\ApiBaseBundle\Service\Authenticate;

use Experteam\ApiBaseBundle\Security\User;
use Redis;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;
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
     * @var Redis
     */
    private $redis;

    public function __construct(HttpClientInterface $httpClient, ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage, Redis $redis)
    {
        $this->httpClient = $httpClient;
        $this->parameterBag = $parameterBag;
        $this->tokenStorage = $tokenStorage;
        $this->redis = $redis;
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

        $user = $this->getRedisUser($token);
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
        $user = $this->getRedisUser($token);
        if (is_null($user))
            return null;

        $this->setTokenAuthentication($user);

        return $user;
    }

    /**
     * @param string $appKey
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
        $this->tokenStorage->setToken(new PostAuthenticationToken(
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
     * @param string $credentials
     * @param int $authType
     * @return User|null
     */
    public function getRedisUser(string $credentials, int $authType = self::AUTH_TOKEN): ?User
    {
        $user = null;
        $data = json_decode($this->redis->get($this->getRedisKey($credentials, $authType)), true);

        if (!is_null($data)) {
            $data = $this->formatUserData($credentials, $data, $authType);
            $user = new User($data);
        }

        return $user;
    }

    /**
     * @param string $credentials
     * @param int $authType
     * @return string
     */
    public function getRedisKey(string $credentials, int $authType = self::AUTH_TOKEN): string
    {
        $prefix = $authType == self::AUTH_TOKEN ? 'security.token' : 'security.appkey';

        return "$prefix:$credentials";
    }

    /**
     * @param string $credentials
     * @param string $url
     * @param int $authType
     * @return array [user, response, error]
     */
    public function getRemoteUser(string $credentials, string $url, int $authType = self::AUTH_TOKEN): array
    {
        $user = null;
        $options = $authType == self::AUTH_TOKEN
            ? ['auth_bearer' => $credentials]
            : ['headers' => ['AppKey' => $credentials]];

        try {
            $response = $this->httpClient->request('GET', $url, $options)
                ->toArray(false);
        } catch (Throwable $e) {
            return [null, null, $e];
        }

        $data = $response['data']['user'] ?? null;

        if (!is_null($data)) {
            $data = $this->formatUserData($credentials, $data, $authType);
            $user = new User($data);
        }

        return [$user, $response, null];
    }

    /**
     * @param string $credentials
     * @param array $data
     * @param int $authType
     * @return array
     */
    protected function formatUserData(string $credentials, array $data, int $authType): array
    {
        $data[$authType == self::AUTH_TOKEN ? 'token' : 'appkey'] = $credentials;

        if (isset($data['permissions'])) {
            $data['roles'] = [];

            foreach ($data['permissions'] as $permission)
                $data['roles'][] = 'ROLE_' . strtoupper($permission);

            unset($data['permissions']);
        }

        return $data;
    }
}
