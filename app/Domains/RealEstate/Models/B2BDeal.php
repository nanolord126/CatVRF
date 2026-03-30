<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class B2BDeal extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use LogsActivity;

        protected $table = 'b2b_deals';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'listing_id',
            'investor_id',
            'correlation_id',
            'deal_amount',
            'expected_roi',
            'status',
            'deal_structure',
        ];

        protected $casts = [
            'deal_amount' => 'integer',
            'expected_roi' => 'float',
            'deal_structure' => 'json',
        ];

        protected static function booted(): void
        {
            static::creating(function (B2BDeal $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                    $model->tenant_id = tenant()->id;
                }
            });

            static::addGlobalScope('tenant', function (Builder $builder) {
                if (function_exists('tenant') && tenant()) {
                    $builder->where('tenant_id', tenant()->id);
                }
            });
        }

        public function listing(): \Illuminate\Database\Eloquent\Relations\BelongsTo
        {
            return $this->belongsTo(Listing::class);
        }

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logFillable()
                ->useLogName('b2b_deals')
                ->logOnlyDirty();
        }
}
