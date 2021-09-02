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
     * @param string $appkey
     * @return User|null
     */
    public function loginWithAppKey(string $appKey): ?User;
}