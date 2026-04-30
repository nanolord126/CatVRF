<?php

declare(strict_types=1);

namespace Modules\Contraindications\Domain\Repositories;

use Modules\Contraindications\Domain\Entities\ProductComposition;

interface ProductCompositionRepositoryInterface
{
    public function findByComposable(string $composableType, int $composableId): ?ProductComposition;

    public function save(ProductComposition $composition): void;

    public function delete(int $id): void;
}
