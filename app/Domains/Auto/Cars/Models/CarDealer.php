<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarDealer extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'car_dealers';

        protected $fillable = [
            'tenant_id',
            'uuid',
            'name',
            'address',
            'geo_point',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant_id', function (Builder $builder) {
                $builder->where('tenant_id', tenant()->id ?? 0);
            });

            static::creating(function (Model $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
            });
        }

        public function cars(): HasMany
        {
            return $this->hasMany(Car::class, 'dealer_id');
        }
}
