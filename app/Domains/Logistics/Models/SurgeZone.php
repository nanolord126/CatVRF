<?php declare(strict_types=1);

namespace App\Domains\Logistics\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SurgeZone extends Model
{


        protected $table = 'logistics_surge_zones';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'geo_zone_id',
            'multiplier',
            'reason', // raining, peak_hour, holiday, extreme_demand
            'is_active',
            'active_from',
            'active_until',
            'metadata',
            'tags',
            'correlation_id'
        ];

        protected $casts = [
            'uuid' => 'string',
            'tenant_id' => 'integer',
            'geo_zone_id' => 'integer',
            'multiplier' => 'float',
            'is_active' => 'boolean',
            'active_from' => 'datetime',
            'active_until' => 'datetime',
            'metadata' => 'array',
            'tags' => 'array',
            'correlation_id' => 'string',
        ];

        protected static function booted_disabled(): void
        {
            static::creating(function (self $model) {
                if (empty($model->uuid)) {
                    $model->uuid = (string) Str::uuid();
                }
                if (empty($model->tenant_id) && function_exists('tenant') && tenant()?->id) {
                    $model->tenant_id = (int) tenant()?->id;
                }
                if (empty($model->is_active)) {
                    $model->is_active = true;
                }
            });

            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant()?->id) {
                    $query->where('tenant_id', tenant()?->id);
                }
            });
        }

        public function geoZone(): BelongsTo
        {
            return $this->belongsTo(GeoZone::class, 'geo_zone_id');
        }

        /**
         * Бизнес-логика (2026 Production Ready)
         */
        public function isActiveNow(): bool
        {
            if (!$this->is_active) {
                return false;
            }

            $now = now();
            $isWithinTimeRange = true;

            if ($this->active_from) {
                $isWithinTimeRange = $isWithinTimeRange && $now->greaterThanOrEqualTo($this->active_from);
            }

            if ($this->active_until) {
                $isWithinTimeRange = $isWithinTimeRange && $now->lessThanOrEqualTo($this->active_until);
            }

            return $isWithinTimeRange;
        }

        public function activate(): void
        {
            $this->update(['is_active' => true]);
        }

        public function deactivate(): void
        {
            $this->update(['is_active' => false]);
        }

        public function extendUntil(\Carbon\Carbon $dateTime): void
        {
            $this->update(['active_until' => $dateTime]);
        }

        public function getReasonLabel(): string
        {
            return match($this->reason) {
                'peak_hour' => 'Час пик',
                'holiday' => 'Праздничный день',
                'extreme_demand' => 'Экстремально высокий спрос',
                default => 'Общее повышение',
            };
        }

        public function getStatusColor(): string
        {
            return $this->isActiveNow() ? 'success' : 'gray';
        }

        /**
         * Форматированный коэффициент (например, 1.5x)
         */
        public function getFormattedMultiplier(): string
        {
            return number_format($this->multiplier, 1) . 'x';
        }
}
