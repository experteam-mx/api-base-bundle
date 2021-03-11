<?php

namespace Experteam\ApiBaseBundle\Util;

use DateTime;
use DateTimeZone;
use Exception;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Exception\ExceptionInterface;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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
}