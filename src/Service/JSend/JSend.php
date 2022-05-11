<?php

namespace Experteam\ApiBaseBundle\Service\JSend;

use Experteam\ApiBaseBundle\Service\ELKLogger\ELKLoggerInterface;
use Experteam\ApiBaseBundle\Service\Transaction\TransactionInterface;
use FOS\RestBundle\Exception\InvalidParameterException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class JSend implements JSendInterface
{
    const PATH_INFO = '/api/json';
    const CONTENT_TYPE = 'application/json';

    /**
     * @var ELKLoggerInterface
     */
    protected $logger;

    /**
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var ParameterBagInterface
     */
    protected $parameterBag;

    /**
     * @var TransactionInterface
     */
    protected $transaction;

    public function __construct(ELKLoggerInterface $elkLogger, RequestStack $requestStack, ParameterBagInterface $parameterBag, TransactionInterface $transaction)
    {
        $this->logger = $elkLogger;
        $this->requestStack = $requestStack;
        $this->parameterBag = $parameterBag;
        $this->transaction = $transaction;
    }

    /**
     * @param ResponseEvent $event
     */
    public function onKernelResponse(ResponseEvent $event)
    {
        if (!$this->isResponseSupports($event)) {
            return;
        }

        $request = $event->getRequest();
        $response = $event->getResponse();
        $this->configResponseETag($response, $request);
        $statusCode = $response->getStatusCode();

        if (304 === $statusCode) {
            return;
        }

        $status = 'success';
        $data = json_decode($response->getContent(), true);
        $message = null;

        if (200 !== $statusCode) {
            if (is_array($data) && isset($data['message'])) {
                $message = $data['message'];
            }

            if (in_array($statusCode, [400, 401, 403])) {
                $status = 'fail';

                if (isset($message)) {
                    $data = json_decode($message, true);

                    if (is_null($data)) {
                        $data = ['message' => $message];
                    }

                    $message = null;
                }

                if (is_array($data)) {
                    $transactionId = $this->transaction->getId(true);

                    if (!is_null($transactionId)) {
                        $data['transactionId'] = $transactionId;
                    }
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
            $headers['Content-Location'] = $request->getPathInfo() . (count($parameters) > 0 ? '?' . http_build_query($parameters) : '');
        }

        $response->headers->add($headers);
    }

    /**
     * @param ExceptionEvent $event
     */
    public function onKernelException(ExceptionEvent $event)
    {
        $e = $event->getThrowable();
        $errorCode = null;

        if ($e instanceof InvalidParameterException) {
            $message = sprintf('Invalid parameter "%s". %s', $e->getParameter()->name, $e->getViolations()[0]->getMessage());
            $event->setThrowable($e = new BadRequestHttpException($message));
        }

        /*
         * Keeps the error message in production environment
         */
        if ($e instanceof HttpException || $e->getMessage() == 'Unauthorized.') {
            $errorCode = $e instanceof HttpException ? $e->getStatusCode() : 500;
            $event->setResponse(new JsonResponse([
                'code' => $errorCode,
                'message' => $e->getMessage()
            ], $errorCode));
        }

        /*
         * Send error log to kibana
         */
        $prefix = $this->parameterBag->get('app.prefix');
        $request = $this->requestStack->getCurrentRequest();
        $this->logger->errorLog("{$prefix}_exception", [
            'request_url' => !is_null($request) ? $request->getUri() : null,
            'request_body' => !is_null($request) ? $request->getContent() : null,
            'error_code' => $errorCode,
            'error' => $e->getMessage()
        ]);
    }

    /**
     * @param Response $response
     * @param Request $request
     */
    protected function configResponseETag(Response $response, Request $request)
    {
        $enabled = $this->parameterBag->get('experteam_api_base.etag')['enabled'] ?? null;

        if (is_bool($enabled) && $enabled) {
            $response->setEtag(md5($response->getContent()));
            $response->setPublic();
            $response->isNotModified($request);
        }
    }

    /**
     * @param ResponseEvent $event
     * @return bool
     */
    public function isResponseSupports(ResponseEvent $event): bool
    {
        return $event->isMasterRequest()
            && $event->getRequest()->getPathInfo() !== self::PATH_INFO
            && $event->getResponse()->headers->get('content-type') === self::CONTENT_TYPE;
    }
}
