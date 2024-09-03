<?php

namespace Experteam\ApiBaseBundle\Traits;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait LocalUpdatedAtEntity
{
    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $localUpdatedAt;

    public $localUpdatedAtAssigned = false;

    public function getLocalUpdatedAt(): ?DateTime
    {
        return $this->localUpdatedAt;
    }

    public function setLocalUpdatedAt(DateTime $localUpdatedAt): self
    {
        $this->localUpdatedAt = $localUpdatedAt;

        return $this;
    }
}