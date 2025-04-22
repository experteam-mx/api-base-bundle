<?php

namespace Experteam\ApiBaseBundle\Service\ELKLogger;

use Experteam\ApiBaseBundle\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ELKLogger implements ELKLoggerInterface
{
    private array $context = [];

    public function __construct(
        private readonly LoggerInterface       $appLogger,
        private readonly RequestStack          $requestStack,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly ParameterBagInterface $parameterBag
    )
    {
    }

    public function infoLog(string $message, array $data = []): void
    {
        $this->appLogger->info($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    protected function getContext(): array
    {
        if (empty($this->context)) {
            $cfgParams = $this->parameterBag->get('experteam_api_base.elk_logger');
            $request = $this->requestStack->getCurrentRequest();
            $transactionId = (!is_null($request) && $request->headers->has('Transaction-Id') ? $request->headers->get('Transaction-Id') : null);

            $this->context = [
                'id' => (empty($transactionId) ? uniqid() : $transactionId),
                'channel' => $cfgParams['channel'],
                'timestamp' => date_create()
            ];

            $token = $this->tokenStorage->getToken();

            if (!is_null($token)) {
                $user = $token->getUser();

                if ($user instanceof User) {
                    $this->context['user'] = [
                        'id' => $user->getId(),
                        'username' => $user->getUsername()
                    ];
                }
            }
        }

        return $this->context;
    }

    public function debugLog(string $message, array $data = []): void
    {
        $this->appLogger->debug($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    public function warningLog(string $message, array $data = []): void
    {
        $this->appLogger->warning($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    public function errorLog(string $message, array $data = []): void
    {
        $this->appLogger->error($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    public function criticalLog(string $message, array $data = []): void
    {
        $this->appLogger->critical($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    public function noticeLog(string $message, array $data = []): void
    {
        $this->appLogger
            ->notice(
                $message,
                array_merge(
                    $this->getContext(),
                    ['timestamp' => date_create()],
                    $data
                )
            );
    }
}
