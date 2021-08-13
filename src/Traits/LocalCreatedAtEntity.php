<?php

namespace Experteam\ApiBaseBundle\Traits;

use DateTime;
use Doctrine\ORM\Mapping as ORM;

trait LocalCreatedAtEntity
{
    /**
     * @var DateTime
     * @ORM\Column(type="datetime")
     */
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