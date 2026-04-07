<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Car extends Model
{
    use HasFactory, SoftDeletes;


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

    /**
     * Get the string representation of this instance.
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return static::class;
    }

    /**
     * Get debug information for this instance.
     *
     * @return array<string, mixed> Debug data including class name and state
     */
    public function toDebugArray(): array
    {
        return [
            'class' => static::class,
            'timestamp' => Carbon::now()->toIso8601String(),
        ];
    }
}
