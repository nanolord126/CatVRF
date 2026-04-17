<?php declare(strict_types=1);

namespace App\Domains\Auto\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class AutoCatalogBrand extends Model
{

        protected $table = 'auto_catalog_brands';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'slug',
            'type',
            'country',
            'correlation_id',
            'tags',
            'metadata',
        ];

        protected $casts = [
            'tenant_id' => 'integer',
            'tags' => 'json',
            'metadata' => 'json',
        ];

        /**
         * Автоматическая генерация UUID и tenant scoping.
         */
        protected static function booted(): void
        {
            static::creating(function ($model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 1);
            });

            static::addGlobalScope('tenant_id', function ($query) {
                $query->where('tenant_id', tenant()->id ?? 1);
            });
        }

        /**
         * Отношение к запчастям.
         */
        public function parts(): HasMany
        {
            return $this->hasMany(AutoPart::class, 'auto_catalog_brand_id');
        }

        /**
         * Отношение к автомобилям.
         */
        public function vehicles(): HasMany
        {
            return $this->hasMany(AutoVehicle::class, 'auto_catalog_brand_id');
        }
}
