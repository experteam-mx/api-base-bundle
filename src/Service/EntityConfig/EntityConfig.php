<?php

namespace Experteam\ApiBaseBundle\Service\EntityConfig;

use Experteam\ApiBaseBundle\Security\User;
use Redis;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class EntityConfig implements EntityConfigInterface
{
    const PRODUCT = 'product';
    const EXTRACHARGE = 'extraCharge';
    const SUPPLY = 'supply';
    const ACCOUNT = 'account';
    const SYSTEM = 'system';

    const MODEL_TYPES = [
        'GLOBAL' => 'GLOBAL',
        'COUNTRY' => 'Country',
        'COMPANY_COUNTRY' => 'CompanyCountry',
        'LOCATION' => 'Location',
        'LOCATION_EMPLOYEE' => 'LocationEmployee',
        'INSTALLATION' => 'Installation'
    ];

    protected TokenStorageInterface $tokenStorage;

    private Redis $redis;

    public function __construct(TokenStorageInterface $tokenStorage, Redis $redis)
    {
        $this->tokenStorage = $tokenStorage;
        $this->redis = $redis;
    }

    public function isActive(string $entity, int $id, bool $getModelDataFromSession = true, array $modelData = []): bool
    {
        $actives = $this->getActives($entity, $getModelDataFromSession, $modelData);
        return in_array($id, $actives);
    }

    public function getActives(string $entity, bool $getModelDataFromSession = true, array $modelData = []): array
    {
        if ($getModelDataFromSession) {
            /** @var User $user */
            $user = $this->tokenStorage->getToken()
                ->getUser();

            $session = $user->getSession();

            if (is_null($session)) {
                throw new BadRequestHttpException(sprintf('You do not have an active session for get %s actives', $entity));
            }

            $modelData = [
                [self::MODEL_TYPES['COUNTRY'], ($session['country_id'] ?? null)],
                [self::MODEL_TYPES['COMPANY_COUNTRY'], ($session['company_country_id'] ?? null)],
                [self::MODEL_TYPES['LOCATION'], ($session['location_id'] ?? null)],
                [self::MODEL_TYPES['LOCATION_EMPLOYEE'], ($session['location_employee_id'] ?? null)],
                [self::MODEL_TYPES['INSTALLATION'], ($session['installation_id'] ?? null)]
            ];
        }

        $actives = [];
        $inactives = [];
        $modelData = array_merge([[self::MODEL_TYPES['GLOBAL'], 0]], $modelData);

        foreach ($modelData as [$modelType, $modelId]) {
            if (is_null($modelId)) {
                continue;
            }

            $levelConfigured = $this->redis->hGet("companies.{$entity}Entity:$modelType", strval($modelId));

            if (is_null($levelConfigured) || $levelConfigured === false) {
                continue;
            }

            $levelConfigured = json_decode($levelConfigured, true);

            $levelActives = array_keys(array_filter($levelConfigured, function ($v) {
                return $v;
            }));

            $actives = (empty($actives) ? $levelActives : array_intersect($actives, $levelActives));

            $levelInactives = array_keys(array_filter($levelConfigured, function ($v) {
                return !$v;
            }));

            $inactives = (empty($inactives) ? $levelInactives : array_intersect($inactives, $levelInactives));
        }

        return array_diff($actives, $inactives);
    }
}
