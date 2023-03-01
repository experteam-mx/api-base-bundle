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
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function addTrace(string $key, $value, string $traceGroup = null): TraceLogger;

    /**
     * @param string $key
     * @param $object
     * @param array|null $groups
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function addTraceWithSerializedObject(string $key, $object, array $groups = null, string $traceGroup = null): TraceLogger;

    /**
     * @param string $key
     * @param $value
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function addTraceWithJsonValue(string $key, $value, string $traceGroup = null): TraceLogger;

    /**
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function clearTrace(string $traceGroup = null): TraceLogger;

    /**
     * @param string $message
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function info(string $message, string $traceGroup = null): TraceLogger;

    /**
     * @param string $message
     * @param string|null $traceGroup
     * @return TraceLogger
     */
    public function error(string $message, string $traceGroup = null): TraceLogger;

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
     * @param string|null $traceGroup
     * @return array
     */
    public function prepareData(string $traceGroup = null): array;

    /**
     * @return bool
     */
    public function isInitialized(): bool;

}