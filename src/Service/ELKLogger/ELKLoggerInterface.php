<?php

namespace Experteam\ApiBaseBundle\Service\ELKLogger;

interface ELKLoggerInterface
{
    /**
     * @param string $message
     * @param array $data
     */
    public function infoLog(string $message, array $data = []): void;

    /**
     * @param string $message
     * @param array $data
     */
    public function debugLog(string $message, array $data = []): void;

    /**
     * @param string $message
     * @param array $data
     */
    public function warningLog(string $message, array $data = []): void;

    /**
     * @param string $message
     * @param array $data
     */
    public function errorLog(string $message, array $data = []): void;

    /**
     * @param string $message
     * @param array $data
     */
    public function criticalLog(string $message, array $data = []): void;

    /**
     * @param string $message
     * @param array $data
     */
    public function noticeLog(string $message, array $data = []): void;
}