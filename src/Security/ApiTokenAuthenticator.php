<?php

namespace Experteam\ApiBaseBundle\Security;

use Experteam\ApiBaseBundle\Service\Authenticate\Authenticate;
use Experteam\ApiBaseBundle\Service\Authenticate\AuthenticateInterface;
use Experteam\ApiBaseBundle\Service\ELKLogger\ELKLoggerInterface;
use Exception;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Throwable;

class ApiTokenAuthenticator extends AbstractAuthenticator implements AuthenticationEntryPointInterface
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

    /**
     * @param ParameterBagInterface $parameterBag
     * @param ELKLoggerInterface $elkLogger
     * @param AuthenticateInterface $authenticate
     */
    public function __construct(ParameterBagInterface $parameterBag, ELKLoggerInterface $elkLogger, AuthenticateInterface $authenticate)
    {
        $this->appKeyConfig = $parameterBag->get('experteam_api_base.appkey');
        $this->authConfig = $parameterBag->get('experteam_api_base.auth');
        $this->logger = $elkLogger;
        $this->authenticate = $authenticate;
    }

    /**
     * @param Request $request
     * @return string
     */
    private function getCredentials(Request $request): string
    {
        // skip beyond "Bearer "
        $credentials = substr($request->headers->get('Authorization'), 7);

        if (empty($credentials)) {
            $credentials = $request->headers->get('AppKey');
            $this->authType = Authenticate::AUTH_APP_KEY;
        }

        return $credentials;
    }

    /**
     * @param string $credentials
     * @return User|null
     */
    private function getUser(string $credentials): ?User
    {
        $user = null;
        $fromRedis = ($this->authConfig['from_redis'] ?? false);

        if ($fromRedis) {
            $user = $this->authenticate->getRedisUser($credentials, $this->authType);

            if (is_null($user)) {
                $this->logger->infoLog('Failed Redis Credentials', [
                    'credentials' => $credentials,
                    'authType' => $this->authType
                ]);
            }
        }

        if (is_null($user)) {
            $remoteUrl = ($this->authConfig['remote_url'] ?? null);

            if (Validation::createValidator()->validate($remoteUrl, [new Assert\Url(), new Assert\NotBlank()])->count() == 0) {
                /**
                 * @var User $user
                 * @var array $response
                 * @var Throwable $exception
                 */
                [$user, $response, $exception] = $this->authenticate->getRemoteUser($credentials, $remoteUrl, $this->authType);

                if (is_null($user)) {
                    $this->logger->infoLog('Failed Remote Credentials', [
                        'response' => $response,
                        'url' => $remoteUrl,
                        'authType' => $this->authType,
                        'exception' => (!is_null($exception) ? $exception->getMessage() : null)
                    ]);
                }
            }
        }

        return $user;
    }

    /**
     * Called on every request to decide if this authenticator should be
     * used for the request. Returning `false` will cause this authenticator
     * to be skipped.
     * @param Request $request
     * @return bool|null
     */
    public function supports(Request $request): ?bool
    {
        // look for header "Authorization: Bearer <token>" or "AppKey: <key>"
        return (!isset($_ENV['APP_SECURITY_ACCESS_ROLE']) || $_ENV['APP_SECURITY_ACCESS_ROLE'] !== 'IS_ANONYMOUS')
            && (($request->headers->has('Authorization') && 0 === strpos($request->headers->get('Authorization'), 'Bearer '))
                || ($this->appKeyConfig['enabled'] && $request->headers->has('AppKey')));
    }

    /**
     * @param Request $request
     * @return SelfValidatingPassport
     */
    public function authenticate(Request $request): SelfValidatingPassport
    {
        $credentials = $this->getCredentials($request);

        return new SelfValidatingPassport(new UserBadge($credentials, function (string $userIdentifier) {
            return $this->getUser($userIdentifier);
        }));
    }

    /**
     * @param Request $request
     * @param TokenInterface $token
     * @param string $firewallName
     * @return Response|null
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        // on success, let the request continue
        return null;
    }

    /**
     * @param Request $request
     * @param AuthenticationException $exception
     * @return Response|null
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        throw new HttpException(($this->authConfig['status_code'] ?? 401), 'Unauthorized.');
    }

    /**
     * @param Request $request
     * @param AuthenticationException|null $authException
     * @return Response|null
     * @throws Exception
     */
    public function start(Request $request, AuthenticationException $authException = null): ?Response
    {
        throw new HttpException(401, 'Unauthorized.');
    }
}
