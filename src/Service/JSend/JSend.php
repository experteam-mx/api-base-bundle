<?php

namespace Experteam\ApiBaseBundle\Service\JSend;

use FOS\RestBundle\Exception\InvalidParameterException;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class JSend implements JSendInterface
{

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        $request = $event->getRequest();
        $pathInfo = $request->getPathInfo();

        if ('/api/json' === $pathInfo) {
            return;
        }

        $response = $event->getResponse();

        if ('application/json' !== $response->headers->get('content-type')) {
            return;
        }

        $statusCode = $response->getStatusCode();
        $status = 'success';
        $data = json_decode($response->getContent(), true);
        $message = null;

        if (200 !== $statusCode) {
            if (is_array($data) && isset($data['message'])) {
                $message = $data['message'];
            }

            if (400 === $statusCode) {
                $status = 'fail';

                if (isset($message)) {
                    $data = json_decode($message, true);

                    if (is_null($data)) {
                        $data = ['message' => $message];
                    }

                    $message = null;
                }
            } else {
                $status = 'error';
                $statusCode = 500;
                $data = null;
            }
        }

        $content = ['status' => $status];

        if (isset($data)) {
            $content['data'] = $data;
        }

        if (isset($message)) {
            $content['message'] = $message;
        }

        $response->setStatusCode($statusCode);
        $response->setContent(json_encode($content));

        $headers = [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny'
        ];

        if ('GET' === $request->getMethod()) {
            $parameters = $request->query->all();
            $headers['Content-Location'] = $pathInfo . (count($parameters) > 0 ? '?' . http_build_query($parameters) : '');
        }

        $response->headers->add($headers);
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();
        if ($e instanceof InvalidParameterException) {
            $message = sprintf('Invalid parameter "%s". %s', $e->getParameter()->name, $e->getViolations()[0]->getMessage());
            $event->setThrowable(new BadRequestException($message));
        }
    }
}