<?php

namespace Experteam\ApiBaseBundle\Type;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\BigIntType;

class CustomBigIntType extends BigIntType
{
    public function convertToPHPValue($value, AbstractPlatform $platform): ?int
    {
        return (is_null($value) ? null : intval($value));
    }
}
