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
     * @return array|string
     */
    public function findOneByName(string $name);

}