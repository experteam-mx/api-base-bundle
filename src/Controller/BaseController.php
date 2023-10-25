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
use Symfony\Contracts\Translation\TranslatorInterface;

class BaseController extends AbstractFOSRestController
{
    /**
     * @var ParamInterface
     */
    protected ParamInterface $param;

    /**
     * @var RequestUtilInterface
     */
    protected RequestUtilInterface $requestUtil;

    /**
     * @var HttpClientInterface
     */
    protected HttpClientInterface $httpClient;

    /**
     * @var Serializer
     */
    protected Serializer $serializer;

    /**
     * @var TranslatorInterface
     */
    protected TranslatorInterface $translator;

    /**
     * @param ParamInterface $param
     * @param RequestUtilInterface $requestUtil
     * @param HttpClientInterface $httpClient
     * @param TranslatorInterface $translator
     */
    public function __construct(ParamInterface $param, RequestUtilInterface $requestUtil, HttpClientInterface $httpClient, TranslatorInterface $translator)
    {
        $this->param = $param;
        $this->requestUtil = $requestUtil;
        $this->httpClient = $httpClient;
        $this->translator = $translator;
        $this->serializer = new Serializer([new ObjectNormalizer()]);
    }

    /**
     * @param string $data
     * @return mixed
     */
    protected function jsonDecode(string $data): mixed
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
    protected function jsonEncode(mixed $data): string
    {
        $jsonEncoder = new JsonEncoder();
        return $jsonEncoder->encode($data, 'json');
    }

    protected function denyAccessUnlessGrantedCustom(mixed $attribute, mixed $subject = null, string $message = 'Forbidden.'): void
    {
        $this->denyAccessUnlessGranted($attribute, $subject, $message);
    }
}
