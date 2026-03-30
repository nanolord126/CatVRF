<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class StrCalendarAvailability extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'str_calendar_availability';

        protected $fillable = [
            'tenant_id',
            'apartment_id',
            'date',
            'is_available',
            'price_override_b2c',
            'price_override_b2b',
            'reason',
            'correlation_id',
        ];

        protected $casts = [
            'date' => 'date',
            'is_available' => 'boolean',
            'price_override_b2c' => 'integer',
            'price_override_b2b' => 'integer',
        ];

        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->correlation_id ??= request()->header('X-Correlation-ID', (string) Str::uuid());
                $model->tenant_id ??= tenant()->id ?? null;
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function apartment(): BelongsTo
        {
            return $this->belongsTo(StrApartment::class, 'apartment_id');
        }
}
