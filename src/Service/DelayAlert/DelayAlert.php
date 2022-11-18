<?php

namespace Experteam\ApiBaseBundle\Service\DelayAlert;

use Experteam\ApiBaseBundle\Security\User;
use Experteam\ApiBaseBundle\Service\TraceLogger\TraceLoggerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Validation;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Throwable;

class DelayAlert implements DelayAlertInterface
{
    const TRACE_LOGGER = 'traceLogger';
    const REQUEST_INFO = 'requestInfo';

    const ERROR_MESSAGE = 'Error sending alert email';

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;
    /**
     * @var TraceLoggerInterface
     */
    private $traceLogger;

    /**
     * @var HttpClientInterface
     */
    private $httpClient;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var bool
     */
    private $initialized = false;

    /**
     * @var float|null
     */
    private $seconds = null;

    /**
     * @var array
     */
    private $requestInfo = [];

    /**
     * @var array
     */
    private $options = [
        self::TRACE_LOGGER => true,
        self::REQUEST_INFO => true,
    ];

    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $application;

    /**
     * @var array
     */
    private $destination;

    /**
     * @var array
     */
    private $routes = [];

    public function __construct(TokenStorageInterface $tokenStorage, ParameterBagInterface $parameterBag,
                                TraceLoggerInterface $traceLogger, LoggerInterface $logger,
                                HttpClientInterface $httpClient)
    {
        $this->tokenStorage = $tokenStorage;
        $this->parameterBag = $parameterBag;
        $this->traceLogger = $traceLogger;
        $this->logger = $logger;
        $this->httpClient = $httpClient;

        $config = $parameterBag->get('experteam_api_base.delay_alert');
        $this->url = $config['remote_url'];
        $this->application = $config['application'];

        $name = $config['destination_name'];
        $address = $config['destination_address'];
        if (!empty($name) && !empty($address)) {
            $this->destination = [
                'name' => $name,
                'address' => $address
            ];
        }

        $this->options[self::TRACE_LOGGER] = $config['attach_trace_logger'];
        $this->options[self::REQUEST_INFO] = $config['attach_request_info'];

        foreach ($config['routes'] as $route) {
            $seconds = $route['seconds'];

            if (!is_int($seconds) || empty($seconds))
                continue;

            $this->routes[$route['name']] = $seconds;
        }
    }

    /**
     * @param string $routeName
     * @return $this
     */
    public function init(string $routeName): DelayAlert
    {
        $routeSeconds = $this->routes[$routeName] ?? null;

        if (!is_null($routeSeconds)) {
            $this->seconds = $routeSeconds;
            $this->initialized = true;
        }

        return $this;
    }

    /**
     * @param string $key
     * @param $info
     * @return DelayAlert
     */
    public function addRequestInfo(string $key, $info): DelayAlert
    {
        $debug = $info['debug'] ?? '';
        unset($info['debug']);

        $this->requestInfo[str_replace(' ', '_', $key)] = [
            'debug' => $debug,
            'info' => array_filter($info, function($v) {
                return is_string($v) || is_array($v) || is_numeric($v);
            }, ARRAY_FILTER_USE_BOTH)
        ];

        return $this;
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return $this
     */
    public function response(Request $request, Response $response): DelayAlert
    {
        if (!$this->isInitialized() || is_null($this->destination) || empty($this->url))
            return $this;

        $diff = microtime(true) - $request->server->get('REQUEST_TIME_FLOAT');
        if (bccomp($diff, $this->seconds) != 1)
            return $this;

        $this->sendAlert($response->getStatusCode(), $diff, $request);

        return $this;
    }

    /**
     * @param int $statusCode
     * @param int $seconds
     * @param Request $request
     * @return void
     */
    protected function sendAlert(int $statusCode, int $seconds, Request $request)
    {
        if (Validation::createValidator()->validate($this->url, [new Assert\Url()])->count() != 0) {
            if ($this->traceLogger->isInitialized())
                $this->traceLogger->addTrace(self::ERROR_MESSAGE, 'Invalid url: ' . $this->url);
            else $this->logger->error(self::ERROR_MESSAGE, ['Invalid url:' => $this->url]);
        }

        $attachTraceLogger = $this->options[self::TRACE_LOGGER] && $this->traceLogger->isInitialized();
        $attachRequestInfo = $this->options[self::REQUEST_INFO];

        $emailBody = $this->getEmailBody(
            $this->application,
            $request->getUri(),
            $seconds,
            $statusCode,
            $this->parameterBag->get('kernel.environment')
        );

        $emailRequest = [
            'destinations' => [$this->destination],
            'subject' => 'Reporte de Demora en ' . $this->application,
            'body' => $emailBody,
            'attachments' => []
        ];

        if ($attachTraceLogger)
            $emailRequest['attachments'][] = [
                'content' => base64_encode(json_encode($this->traceLogger->prepareData())),
                'name' => 'log.txt',
                'embed' => false
            ];

        if ($attachRequestInfo) {
            foreach ($this->requestInfo as $key => $reqInfo) {
                $emailRequest['attachments'][] = [
                    'content' => base64_encode(json_encode($reqInfo['info'])),
                    'name' => "{$key}_Info.txt",
                    'embed' => false
                ];

                $emailRequest['attachments'][] = [
                    'content' => base64_encode(json_encode($reqInfo['debug'])),
                    'name' => "{$key}_Debug.txt",
                    'embed' => false
                ];
            }
        }

        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();

        try {
            $response = $this->httpClient->request(
                'POST',
                $this->url, [
                'auth_bearer' => $user->getToken(),
                'body' => json_encode($emailRequest)
            ])->toArray(false);

            if ($response['status'] != 'success') {
                if ($this->traceLogger->isInitialized())
                    $this->traceLogger->addTraceWithJsonValue(self::ERROR_MESSAGE, $response);
                else $this->logger->error(self::ERROR_MESSAGE, ['response' => $response]);
            }

        } catch (Throwable $e) {
            $this->exceptionSendAlert($e);
        }
    }

    /**
     * @param Throwable|null $e
     * @return void
     */
    protected function exceptionSendAlert(Throwable $e)
    {
        if ($this->traceLogger->isInitialized())
            $this->traceLogger->addTrace(self::ERROR_MESSAGE, $e->getMessage());
        else $this->logger->error(self::ERROR_MESSAGE, ['trace' => $e->getTraceAsString()]);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @return bool
     */
    public function isInitialized(): bool
    {
        return $this->initialized && !empty($this->seconds);
    }

    /**
     * @param string $app
     * @param string $url
     * @param int $seconds
     * @param int $statusCode
     * @param string $env
     * @return string
     */
    protected function getEmailBody(string $app, string $url, int $seconds, int $statusCode, string $env): string
    {
        return "<!DOCTYPE html>
            <html lang='es'>
                <head>
                    <meta charset=\"UTF-8\">
                    <title>Reporte de Demora en $app</title>
                </head>
            <body>
                <p>Estimad@(s):</p>
                <p>Este correo es debido a que se detectó una demora excesiva en $app.</p>
        
                <p>URL: $url</p>
                <p>Tiempo de respuesta: $seconds segundos</p>
                <p>Código de Respuesta: $statusCode</p>
                <p>Ambiente: $env</p>
                <p>Se adjunta el log con las acciones realizadas en la llamada.</p>
                <p style=\"font-style: italic\">Por favor, no conteste este correo electrónico, el mismo ha sido generado de forma automática.</p>
            </body>
        </html>";
    }

}