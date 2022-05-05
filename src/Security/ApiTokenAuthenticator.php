<?php

namespace Experteam\ApiBaseBundle\Security;

use Experteam\ApiBaseBundle\Service\Authenticate\Authenticate;
use Experteam\ApiBaseBundle\Service\Authenticate\AuthenticateInterface;
use Experteam\ApiBaseBundle\Service\ELKLogger\ELKLoggerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Guard\AbstractGuardAuthenticator;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Throwable;

class ApiTokenAuthenticator extends AbstractGuardAuthenticator
{
    /**
     * @var ELKLoggerInterface
     */
    protected $logger;

    /**
     * @var AuthenticateInterface
     */
    protected $authenticate;

    /**
     * @var array
     */
    private $appKeyConfig;

    /**
     * @var array
     */
    private $authConfig;

    /**
     * @var int
     */
    private $authType = Authenticate::AUTH_TOKEN;

    public function __construct(ParameterBagInterface $parameterBag, ELKLoggerInterface $elkLogger, AuthenticateInterface $authenticate)
    {
        $this->appKeyConfig = $parameterBag->get('experteam_api_base.appkey');
        $this->authConfig = $parameterBag->get('experteam_api_base.auth');
        $this->logger = $elkLogger;
        $this->authenticate = $authenticate;
    }

    public function supports(Request $request)
    {
        // look for header "Authorization: Bearer <token>" or "AppKey: <key>"
        return (!isset($_ENV['APP_SECURITY_ACCESS_ROLE']) || $_ENV['APP_SECURITY_ACCESS_ROLE'] !== 'IS_ANONYMOUS')
            && (($request->headers->has('Authorization') && 0 === strpos($request->headers->get('Authorization'), 'Bearer '))
                || ($this->appKeyConfig['enabled'] && $request->headers->has('AppKey')));
    }

    public function getCredentials(Request $request)
    {
        // skip beyond "Bearer "
        $credentials = substr($request->headers->get('Authorization'), 7);

        if ($credentials === false) {
            $credentials = $request->headers->get('AppKey');
            $this->authType = Authenticate::AUTH_APP_KEY;
        }

        return $credentials;
    }

    public function getUser($credentials, UserProviderInterface $userProvider)
    {
        $user = null;

        $fromRedis = $this->authConfig['from_redis'] ?? false;
        if ($fromRedis) {
            $user = $this->authenticate->getRedisUser($credentials, $this->authType);

            if (is_null($user))
                $this->logger->infoLog('Failed Redis Credentials', [
                    'credentials' => $credentials,
                    'authType' => $this->authType
                ]);
        }

        if (is_null($user)) {
            $remoteUrl = $this->authConfig['remote_url'] ?? null;

            if (Validation::createValidator()->validate($remoteUrl, [new Assert\Url(), new Assert\NotBlank()])->count() == 0) {
                /**
                 * @var User $user
                 * @var array $response
                 * @var Throwable $exception
                 */
                [$user, $response, $exception] = $this->authenticate->getRemoteUser($credentials, $remoteUrl, $this->authType);

                if (is_null($user))
                    $this->logger->infoLog('Failed Remote Credentials', [
                        'response' => $response,
                        'url' => $remoteUrl,
                        'authType' => $this->authType,
                        'exception' => !is_null($exception) ? $exception->getMessage() : null
                    ]);
            }
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
        throw new HttpException($this->authConfig['status_code'] ?? 401, 'Unauthorized.');
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
        throw new HttpException(401, 'Unauthorized.');
    }

    public function supportsRememberMe()
    {
        return false;
    }
}