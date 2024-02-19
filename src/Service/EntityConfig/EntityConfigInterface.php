<?php

namespace Experteam\ApiBaseBundle\Service\EntityConfig;

interface EntityConfigInterface
{
    public function isActive(string $entity, int $id, bool $getModelDataFromSession = true, array $modelData = []): bool;

    public function getActives(string $entity, bool $getModelDataFromSession = true, array $modelData = []): array;
}
