<?php

namespace Experteam\ApiBaseBundle\Traits;

use Doctrine\ORM\Mapping as ORM;

trait ChangeSetEntity
{
    /**
     * @var string
     * @ORM\Column(type="text", nullable=true)
     */
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