<?php declare(strict_types=1);

namespace App\Models\Cleaning;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

final class CleaningAddress extends Model
{
    use HasFactory;

    protected $table = 'cleaning_addresses';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'user_id',
            'address_line',
            'lat',
            'lon',
            'access_info',
            'property_type', // apartment, house, office, warehouse, commercial
            'area_sqm',
            'metadata',
            'correlation_id',
        ];

        protected $casts = [
            'lat' => 'float',
            'lon' => 'float',
            'area_sqm' => 'float',
            'metadata' => 'json',
            'tenant_id' => 'integer',
            'user_id' => 'integer',
        ];

        /**
         * Boot logic for metadata and tenant isolation.
         */
        protected static function booted(): void
        {
            static::creating(function (self $model) {
                $model->uuid = $model->uuid ?? (string) Str::uuid();
                $model->tenant_id = $model->tenant_id ?? (int) (tenant()->id ?? 0);
            });

            static::addGlobalScope('tenant', function (Builder $query) {
                if (tenant()) {
                    $query->where('tenant_id', tenant()->id);
                }
            });
        }

        /**
         * User/Customer owner of the location.
         */
        public function user(): BelongsTo
        {
            return $this->belongsTo(User::class);
        }

        /**
         * All orders ever made at this address.
         */
        public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
        {
            return $this->hasMany(CleaningOrder::class);
        }

        /**
         * B2B context verification for commercial locations.
         */
        public function isCommercial(): bool
        {
            return in_array($this->property_type, ['office', 'warehouse', 'commercial'], true);
        }

        /**
         * Formatting address for display.
         */
        public function fullAddress(): string
        {
            return $this->address_line . ' (' . $this->area_sqm . ' sqm)';
        }
}
