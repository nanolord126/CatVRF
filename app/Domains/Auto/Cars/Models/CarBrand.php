<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarBrand extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'car_brands';

        protected $fillable = [
            'uuid',
            'name',
            'slug',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
            });
        }

        public function models(): HasMany
        {
            return $this->hasMany(CarModel::class, 'brand_id');
        }
}
