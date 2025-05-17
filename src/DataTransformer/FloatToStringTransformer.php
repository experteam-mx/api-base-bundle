<?php

namespace Experteam\ApiBaseBundle\DataTransformer;

use Symfony\Component\Form\DataTransformerInterface;

class FloatToStringTransformer implements DataTransformerInterface
{
    public function transform(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        return strval($value);
    }

    public function reverseTransform(mixed $value): ?float
    {
        if (is_null($value)) {
            return null;
        }

        return floatval($value);
    }
}
