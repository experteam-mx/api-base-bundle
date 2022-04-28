<?php

namespace Experteam\ApiBaseBundle\Service\ChangeSet;

use Doctrine\ORM\EntityManagerInterface;
use Experteam\ApiBaseBundle\Traits\ChangeSetEntity;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

class ChangeSet implements ChangeSetInterface
{
    const FIELD = 'field';
    const GROUP = 'group';

    /**
     * @var EntityManagerInterface
     */
    protected $manager;

    /**
     * @var SerializerInterface
     */
    protected $serializer;

    public function __construct(EntityManagerInterface $manager, SerializerInterface $serializer)
    {
        $this->manager = $manager;
        $this->serializer = $serializer;
    }

    /**
     * @param object $object
     * @param array $options
     */
    public function processEntity(object $object, array $options = [])
    {
        $class = get_class($object);

        if (!array_key_exists($class, $options))
            return;

        if (!in_array(ChangeSetEntity::class, class_uses($object)))
            return;

        $field = $options[$class][self::FIELD] ?? null;
        $group = $options[$class][self::GROUP] ?? 'read';
        $_object = $object;
        $cascaded = false;

        if (!is_null($field)) {
            $method = 'get' . ucfirst($field);
            if (!method_exists($object, $method))
                return;

            $_object = $object->$method();

            $objectMetadata = $this->manager->getClassMetadata(get_class($object));
            $cascaded = in_array('persist', $objectMetadata->associationMappings[$field]['cascade'] ?? []);
        }

        $uow = $this->manager->getUnitOfWork();
        $_objectMetadata = $this->manager->getClassMetadata(get_class($_object));

        if ($cascaded)
            $this->manager->persist($_object);

        $uow->computeChangeSet($_objectMetadata, $_object);
        $changes = $uow->getEntityChangeSet($_object);

        $object->setChangeSet($this->serializeWithCircularRefHandler($changes, [$group]));
    }

    /**
     * @param $object
     * @param array|null $groups
     * @param string $format
     * @return string
     */
    protected function serializeWithCircularRefHandler($object, array $groups = null, string $format = 'json'): string
    {
        return $this->serializer->serialize($object, $format, array_merge(
            [
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => function ($object) {
                    return (method_exists($object, 'getId') ? $object->getId() : null);
                }
            ],
            !is_null($groups) ? [AbstractNormalizer::GROUPS => $groups] : []
        ));
    }
}