<?php

namespace Experteam\ApiBaseBundle\Service\HttpClient;

use Closure;
use Experteam\ApiBaseBundle\Security\User;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface as BaseHttpClientInterface;

class HttpClient implements HttpClientInterface
{

    const DEFAULT_OPTIONS = [
        'verify_peer' => false,
        'verify_host' => false,
    ];

    /**
     * @var BaseHttpClientInterface
     */
    protected $client;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ValidatorInterface
     */
    private $validator;

    /**
     * @var HttpEventsInterface
     */
    private $httpEvents;

    /**
     * @var string|null
     */
    private $traceMessage;

    /**
     * @param BaseHttpClientInterface $httpClient
     * @param TokenStorageInterface $tokenStorage
     * @param ValidatorInterface $validator
     * @param HttpEventsInterface $httpEvents
     */
    public function __construct(BaseHttpClientInterface $httpClient, TokenStorageInterface $tokenStorage,
                                ValidatorInterface $validator, HttpEventsInterface $httpEvents)
    {
        $this->client = $httpClient;
        $this->tokenStorage = $tokenStorage;
        $this->validator = $validator;
        $this->httpEvents = $httpEvents;
    }

    /**
     * @param string $traceMessage
     * @return HttpClient
     */
    public function setTraceMessage(string $traceMessage): HttpClient
    {
        $this->traceMessage = $traceMessage;

        return $this;
    }

    /**
     * @param string $url
     * @param mixed $body
     * @param Closure|null $dataValidator
     * @param array $query
     * @param string|null $appKey
     * @param array $headers
     * @param bool $ignoreResponse
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function post(string $url, $body, Closure $dataValidator = null, array $query = [], string $appKey = null, array $headers = [], bool $ignoreResponse = false): array
    {
        return $this->request('POST', $url, ['body' => $body, 'query' => $query, 'headers' => $headers], $appKey, $dataValidator, $ignoreResponse);
    }

    /**
     * @param string $url
     * @param mixed $body
     * @param Closure|null $dataValidator
     * @param array $query
     * @param string|null $appKey
     * @param array $headers
     * @param bool $ignoreResponse
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function put(string $url, $body, Closure $dataValidator = null, array $query = [], string $appKey = null, array $headers = [], bool $ignoreResponse = false): array
    {
        return $this->request('PUT', $url, ['body' => $body, 'query' => $query, 'headers' => $headers], $appKey, $dataValidator, $ignoreResponse);
    }

    /**
     * @param string $url
     * @param array $query
     * @param Closure|null $dataValidator
     * @param string|null $appKey
     * @param array $headers
     * @param bool $ignoreResponse
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function get(string $url, array $query = [], Closure $dataValidator = null, string $appKey = null, array $headers = [], bool $ignoreResponse = false): array
    {
        return $this->request('GET', $url, ['query' => $query, 'headers' => $headers], $appKey, $dataValidator, $ignoreResponse);
    }

    /**
     * @param string $method
     * @param string $url
     * @param array $options
     * @param string|null $appKey
     * @param Closure|null $dataValidator
     * @param bool $ignoreResponse
     * @return array [status, message, response]
     * @throws HttpException
     */
    protected function request(string $method, string $url, array $options, string $appKey = null, Closure $dataValidator = null, bool $ignoreResponse = false): array
    {
        $options = array_merge($options, self::DEFAULT_OPTIONS);

        if (is_null($appKey)) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()->getUser();
            $options['auth_bearer'] = $user->getToken();
        } else
            $options['headers'] = array_merge($options['headers'] ?? [], ['AppKey' => $appKey]);

        try {
            $this->httpEvents->beforeRequest($this->traceMessage, $url);
            $this->traceMessage = null;

            $_response = $this->client->request($method, $url, $options);

            if ($ignoreResponse)
                return [true, 'Ok', null];

            $response = $_response->toArray(false);
            $this->httpEvents->afterRequest($this->traceMessage, $_response);

        } catch (ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface | TransportExceptionInterface $e) {
            throw new HttpException(500, $e->getMessage());
        }

        [$status, $message] = $this->validateResponse($response, $dataValidator);
        if (!$status)
            return [false, sprintf('The response received from url %s is not valid: %s', $url, $message), $response];

        switch ($response['status']) {
            case HttpResponse::ERROR:
                return [false, $response['message'], $response];
            case HttpResponse::FAIL:
                return [false, $response['data']['message'] ?? json_encode($response['data']), $response];
        }

        return [true, 'Ok', $response];
    }

    /**
     * @param array $response
     * @param Closure|null $dataValidator
     * @return array [status, message]
     */
    protected function validateResponse(array $response, Closure $dataValidator = null): array
    {
        $violations = $this->validator->validate(new HttpResponse($response));

        if ($violations->count() > 0) {
            $errors = [];

            /** @var ConstraintViolation $violation */
            foreach ($violations as $violation)
                $errors[$violation->getPropertyPath()] = $violation->getMessage();

            return [false, json_encode($errors)];
        }

        if ($response['status'] == HttpResponse::SUCCESS && !is_null($dataValidator)) {
            [$status, $message] = $dataValidator($response['data']);
            if (!$status)
                return [false, $message];
        }

        return [true, 'Ok'];
    }
}