<?php

namespace Experteam\ApiBaseBundle\Service\HttpClient;

use Closure;
use Symfony\Component\HttpKernel\Exception\HttpException;

interface HttpClientInterface
{
    /**
     * @param string $url
     * @param mixed $body
     * @param Closure|null $dataValidator
     * @param array $query
     * @param string|null $appKey
     * @param array $headers
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function post(string $url, $body, Closure $dataValidator = null, array $query = [], string $appKey = null, array $headers = []): array;

    /**
     * @param string $url
     * @param mixed $body
     * @param Closure|null $dataValidator
     * @param array $query
     * @param string|null $appKey
     * @param array $headers
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function put(string $url, $body, Closure $dataValidator = null, array $query = [], string $appKey = null, array $headers = []): array;

    /**
     * @param string $url
     * @param array $query
     * @param Closure|null $dataValidator
     * @param string|null $appKey
     * @param array $headers
     * @return array [status, message, response]
     * @throws HttpException
     */
    public function get(string $url, array $query = [], Closure $dataValidator = null, string $appKey = null, array $headers = []): array;
}