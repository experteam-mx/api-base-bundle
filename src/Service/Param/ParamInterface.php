<?php

namespace Experteam\ApiBaseBundle\Service\Param;

interface ParamInterface
{
    /**
     * @param array $values
     * @return array|string
     */
    public function findByName(array $values);

    /**
     * @param string $name
     * @return string
     */
    public function findOneByName(string $name);

    /**
     * @param array $parameters
     */
    public function load(array $parameters);
}