<?php

namespace Experteam\ApiBaseBundle\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;

class ResponseSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            ResponseEvent::class => 'onKernelResponse'
        ];
    }

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
}