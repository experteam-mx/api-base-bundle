<?php

namespace Experteam\ApiBaseBundle\Security;

use Predis\Client;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var Client
     */
    private $predisClient;

    public function __construct(Client $predisClient)
    {
        $this->predisClient = $predisClient;
    }

    public function supports(Request $request)
    {
        // look for header "Authorization: Bearer <token>"
        return (!isset($_ENV['APP_SECURITY_ACCESS_ROLE']) || $_ENV['APP_SECURITY_ACCESS_ROLE'] !== 'IS_ANONYMOUS')
            && $request->headers->has('Authorization')
            && 0 === strpos($request->headers->get('Authorization'), 'Bearer ');
    }

    public function getCredentials(Request $request)
    {
        // skip beyond "Bearer "
        return substr($request->headers->get('Authorization'), 7);
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = null;
        $data = json_decode($this->predisClient->get("security.token:{$credentials}"), true);

        if (!is_null($data)) {
            $data['token'] = $credentials;

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

    public function checkCredentials($credentials, UserInterface $user)
    {
        return true;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @throws Exception
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception)
    {
        throw new Exception('Unauthorized.');
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, $providerKey)
    {
        // allow the authentication to continue
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @throws Exception
     */
    public function start(Request $request, AuthenticationException $authException = null)
    {
        throw new Exception('Unauthorized.');
    }

    public function supportsRememberMe()
    {
        return false;
    }
}