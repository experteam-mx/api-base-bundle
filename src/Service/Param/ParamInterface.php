<?php

namespace Experteam\ApiBaseBundle\Service\Param;

use Exception;

interface ParamInterface
{
    /**
     * @param array $values
     * @param string|null $modelType
     * @param string|null $modelId
     * @return array|string
     * @throws Exception
     */
    public function findByName(array $values, string $modelType = null, string $modelId = null);

    /**
     * @param string $name
     * @param string|null $modelType
     * @param string|null $modelId
     * @return array|string
     * @throws Exception
     */
    public function findOneByName(string $name, string $modelType = null, string $modelId = null);

}