<?php

namespace Experteam\ApiBaseBundle\Service\TraceLogger;

use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

interface TraceLoggerInterface
{
    /**
     * @param array|null $options
     * @return TraceLogger
     */
    public function init(array $options = []): TraceLogger;

    /**
     * @param LoggerInterface $logger
     * @return TraceLogger
     */
    public function setLogger(LoggerInterface $logger): TraceLogger;

    /**
     * @param string $key
     * @param $value
     * @return TraceLogger
     */
    public function addTrace(string $key, $value): TraceLogger;

    /**
     * @param string $key
     * @param $object
     * @param array|null $groups
     * @return TraceLogger
     */
    public function addTraceWithSerializedObject(string $key, $object, array $groups = null): TraceLogger;

    /**
     * @param string $key
     * @param $value
     * @return TraceLogger
     */
    public function addTraceWithJsonValue(string $key, $value): TraceLogger;

    /**
     * @param string $message
     * @return TraceLogger
     */
    public function info(string $message): TraceLogger;

    /**
     * @param string $message
     * @return TraceLogger
     */
    public function error(string $message): TraceLogger;

    /**
     * @param Response $response
     * @return TraceLogger
     */
    public function response(Response $response): TraceLogger;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @return array
     */
    public function prepareData(): array;

    /**
     * @return bool
     */
    public function isInitialized(): bool;

}