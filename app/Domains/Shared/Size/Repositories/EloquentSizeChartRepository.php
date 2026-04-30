<?php

declare(strict_types=1);

namespace App\Domains\Shared\Size\Repositories;

use App\Domains\Shared\Size\Entities\SizeChart;
use App\Domains\Shared\Size\ValueObjects\Gender;
use App\Domains\Shared\Size\ValueObjects\Region;
use App\Domains\Shared\Size\ValueObjects\SizeSystem;
use App\Models\SizeChart as SizeChartModel;
use Illuminate\Support\Collection;

final class EloquentSizeChartRepository implements SizeChartRepositoryInterface
{
    public function save(SizeChart $chart): void
    {
        $model = SizeChartModel::where('uuid', $chart->getUuid())->first();

        if ($model) {
            $model->update([
                'brand' => $chart->getBrand(),
                'gender' => $chart->getGender()->value,
                'region' => $chart->getRegion()->value,
                'size_system' => $chart->getSizeSystem()->value,
                'size_mappings' => $chart->getSizeMappings(),
                'measurements' => $chart->getMeasurements(),
                'category' => $chart->getCategory(),
                'is_active' => $chart->isActive(),
            ]);
        } else {
            SizeChartModel::create([
                'uuid' => $chart->getUuid(),
                'brand' => $chart->getBrand(),
                'gender' => $chart->getGender()->value,
                'region' => $chart->getRegion()->value,
                'size_system' => $chart->getSizeSystem()->value,
                'size_mappings' => $chart->getSizeMappings(),
                'measurements' => $chart->getMeasurements(),
                'category' => $chart->getCategory(),
                'is_active' => $chart->isActive(),
            ]);
        }
    }

    public function findByUuid(string $uuid): ?SizeChart
    {
        $model = SizeChartModel::where('uuid', $uuid)->first();

        return $model?->toDomain();
    }

    public function findByBrand(string $brand): Collection
    {
        return SizeChartModel::where('brand', $brand)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByBrandGenderRegion(string $brand, Gender $gender, Region $region, ?string $category = null): Collection
    {
        $query = SizeChartModel::where('brand', $brand)
            ->where('gender', $gender->value)
            ->where('region', $region->value)
            ->where('is_active', true);

        if ($category) {
            $query->where('category', $category);
        } else {
            $query->whereNull('category');
        }

        return $query->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByBrandGenderRegionSystem(string $brand, Gender $gender, Region $region, SizeSystem $system): Collection
    {
        return SizeChartModel::where('brand', $brand)
            ->where('gender', $gender->value)
            ->where('region', $region->value)
            ->where('size_system', $system->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByCategory(string $category): Collection
    {
        return SizeChartModel::where('category', $category)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByGender(Gender $gender): Collection
    {
        return SizeChartModel::where('gender', $gender->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findByRegion(Region $region): Collection
    {
        return SizeChartModel::where('region', $region->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findBySystem(SizeSystem $system): Collection
    {
        return SizeChartModel::where('size_system', $system->value)
            ->where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function findActive(): Collection
    {
        return SizeChartModel::where('is_active', true)
            ->get()
            ->map(fn($model) => $model->toDomain());
    }

    public function delete(string $uuid): bool
    {
        return SizeChartModel::where('uuid', $uuid)->delete() > 0;
    }

    public function getStatistics(): array
    {
        return [
            'total_charts' => SizeChartModel::count(),
            'active_charts' => SizeChartModel::where('is_active', true)->count(),
            'unique_brands' => SizeChartModel::distinct('brand')->count(),
            'by_brand' => $this->countByBrand(),
            'by_gender' => $this->countByGender(),
            'by_category' => $this->countByCategory(),
        ];
    }

    public function countByBrand(): array
    {
        return SizeChartModel::selectRaw('brand, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('brand')
            ->orderBy('count', 'desc')
            ->pluck('count', 'brand')
            ->toArray();
    }

    public function countByGender(): array
    {
        return SizeChartModel::selectRaw('gender, COUNT(*) as count')
            ->where('is_active', true)
            ->groupBy('gender')
            ->pluck('count', 'gender')
            ->toArray();
    }

    public function countByCategory(): array
    {
        return SizeChartModel::selectRaw('category, COUNT(*) as count')
            ->where('is_active', true)
            ->whereNotNull('category')
            ->groupBy('category')
            ->pluck('count', 'category')
            ->toArray();
    }
}
