<?php

namespace Experteam\ApiBaseBundle\Service\Localization;

interface LocalizationInterface
{
    /**
     * @return string
     */
    public function getDefaultTimezone(): string;

    /**
     * @param object $object
     */
    public function processGmtOffset(object $object);

    /**
     * @param object $object
     */
    public function processLocalCreatedAt(object $object);

    /**
     * @param object $object
     */
    public function processLocalUpdatedAt(object $object);

}