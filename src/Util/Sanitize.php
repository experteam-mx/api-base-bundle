<?php

namespace Experteam\ApiBaseBundle\Util;

class Sanitize
{
    const ALLOWS = [' ', '!', '@', '<', '>', '$', '#', '%', '&', '*', 'ñ', 'Ñ', '(', ')', '.', ',', '-'];

    /**
     * @param string $value
     * @return string
     */
    public static function integer(string $value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_INT);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function numeric(string $value)
    {
        return filter_var($value, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function email(string $value)
    {
        return filter_var($value, FILTER_SANITIZE_EMAIL);
    }

    /**
     * @param string $value
     * @return string
     */
    public static function string(string $value)
    {
        $allows = implode('', self::ALLOWS);
        return preg_replace("/[^A-Za-z0-9$allows]/u", '', $value);
    }
}