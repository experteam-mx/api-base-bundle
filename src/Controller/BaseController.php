<?php

namespace Experteam\ApiBaseBundle\Controller;

use Experteam\ApiBaseBundle\Security\User;
use Experteam\ApiBaseBundle\Service\Param\ParamInterface;
use Experteam\ApiBaseBundle\Service\RequestUtil\RequestUtilInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BaseController extends AbstractFOSRestController
{
    /**
     * @var ParamInterface
     */
    protected $param;

    /**
     * @var RequestUtilInterface
     */
    protected $requestUtil;

    /**
     * @var HttpClientInterface
     */
    protected $httpClient;

    /**
     * @var Serializer
     */
    protected Serializer $serializer;

    /**
     * @param ParamInterface $param
     * @param RequestUtilInterface $requestUtil
     * @param HttpClientInterface $httpClient
     */
    public function __construct(ParamInterface $param, RequestUtilInterface $requestUtil, HttpClientInterface $httpClient)
    {
        $this->param = $param;
        $this->requestUtil = $requestUtil;
        $this->httpClient = $httpClient;
        $this->serializer = new Serializer([new ObjectNormalizer()]);
    }

    /**
     * @param string $data
     * @return mixed
     */
    protected function jsonDecode(string $data)
    {
        $jsonEncoder = new JsonEncoder();
        return $jsonEncoder->decode($data, 'json');
    }

    /**
     * @return array
     */
    protected function validateSession(): array
    {
        $session = [];

        if (!isset($_ENV['APP_SECURITY_ACCESS_ROLE']) || $_ENV['APP_SECURITY_ACCESS_ROLE'] !== 'IS_ANONYMOUS') {
            /** @var User $user */
            $user = $this->getUser();
            $session = $user->getSession();

            if (!isset($session)) {
                throw new BadRequestHttpException('You do not have an active session.');
            }
        }

        return $session;
    }

    /**
     * @param mixed $data
     * @return string
     */
    protected function jsonEncode($data): string
    {
        $jsonEncoder = new JsonEncoder();
        return $jsonEncoder->encode($data, 'json');
    }

    protected function denyAccessUnlessGrantedCustom(mixed $attribute, mixed $subject = null, string $message = 'Forbidden.'): void
    {
        $this->denyAccessUnlessGranted($attribute, $subject, $message);
    }
}
