<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\Repositories;

use App\Domains\Shared\Size\Entities\SizeChart;
use App\Domains\Shared\Size\ValueObjects\Gender;
use App\Domains\Shared\Size\ValueObjects\Region;
use App\Domains\Shared\Size\ValueObjects\SizeSystem;
use Illuminate\Support\Collection;

interface SizeChartRepositoryInterface
{
    public function save(SizeChart $chart): void;

    public function findByUuid(string $uuid): ?SizeChart;

    public function findByBrand(string $brand): Collection;

    public function findByBrandGenderRegion(string $brand, Gender $gender, Region $region, ?string $category = null): Collection;

    public function findByBrandGenderRegionSystem(string $brand, Gender $gender, Region $region, SizeSystem $system): Collection;

    public function findByCategory(string $category): Collection;

    public function findByGender(Gender $gender): Collection;

    public function findByRegion(Region $region): Collection;

    public function findBySystem(SizeSystem $system): Collection;

    public function findActive(): Collection;

    public function delete(string $uuid): bool;

    public function getStatistics(): array;

    public function countByBrand(): array;

    public function countByGender(): array;

    public function countByCategory(): array;
}
