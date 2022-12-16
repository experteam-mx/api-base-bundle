<?php

namespace Experteam\ApiBaseBundle\Service\Localization;

use DateTimeZone;
use Exception;
use Experteam\ApiBaseBundle\Security\User;
use Experteam\ApiBaseBundle\Traits\GmtOffsetEntity;
use Experteam\ApiBaseBundle\Traits\LocalCreatedAtEntity;
use Experteam\ApiBaseBundle\Traits\LocalUpdatedAtEntity;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class Localization implements LocalizationInterface
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var ParameterBagInterface
     */
    private $parameterBag;

    /**
     * @var TokenStorageInterface
     */
    private $tokenStorage;

    /**
     * @var string
     */
    private $defaultTimezone;

    /**
     * Localization constructor.
     *
     * @param ContainerInterface $container
     * @param ParameterBagInterface $parameterBag
     * @param TokenStorageInterface $tokenStorage
     */
    public function __construct(ContainerInterface $container, ParameterBagInterface $parameterBag, TokenStorageInterface $tokenStorage)
    {
        $this->container = $container;
        $this->parameterBag = $parameterBag;
        $this->tokenStorage = $tokenStorage;
    }

    /**
     * @return string
     * @throws Exception
     */
    public function getDefaultTimezone(): string
    {
        if (is_null($this->defaultTimezone)) {
            $timezoneConfig = $this->parameterBag->get('experteam_api_base.timezone');
            $this->defaultTimezone = $timezoneConfig['default'];

            $redisConfig = $timezoneConfig['redis'] ?? null;
            if (!is_null($redisConfig)) {
                $token = $this->tokenStorage->getToken();
                if (!is_null($token)) {
                    /** @var User $user */
                    $user = $token->getUser();
                    $session = $user->getSession();
                    if (is_null($session))
                        throw new Exception('There is no active session to retrieve the timezone from redis');

                    $redisClient = $this->container->get('api_redis.client');
                    $key = $redisConfig['key'];
                    $id = $session[$redisConfig['id']];
                    $data = $redisClient->hget($key, $id, null, true);

                    if (is_null($data))
                        throw new Exception("Error retrieving timezone from redis with key: $key, id: $id");

                    $field = $redisConfig['field'];
                    $timezone = $data[$field] ?? null;
                    if (is_null($timezone))
                        throw new Exception("There is no $field field in the redis data for key: $key, id: $id");

                    $this->defaultTimezone = $timezone;
                }
            }
        }

        return $this->defaultTimezone;
    }

    /**
     * @param object $object
     * @throws Exception
     */
    public function processGmtOffset(object $object)
    {
        if (in_array(GmtOffsetEntity::class, class_uses($object)) && is_null($object->getGmtOffset()))
            $object->setGmtOffset($this->getDefaultTimezone());
    }

    /**
     * @param object $object
     * @throws Exception
     */
    public function processLocalCreatedAt(object $object)
    {
        if (in_array(LocalCreatedAtEntity::class, class_uses($object)) && is_null($object->getLocalCreatedAt()))
            $object->setLocalCreatedAt(date_create('now', new DateTimeZone($this->getDefaultTimezone())));
    }

    /**
     * @param object $object
     * @throws Exception
     */
    public function processLocalUpdatedAt(object $object)
    {
        if (in_array(LocalUpdatedAtEntity::class, class_uses($object)) && !$object->localUpdatedAtAssigned)
            $object->setLocalUpdatedAt(date_create('now', new DateTimeZone($this->getDefaultTimezone())));
    }
}