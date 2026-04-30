<?php

declare(strict_types=1);

namespace App\Shared\Domain;

abstract class Entity
{
    protected readonly ?int $id = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function equals(self $other): bool
    {
        return static::class === get_class($other)
            && $this->id !== null
            && $this->id === $other->id;
    }
}
