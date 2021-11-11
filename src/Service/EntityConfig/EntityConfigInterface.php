<?php

namespace Experteam\ApiBaseBundle\Service\EntityConfig;

interface EntityConfigInterface
{
    /**
     * @param string $entity
     * @param int $id
     * @return bool
     */
    public function isActive(string $entity, int $id): bool;

    /**
     * @param string $entity
     * @return array
     */
    public function getActives(string $entity): array;
}