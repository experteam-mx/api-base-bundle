<?php

namespace Experteam\ApiBaseBundle\Service\ELKLogger;

use Experteam\ApiBaseBundle\Security\User;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class ELKLogger implements ELKLoggerInterface
{
    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Request
     */
    protected $request;

    public function __construct(LoggerInterface $logger, RequestStack $requestStack, TokenStorageInterface $tokenStorage, ParameterBagInterface $parameterBag)
    {
        $this->logger = $logger;
        $this->parameterBag = $parameterBag;
        $this->tokenStorage = $tokenStorage;
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * @return array
     */
    protected function getContext()
    {
        if (!isset($this->context)) {
            $cfgParams = $this->parameterBag->get('experteam_api_base.elk_logger');
            $transactionId = $this->request->get('transaction_id');

            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();

            $this->context = [
                'id' => empty($transactionId) ? uniqid() : $transactionId,
                'user' => [
                    'id' => $user->getId(),
                    'username' => $user->getUsername()
                ],
                'channel' => $cfgParams['channel'],
                'timestamp' => date_create()
            ];
        }

        return $this->context;
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function infoLog(string $message, array $data = []): void
    {
        $this->logger->info($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function debugLog(string $message, array $data = []): void
    {
        $this->logger->debug($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function warningLog(string $message, array $data = []): void
    {
        $this->logger->warning($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function errorLog(string $message, array $data = []): void
    {
        $this->logger->error($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }

    /**
     * @param string $message
     * @param array $data
     */
    public function criticalLog(string $message, array $data = []): void
    {
        $this->logger->critical($message, array_merge(
            $this->getContext(),
            ['timestamp' => date_create()],
            $data
        ));
    }
}