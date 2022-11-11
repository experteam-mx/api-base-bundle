<?php

namespace Experteam\ApiBaseBundle\Service\TraceLogger;

use DateTimeZone;
use Doctrine\DBAL\Logging\DebugStack;
use Doctrine\ORM\EntityManagerInterface;
use Experteam\ApiBaseBundle\Security\User;
use Experteam\ApiBaseBundle\Service\ELKLogger\ELKLoggerInterface;
use Predis\Client;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Serializer\SerializerInterface;

class TraceLogger implements TraceLoggerInterface
{

    const LOG_TO_KIBANA = 'logToKibana';
    const LOG_QUERIES = 'logQueries';
    const TRACE_SUCCESS_RESPONSE = 'traceSuccessResponse';
    const TRACE_REQUESTS = 'traceRequests';

    /**
     * @var RequestStack
     */
    private $requestStack;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var ELKLoggerInterface
     */
    private $elkLogger;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var Client
     */
    private $predisClient;

    /**
     * @var DebugStack
     */
    private $doctrinelogger;

    /**
     * @var EntityManagerInterface
     */
    private $manager;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $gmtOffset = '+00:00';

    /**
     * @var array
     */
    private $request = [];

    /**
     * @var array
     */
    private $auth = [];

    /**
     * @var array
     */
    private $trace = [];

    /**
     * @var array
     */
    private $options = [
        self::LOG_TO_KIBANA => false,
        self::LOG_QUERIES => false,
        self::TRACE_SUCCESS_RESPONSE => true,
        self::TRACE_REQUESTS => false
    ];

    public function __construct(RequestStack $requestStack, ELKLoggerInterface $elkLogger, TokenStorageInterface $tokenStorage,
                                Client $predisClient, EntityManagerInterface $manager, SerializerInterface $serializer,
                                LoggerInterface $logger)
    {
        $this->logger = $logger;
        $this->requestStack = $requestStack;
        $this->elkLogger = $elkLogger;
        $this->tokenStorage = $tokenStorage;
        $this->predisClient = $predisClient;
        $this->manager = $manager;
        $this->serializer = $serializer;
        $this->doctrinelogger = new DebugStack();
    }

    /**
     * @param array|null $options
     * @return TraceLogger
     */
    public function init(array $options = []): TraceLogger
    {
        foreach ($options as $key => $value)
            if (isset($this->options[$key]))
                $this->options[$key] = $value;

        $request = $this->requestStack->getCurrentRequest();
        if (!is_null($request)) {
            $this->request = [
                'url' => $request->getUri(),
                'body' => $request->getContent()
            ];
        }

        $token = $this->tokenStorage->getToken();
        if (!is_null($token)) {
            /** @var User $user */
            $user = $token->getUser();
            $this->auth = [
                'username' => $user->getUsername(),
                'token' => $user->getToken(),
                'appkey' => $user->getAppkey()
            ];

            $session = $user->getSession();
            if (!is_null($session)) {
                $this->auth['session'] = $session;

                $location = json_decode($this->predisClient->hget('companies.location', $session['location_id'] ?? 0));
                if (!is_null($location))
                    $this->gmtOffset = $location->gmt_offset ?? '+00:00';
            }
        }

        if ($options[self::LOG_QUERIES])
            $this->manager->getConnection()
                ->getConfiguration()
                ->setSQLLogger($this->doctrinelogger);

        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return TraceLogger
     */
    public function setLogger(LoggerInterface $logger): TraceLogger
    {
        $this->logger = $logger;

        return $this;
    }

    /**
     * @param string $key
     * @param $value
     * @return TraceLogger
     */
    public function addTrace(string $key, $value): TraceLogger
    {
        $now = date_create('now', new DateTimeZone($this->gmtOffset));
        $this->trace[sprintf('[%s] %s', $now->format('Y-m-d\TH:i:s.v'), $key)] = $value;

        return $this;
    }

    /**
     * @param string $key
     * @param $object
     * @param array|null $groups
     * @return TraceLogger
     */
    public function addTraceWithSerializedObject(string $key, $object, array $groups = null): TraceLogger
    {
        return $this->addTrace(
            $key,
            $this->serializeWithCircularRefHandler($object, $groups)
        );
    }

    /**
     * @param string $key
     * @param $value
     * @return TraceLogger
     */
    public function addTraceWithJsonValue(string $key, $value): TraceLogger
    {
        return $this->addTrace(
            $key,
            json_encode($value)
        );
    }

    /**
     * @param string $message
     * @param bool $trace
     * @param bool $queries
     * @return TraceLogger
     */
    public function info(string $message, bool $trace = true, bool $queries = false): TraceLogger
    {
        $data = $this->prepareData($trace, $queries);

        $this->logger->info($message, $data);

        if ($this->options[self::LOG_TO_KIBANA])
            $this->elkLogger->infoLog($message, $data);

        return $this;
    }

    /**
     * @param string $message
     * @param bool $trace
     * @param bool $queries
     * @return TraceLogger
     */
    public function error(string $message, bool $trace = true, bool $queries = false): TraceLogger
    {
        $data = $this->prepareData($trace, $queries);

        $this->logger->error($message, $data);

        if ($this->options[self::LOG_TO_KIBANA])
            $this->elkLogger->errorLog($message, $data);

        return $this;
    }

    /**
     * @param Response $response
     * @return TraceLogger
     */
    public function response(Response $response): TraceLogger
    {
        $statusCode = $response->getStatusCode();

        if ($statusCode != 200 || $this->options[self::TRACE_SUCCESS_RESPONSE])
            $this->addTrace('Response Content', $response->getContent());

        return $statusCode == 200
            ? $this->info($statusCode, true, true) :
            $this->error($statusCode, true, true);
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param bool $trace
     * @param bool $queries
     * @return array
     */
    protected function prepareData(bool $trace = true, bool $queries = false): array
    {
        return array_merge([
            'request' => $this->request,
            'auth' => $this->auth,
            'gmtOffset' => $this->gmtOffset
        ],
            $trace ? ['trace' => $this->trace] : [],
            $queries ? ['queries' => $this->doctrinelogger->queries] : []
        );
    }

    /**
     * @param $object
     * @param array|null $groups
     * @return string
     */
    protected function serializeWithCircularRefHandler($object, array $groups = null): string
    {
        $context = [
            'circular_reference_handler' => function ($object) {
                return (method_exists($object, 'getId') ? $object->getId() : null);
            }
        ];

        if (!is_null($groups)) {
            $context['groups'] = $groups;
        }

        return $this->serializer->serialize($object, 'json', $context);
    }


}