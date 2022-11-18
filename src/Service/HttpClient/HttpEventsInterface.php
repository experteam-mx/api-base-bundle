<?php

namespace Experteam\ApiBaseBundle\Service\HttpClient;

use Symfony\Contracts\HttpClient\ResponseInterface;

interface HttpEventsInterface
{
    /**
     * @param string|null $traceMessage
     * @param string $url
     * @return void
     */
    public function beforeRequest(?string $traceMessage, string $url);

    /**
     * @param string|null $traceMessage
     * @param ResponseInterface $response
     * @return void
     */
    public function afterRequest(?string $traceMessage, ResponseInterface $response);
}