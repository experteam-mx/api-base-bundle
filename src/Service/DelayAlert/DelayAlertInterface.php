<?php

namespace Experteam\ApiBaseBundle\Service\DelayAlert;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface DelayAlertInterface
{
    /**
     * @param string $routeName
     * @return $this
     */
    public function init(string $routeName): DelayAlert;

    /**
     * @param string $key
     * @param $info
     * @return DelayAlert
     */
    public function addRequestInfo(string $key, $info): DelayAlert;

    /**
     * @param Request $request
     * @param Response $response
     * @return $this
     */
    public function response(Request $request, Response $response): DelayAlert;

    /**
     * @return array
     */
    public function getOptions(): array;

    /**
     * @return bool
     */
    public function isInitialized(): bool;

}