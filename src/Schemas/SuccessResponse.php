<?php

namespace Experteam\ApiBaseBundle\Schemas;

use OpenApi\Annotations as OA;

class SuccessResponse
{
    /**
     * @OA\Property(type="string", example="success")
     */
    public $status;

    /**
     * @OA\Property(type="array", @OA\Items(), example={})
     */
    public $data;
}