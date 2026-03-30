<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Car extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use SoftDeletes;

        protected $table = 'cars';

        protected $fillable = [
            'tenant_id',
            'dealer_id',
            'model_id',
            'uuid',
            'price',
            'year',
            'vin',
            'status',
            'specifications',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'specifications' => 'json',
            'tags' => 'json',
            'price' => 'integer'
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

        public function dealer(): BelongsTo
        {
            return $this->belongsTo(CarDealer::class, 'dealer_id');
        }

        public function model(): BelongsTo
        {
            return $this->belongsTo(CarModel::class, 'model_id');
        }
}
