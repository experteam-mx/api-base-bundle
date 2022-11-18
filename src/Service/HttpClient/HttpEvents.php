<?php

namespace Experteam\ApiBaseBundle\Service\HttpClient;

use Experteam\ApiBaseBundle\Service\DelayAlert\DelayAlert;
use Experteam\ApiBaseBundle\Service\DelayAlert\DelayAlertInterface;
use Experteam\ApiBaseBundle\Service\TraceLogger\TraceLogger;
use Experteam\ApiBaseBundle\Service\TraceLogger\TraceLoggerInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

class HttpEvents implements HttpEventsInterface
{
    /**
     * @var TraceLoggerInterface
     */
    private $traceLogger;

    /**
     * @var DelayAlertInterface
     */
    private $delayAlert;

    /**
     * @param TraceLoggerInterface $traceLogger
     * @param DelayAlertInterface $delayAlert
     */
    public function __construct(TraceLoggerInterface $traceLogger, DelayAlertInterface $delayAlert)
    {
        $this->traceLogger = $traceLogger;
        $this->delayAlert = $delayAlert;
    }

    /**
     * @param string|null $traceMessage
     * @param string $url
     * @return void
     */
    public function beforeRequest(?string $traceMessage, string $url)
    {
        if ($this->traceLogger->isInitialized() && $this->traceLogger->getOptions()[TraceLogger::TRACE_REQUESTS])
            $this->traceLogger->addTrace(($traceMessage ?? '') . 'Request Begin', $url);
    }

    /**
     * @param string|null $traceMessage
     * @param ResponseInterface $response
     * @return void
     */
    public function afterRequest(?string $traceMessage, ResponseInterface $response)
    {
        if ($this->traceLogger->isInitialized() && $this->traceLogger->getOptions()[TraceLogger::TRACE_REQUESTS])
            $this->traceLogger->addTrace(($traceMessage ?? '') . 'Request Finish', true);

        if ($this->delayAlert->isInitialized() && $this->delayAlert->getOptions()[DelayAlert::REQUEST_INFO])
            $this->delayAlert->addRequestInfo(($traceMessage ?? '') . 'Request', $response->getInfo());
    }
}