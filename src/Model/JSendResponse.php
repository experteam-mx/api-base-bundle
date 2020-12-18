<?php

namespace Experteam\ApiBaseBundle\Model;

use OpenApi\Annotations as OA;

class JSendResponse
{
    /**
     * @OA\Property(type="string", description="Status.")
     */
    public $status;

    /**
     * @OA\Property(type="string", description="Data.")
     */
    public $data;

    /**
     * @OA\Property(type="string", description="Message.")
     */
    public $message;

    /**
     * @OA\Property(type="string", description="Code.")
     */
    public $code;
}