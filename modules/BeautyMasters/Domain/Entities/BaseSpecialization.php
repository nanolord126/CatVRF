<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Domain\Entities;

use Modules\BeautyMasters\Domain\Entities\SpecializationLevel;

abstract readonly class BaseSpecialization
{
    public function __construct(
        public int $id,
        public int $masterId,
        public string $specialization,
        public SpecializationLevel $level,
        public ?string $notes,
        public \DateTimeImmutable $createdAt,
        public ?\DateTimeImmutable $updatedAt,
    ) {
    }
}
