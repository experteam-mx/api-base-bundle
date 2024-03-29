<?php

namespace Experteam\ApiBaseBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

trait GmtOffsetEntity
{
    /**
     * @var string
     * @ORM\Column(type="string", length=50)
     */
    #[ORM\Column(type: Types::STRING, length: 50)]
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
