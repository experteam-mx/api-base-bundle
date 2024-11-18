<?php

namespace Experteam\ApiBaseBundle\Schemas;

use OpenApi\Attributes as OA;

class SuccessResponse
{
    #[OA\Property(type: 'string', example: 'success')]
    public $status;

    #[OA\Property(type: 'array', items: new OA\Items(), example: [])]
    public $data;
}
