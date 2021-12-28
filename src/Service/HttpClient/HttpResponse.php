<?php

namespace Experteam\ApiBaseBundle\Service\HttpClient;

use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Validator\Context\ExecutionContextInterface;

class HttpResponse
{
    const SUCCESS = 'success';
    const FAIL = 'fail';
    const ERROR = 'error';

    /**
     * @var string|null
     * @Assert\NotBlank(message="The status is required.")
     * @Assert\Type("string")
     * @Assert\Choice(callback="getStatuses")
     */
    public $status;

    /**
     * @var string|null
     * @Assert\Type(
     *     type="string",
     *     message="The message is not of type string."
     * )
     */
    public $message;

    /**
     * @var array|null
     * @Assert\Type(
     *     type="array",
     *     message="The data is not of type array."
     * )
     */
    public $data;

    public function __construct(array $response = [])
    {
        foreach ($response as $key => $value)
            if (property_exists($this, $key))
                $this->$key = $value;
    }

    public static function getStatuses(): array
    {
        return [self::SUCCESS, self::FAIL, self::ERROR];
    }

    /**
     * @Assert\Callback
     */
    public function validate(ExecutionContextInterface $context)
    {
        if ($this->status == self::ERROR && is_null($this->message))
            $context->buildViolation(sprintf('If status is %s the message key is required.', self::ERROR))
                ->atPath('message')
                ->addViolation();

        if (in_array($this->status, [self::SUCCESS, self::FAIL]) && is_null($this->data))
            $context->buildViolation(sprintf('If status is %s the data key is required.', self::SUCCESS))
                ->atPath('data')
                ->addViolation();
    }
}