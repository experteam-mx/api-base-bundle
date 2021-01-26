<?php

namespace Experteam\ApiBaseBundle\Model;

use OpenApi\Annotations as OA;

class JSendResponse
{
    /**
     * @OA\Property(type="string", description="Status (success|fail|error)", example="success|fail|error")
     */
    public $status;

    /**
     * @OA\Property(type="string", description="Data.")
     */
    public $data;

    /**
     * @OA\Property(type="string", description="Message (only if status is fail or error)")
     */
    public $message;

    /**
     * @OA\Property(type="string", description="Code (only if status is fail or error).")
     */
    public $code;
}