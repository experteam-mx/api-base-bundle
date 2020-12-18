<?php

namespace Experteam\ApiBaseBundle\Service\RequestUtil;

interface RequestUtilInterface
{
    /**
     * @param string $data
     * @param string $model
     * @return object
     */
    public function validate(string $data, string $model): object;
}