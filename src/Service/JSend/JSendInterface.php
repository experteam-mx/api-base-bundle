<?php

namespace Experteam\ApiBaseBundle\Service\JSend;

use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

interface JSendInterface
{
    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event);

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event);
}