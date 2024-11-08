<?php

namespace Experteam\ApiBaseBundle\Util;

use DateTime;
use DateTimeZone;
use Exception;
use Experteam\ApiBaseBundle\Security\User;
use PDO;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class Common
{
    /**
     * @return bool|int
     * @throws Exception
     */
    public static function getTimeZoneOffset()
    {
        return timezone_offset_get(new DateTimeZone(date_default_timezone_get()), new DateTime('now'));
    }

    /**
     * @param int|null $zone
     * @param string|null $format
     * @return string
     * @throws Exception
     */
    public static function getMicroTimeByZone($zone = null, $format = null): string
    {
        $microTime = '';

        if (is_null($format) || $format === '') {
            $format = 'Ymd';
        }

        if (is_null($zone)) {
            $timeZone = Common::getTimeZoneOffset();
        } else {
            $timeZone = intval($zone) * 3600;
        }

        if (isset($timeZone) && $timeZone !== false) {
            $t = microtime(true) + $timeZone;
            $micro = sprintf('%06d', ($t - floor($t)) * 1000000);
            $d = new DateTime(gmdate($format . ' H:i:s.' . $micro, $t));
            $microTime = substr($d->format($format . ' H:i:s.u'), 0, (strlen($d->format($format . ' H:i:s.u')) - 3));
            $microTime = (($microTime === false) ? '' : $microTime);
        }

        return $microTime;
    }

    /**
     * @return float|int
     * @throws Exception
     */
    public static function getDefaultTimeZone()
    {
        return (Common::getTimeZoneOffset() / 3600);
    }

    /**
     * @return string
     * @throws Exception
     */
    public static function getMessageTime(): string
    {
        $timeZone = Common::getDefaultTimeZone();
        $timeZoneAbs = abs($timeZone);
        $microTime = Common::getMicroTimeByZone(0, 'Y-m-d');
        return str_replace(' ', 'T', $microTime) . (($timeZone < 0) ? '-' : '+') . (($timeZoneAbs < 10) ? '0' : '') . $timeZoneAbs . ':00';
    }

    public static function generateAwbNumber(): string
    {
        return 'P' . strval(rand(10, 99)) . substr(strval(time()), 2);
    }

    /**
     * @param string $data
     * @return mixed
     */
    public static function jsonDecode(string $data)
    {
        $jsonEncoder = new JsonEncoder();
        return $jsonEncoder->decode($data, 'json');
    }

    /**
     * @param string $serviceCode
     */
    public static function validateServiceCode(string $serviceCode)
    {
        if (!in_array($serviceCode, ['C', 'Q'])) {
            throw new BadRequestHttpException('The value of the service code parameter must be one of the following: "C" or "Q".');
        }
    }

    /**
     * @param mixed $data
     * @param string $type
     * @return mixed
     * @throws ExceptionInterface
     */
    public static function arrayToObject($data, string $type)
    {
        $serializer = new Serializer([new DateTimeNormalizer(), new ObjectNormalizer(null, null, null, new ReflectionExtractor())]);
        return $serializer->denormalize($data, $type);
    }

    /**
     * @param ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface|null $e
     * @param array $content
     * @param string $message
     */
    public static function processHttpResponse($e, array $content, string $message)
    {
        if (isset($e)) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if (!isset($content[Literal::STATUS])) {
            throw new BadRequestHttpException($message);
        }

        $status = $content[Literal::STATUS];

        if ($status !== Literal::SUCCESS) {
            switch ($status) {
                case 'fail':
                    if (isset($content[Literal::DATA])) {
                        $message = json_encode($content[Literal::DATA]);
                    }

                    break;
                case 'error':
                    if (isset($content[Literal::MESSAGE])) {
                        $message = $content[Literal::MESSAGE];
                    }

                    break;
            }

            throw new BadRequestHttpException($message);
        }
    }

    /**
     * @param DateTime $dateTime
     * @return string
     */
    public static function getMaxCollectionDate(DateTime $dateTime): string
    {
        $sum1 = 2;
        $dia = $dateTime->format('w');

        switch ($dia) {
            case '4':
            case '5':
                $sum1 = 4;
                break;
            case '6':
                $sum1 = 3;
                break;
        }

        $dateTime->modify("+{$sum1} days");
        return $dateTime->format('d/m/Y');
    }

    /**
     * @return float
     */
    public static function microtime(): float
    {
        list($usec, $sec) = explode(' ', microtime());
        return floatval($usec) + floatval($sec);
    }

    /**
     * @param HttpClientInterface $httpClient
     * @param string $method
     * @param string $url
     * @param User|null $user
     * @param string|array $payload
     * @param array $result
     * @param bool $checkData
     * @param string $serviceName
     * @param array|null $headers
     * @param bool $jsonContentType
     * @return array
     */
    public static function httpRequest(HttpClientInterface $httpClient, string $method, string $url, ?User $user, string|array $payload, array $result = [Literal::SUCCESS => false], bool $checkData = true, string $serviceName = '', array $headers = null, bool $jsonContentType = false): array
    {
        try {
            $options = [
                (($method === 'GET') ? 'query' : ($jsonContentType ? 'json' : 'body')) => $payload
            ];

            if (!is_null($user)) {
                $options['auth_bearer'] = $user->getToken();
            }

            if (!is_null($headers)) {
                $options['headers'] = $headers;
            }

            $response = $httpClient->request($method, $url, $options);
            $content = $response->toArray(false);
        } catch (ClientExceptionInterface|DecodingExceptionInterface|RedirectionExceptionInterface|ServerExceptionInterface|TransportExceptionInterface $e) {
        }

        $messagePrefix = (empty($serviceName) ? '' : "$serviceName: ");

        if (isset($e)) {
            $result[Literal::MESSAGE] = "$messagePrefix{$e->getMessage()}";
            return $result;
        }

        if (!isset($content[Literal::STATUS])) {
            return $result;
        }

        $status = $content[Literal::STATUS];

        if ($status !== Literal::SUCCESS) {
            switch ($status) {
                case 'fail':
                    if (isset($content[Literal::DATA])) {
                        $content[Literal::DATA][Literal::MESSAGE] = $messagePrefix . ($content[Literal::DATA][Literal::MESSAGE] ?? 'Request validation failed.');
                        $result[Literal::MESSAGE] = json_encode($content[Literal::DATA]);
                    }

                    break;
                case 'error':
                    if (isset($content[Literal::MESSAGE])) {
                        $result[Literal::MESSAGE] = "$messagePrefix{$content[Literal::MESSAGE]}";
                    }

                    break;
            }

            return $result;
        }

        if ($checkData && !isset($content[Literal::DATA])) {
            return $result;
        }

        $result[Literal::SUCCESS] = true;
        $result['content'] = $content;
        return $result;
    }

    /**
     * @param mixed $data
     * @return string
     */
    public static function jsonEncode($data): string
    {
        $jsonEncoder = new JsonEncoder();
        return $jsonEncoder->encode($data, 'json');
    }

    public static function createDbh(): ?PDO
    {
        $dbUrl = $_ENV['DATABASE_URL'];
        $dbUrlPart = explode('://', $dbUrl);

        if ($dbUrlPart[0] !== 'mssql') {
            return null;
        }

        $dbUrlPart1 = $dbUrlPart[1];
        $dbUrlPart2 = explode('/', $dbUrlPart1);
        $database = $dbUrlPart2[1];
        $dbUrlPart3 = explode('@', $dbUrlPart2[0]);
        $dbUrlPart4 = explode(':', $dbUrlPart3[1]);
        $host = $dbUrlPart4[0];
        $port = ($dbUrlPart4[1] ?? null);
        $dbUrlPart5 = explode(':', $dbUrlPart3[0]);
        $username = $dbUrlPart5[0];
        $passwd = $dbUrlPart5[1];
        $server = $host . (is_null($port) ? '' : ",$port");
        return new PDO("sqlsrv:Server=$server;Database=$database;TrustServerCertificate=1", $username, $passwd);
    }
}
