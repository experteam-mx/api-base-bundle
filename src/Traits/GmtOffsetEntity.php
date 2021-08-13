<?php

namespace Experteam\ApiBaseBundle\Traits;

use Doctrine\ORM\Mapping as ORM;

trait GmtOffsetEntity
{
    /**
     * @var string
     * @ORM\Column(type="string", length="25")
     */
    private $gmtOffset;

    public function getGmtOffset(): ?string
    {
        return $this->gmtOffset;
    }

    public function setGmtOffset(string $gmtOffset): self
    {
        $this->gmtOffset = $gmtOffset;

        return $this;
    }
}