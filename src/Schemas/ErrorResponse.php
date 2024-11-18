<?php

namespace Experteam\ApiBaseBundle\Schemas;

use OpenApi\Attributes as OA;

class ErrorResponse
{
    #[OA\Property(type: 'string', example: 'error')]
    public $status;

    #[OA\Property(type: 'string', example: 'Error message')]
    public $message;
}
