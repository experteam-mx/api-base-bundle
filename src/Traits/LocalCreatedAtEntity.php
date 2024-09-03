<?php

namespace Experteam\ApiBaseBundle\Traits;

use DateTime;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait LocalCreatedAtEntity
{
    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    protected $localCreatedAt;

    public function getLocalCreatedAt(): ?DateTime
    {
        return $this->localCreatedAt;
    }

    public function setLocalCreatedAt(DateTime $localCreatedAt): self
    {
        $this->localCreatedAt = $localCreatedAt;

        return $this;
    }


}