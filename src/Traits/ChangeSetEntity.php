<?php

namespace Experteam\ApiBaseBundle\Traits;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use OpenApi\Attributes\Property;

trait ChangeSetEntity
{
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
    #[Property(type: Types::STRING)]
    #[ORM\Column(type: Types::TEXT, nullable:true)]
    private $changeSet;

    public function getChangeSet(): ?string
    {
        return $this->changeSet;
    }

    public function setChangeSet(?string $changeSet): self
    {
        $this->changeSet = $changeSet;

        return $this;
    }
}