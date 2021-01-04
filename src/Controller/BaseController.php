<?php

namespace Experteam\ApiBaseBundle\Controller;

use Experteam\ApiBaseBundle\Service\Param\ParamInterface;
use Experteam\ApiBaseBundle\Service\RequestUtil\RequestUtilInterface;
use FOS\RestBundle\Controller\AbstractFOSRestController;
use Symfony\Component\Serializer\Encoder\JsonEncoder;

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
     * @param ParamInterface $param
     * @param RequestUtilInterface $requestUtil
     */
    public function __construct(ParamInterface $param, RequestUtilInterface $requestUtil)
    {
        $this->param = $param;
        $this->requestUtil = $requestUtil;
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
}