<?php

namespace Experteam\ApiBaseBundle\Service\Authenticate;

use Experteam\ApiBaseBundle\Security\User;

interface AuthenticateInterface
{
    /**
     * @param string $username
     * @param string $password
     * @param string $url
     * @return User|null
     */
    public function loginWithUsernamePassword(string $username, string $password, string $url): ?User;

    /**
     * @param string $token
     * @return User|null
     */
    public function loginWithToken(string $token): ?User;

    /**
     * @param string $appKey
     * @return User|null
     */
    public function loginWithAppKey(string $appKey): ?User;

    /**
     * @param string $credentials
     * @param int $authType
     * @return User|null
     */
    public function getRedisUser(string $credentials, int $authType = Authenticate::AUTH_TOKEN): ?User;

    /**
     * @param string $credentials
     * @param string $url
     * @param int $authType
     * @return array [user, response, error]
     */
    public function getRemoteUser(string $credentials, string $url, int $authType = Authenticate::AUTH_TOKEN): array;
}