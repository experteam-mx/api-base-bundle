<?php

namespace Experteam\ApiBaseBundle\Service\EntityConfig;

use Experteam\ApiBaseBundle\Security\User;
use Predis\Client;
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
        'INSTALLATION' => 'Installation',
    ];

    /**
     * @var TokenStorageInterface
     */
    protected $tokenStorage;

    /**
     * @var Client
     */
    private $predisClient;

    /**
     * @param TokenStorageInterface $tokenStorage
     * @param Client $predisClient
     */
    public function __construct(TokenStorageInterface $tokenStorage, Client $predisClient)
    {
        $this->tokenStorage = $tokenStorage;
        $this->predisClient = $predisClient;
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
        if (is_null($session))
            throw new BadRequestHttpException(sprintf('You do not have an active session for get %s actives', $entity));

        $prefix = "companies.{$entity}Entity:";

        $fromRedis = [
            [$prefix . self::MODEL_TYPES['GLOBAL'], 0],
            [$prefix . self::MODEL_TYPES['COUNTRY'], $session['country_id'] ?? null],
            [$prefix . self::MODEL_TYPES['COMPANY_COUNTRY'], $session['company_country_id'] ?? null],
            [$prefix . self::MODEL_TYPES['LOCATION'], $session['location_id'] ?? null],
            [$prefix . self::MODEL_TYPES['LOCATION_EMPLOYEE'], $session['location_employee_id'] ?? null],
            [$prefix . self::MODEL_TYPES['INSTALLATION'], $session['installation_id'] ?? null],
        ];

        $actives = [];
        foreach ($fromRedis as [$key, $id]) {
            $levelActives = $this->predisClient->hget($key, $id);

            if (is_null($levelActives))
                continue;

            $levelActives = json_decode($levelActives, false);

            $actives = empty($actives) ? $levelActives : array_intersect($actives, $levelActives);
        }

        return $actives;
    }


}