<?php

namespace Experteam\ApiBaseBundle\Schemas;

use OpenApi\Annotations as OA;

class FailResponse
{
    /**
     * @OA\Property(type="string", example="fail")
     */
    public $status;

    /**
     * @OA\Property(type="object", example={"field": "Validation message"})
     */
    public $data;
}