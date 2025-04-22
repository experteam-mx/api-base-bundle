<?php

namespace Experteam\ApiBaseBundle\Service\ELKLogger;

interface ELKLoggerInterface
{
    public function infoLog(string $message, array $data = []): void;

    public function debugLog(string $message, array $data = []): void;

    public function warningLog(string $message, array $data = []): void;

    public function errorLog(string $message, array $data = []): void;

    public function criticalLog(string $message, array $data = []): void;

    public function noticeLog(string $message, array $data = []): void;
}
