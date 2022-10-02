<?php

namespace Experteam\ApiBaseBundle\Service\Transaction;

use Redis;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Uid\Uuid;

class Transaction implements TransactionInterface
{
    /**
     * @var Redis
     */
    private $redis;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var Request
     */
    private $request;

    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @var string
     */
    private $key;

    /**
     * @param Redis $redis
     * @param ParameterBagInterface $parameterBag
     * @param RequestStack $requestStack
     * @param SerializerInterface $serializer
     */
    public function __construct(Redis $redis, ParameterBagInterface $parameterBag, RequestStack $requestStack, SerializerInterface $serializer)
    {
        $this->redis = $redis;
        $this->parameterBag = $parameterBag;
        $this->request = $requestStack->getCurrentRequest();
        $this->serializer = $serializer;
    }

    /**
     * @return string
     */
    private function getRedisKey(): string
    {
        return sprintf('%s.transaction:%s', $this->parameterBag->get('app.prefix'), $this->getId());
    }

    /**
     * @param bool $justReturn
     * @return string|null
     */
    public function getId(bool $justReturn = false): ?string
    {
        if ($justReturn) {
            return $this->key;
        }

        if (is_null($this->key)) {
            $this->key = ((is_null($this->request) || !$this->request->headers->has('Transaction-Id')) ? Uuid::v1()->toRfc4122() : $this->request->headers->get('Transaction-Id'));
        }

        return $this->key;
    }

    /**
     * @param string $field
     * @param $value
     */
    public function saveToRedis(string $field, $value)
    {
        $redisKey = $this->getRedisKey();
        $now = date_create()->format('YmdHisv');
        $value = $this->serializer->serialize($value, 'json');
        $this->redis->hSet($redisKey, "{$now}_$field", $value);
        $this->redis->expire($redisKey, $this->parameterBag->get('app.transaction.ttl.sec'));
    }

    /**
     * @param string $transactionId
     * @return array
     */
    public function getFromRedis(string $transactionId): array
    {
        $this->key = $transactionId;
        $redisKey = $this->getRedisKey();

        return array_map(function ($v) {
            return json_decode($v);
        }, ($this->redis->hGetAll($redisKey) ?? []));
    }
}
