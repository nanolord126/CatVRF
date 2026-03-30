<?php declare(strict_types=1);

namespace App\Models\Stationery;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StationeryCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'stationery_categories';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'name',
            'slug',
            'description',
            'is_active',
            'correlation_id'
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = (string) Str::uuid();
                $model->slug = Str::slug($model->name);
                if (auth()->check() && empty($model->tenant_id)) {
                    $model->tenant_id = auth()->user()->tenant_id;
                }
            });

            static::addGlobalScope('tenant', function ($builder) {
                if (auth()->check()) {
                    $builder->where('tenant_id', auth()->user()->tenant_id);
                }
            });
        }

        public function products(): HasMany
        {
            return $this->hasMany(StationeryProduct::class, 'category_id');
        }
}
