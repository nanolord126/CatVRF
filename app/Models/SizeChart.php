<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Domains\Shared\Size\Entities\SizeChart;
use App\Domains\Shared\Size\ValueObjects\Gender;
use App\Domains\Shared\Size\ValueObjects\Region;
use App\Domains\Shared\Size\ValueObjects\SizeSystem;

final class SizeChart extends Model
{
    use HasFactory;

    protected $table = 'size_charts';

    protected $fillable = [
        'uuid',
        'brand',
        'gender',
        'region',
        'size_system',
        'size_mappings',
        'measurements',
        'category',
        'is_active',
    ];

    protected $casts = [
        'size_mappings' => 'array',
        'measurements' => 'array',
        'is_active' => 'boolean',
    ];

    public function toDomain(): SizeChart
    {
        return SizeChart::fromArray([
            'uuid' => $this->uuid,
            'brand' => $this->brand,
            'gender' => $this->gender,
            'region' => $this->region,
            'size_system' => $this->size_system,
            'size_mappings' => $this->size_mappings,
            'measurements' => $this->measurements,
            'category' => $this->category,
            'is_active' => $this->is_active,
        ]);
    }

    public static function fromDomain(SizeChart $chart): self
    {
        return new self([
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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByBrand($query, string $brand)
    {
        return $query->where('brand', $brand);
    }

    public function scopeByCategory($query, ?string $category)
    {
        if ($category) {
            return $query->where('category', $category);
        }
        return $query->whereNull('category');
    }
}
