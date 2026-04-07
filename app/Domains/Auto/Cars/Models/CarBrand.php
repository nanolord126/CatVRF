<?php declare(strict_types=1);

namespace App\Domains\Auto\Cars\Models;

use Carbon\Carbon;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CarBrand extends Model
{
    use HasFactory;


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
            static::addGlobalScope('tenant', function ($query) {
                if (function_exists('tenant') && tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });

            static::creating(function (Model $model) {
                if (empty($model->uuid)) {
                    $model->uuid = \Illuminate\Support\Str::uuid()->toString();
                }
            });
        }

        public function models(): HasMany
        {
            return $this->hasMany(CarModel::class, 'brand_id');
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