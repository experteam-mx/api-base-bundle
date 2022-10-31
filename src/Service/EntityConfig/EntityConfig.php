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

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Redis
     */
    private $redis;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param Redis $redis
     */
    public function __construct(TokenStorageInterface $tokenStorage, Redis $redis)
    {
        $this->tokenStorage = $tokenStorage;
        $this->redis = $redis;
    }

    /**
     * @param string $entity
     * @param int $id
     * @return bool
     */
    public function isActive(string $entity, int $id): bool
    {
        $actives = $this->getActives($entity);
        return in_array($id, $actives);
    }

    /**
     * @param string $entity
     * @return array
     */
    public function getActives(string $entity): array
    {
        /** @var User $user */
        $user = $this->tokenStorage->getToken()->getUser();
        $session = $user->getSession();

        if (is_null($session)) {
            throw new BadRequestHttpException(sprintf('You do not have an active session for get %s actives', $entity));
        }

        $prefix = "companies.{$entity}Entity:";

        $fromRedis = [
            [$prefix . self::MODEL_TYPES['GLOBAL'], 0],
            [$prefix . self::MODEL_TYPES['COUNTRY'], ($session['country_id'] ?? null)],
            [$prefix . self::MODEL_TYPES['COMPANY_COUNTRY'], ($session['company_country_id'] ?? null)],
            [$prefix . self::MODEL_TYPES['LOCATION'], ($session['location_id'] ?? null)],
            [$prefix . self::MODEL_TYPES['LOCATION_EMPLOYEE'], ($session['location_employee_id'] ?? null)],
            [$prefix . self::MODEL_TYPES['INSTALLATION'], ($session['installation_id'] ?? null)]
        ];

        $actives = [];
        $inactives = [];

        foreach ($fromRedis as [$key, $id]) {
            $levelConfigured = $this->redis->hGet($key, (string)$id);

            if ($levelConfigured === false) {
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
