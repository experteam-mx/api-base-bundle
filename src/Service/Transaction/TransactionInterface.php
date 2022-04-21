<?php

namespace Experteam\ApiBaseBundle\Service\Transaction;

interface TransactionInterface
{
    /**
     * @param bool $justReturn
     * @return string|null
     */
    public function getId(bool $justReturn = false): ?string;

    /**
     * @param string $field
     * @param $value
     */
    public function saveToRedis(string $field, $value);

    /**
     * @param string $transactionId
     * @return array
     */
    public function getFromRedis(string $transactionId): array;
}
