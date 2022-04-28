<?php

namespace Experteam\ApiBaseBundle\Service\ChangeSet;

interface ChangeSetInterface
{
    /**
     * @param object $object
     * @param array $options
     */
    public function processEntity(object $object, array $options = []);
}