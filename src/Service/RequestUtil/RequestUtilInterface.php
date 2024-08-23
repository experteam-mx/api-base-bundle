<?php

namespace Experteam\ApiBaseBundle\Service\RequestUtil;

interface RequestUtilInterface
{
    /**
     * @param string $data
     * @param string $model
     * @param string[]|null $groups
     * @return object
     */
    public function validate(string $data, string $model, ?array $groups = null): object;
}
