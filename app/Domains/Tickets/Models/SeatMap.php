<?php declare(strict_types=1);

namespace App\Domains\Tickets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Traits\LogsActivity;
use Illuminate\Support\Str;

final class SeatMap extends Model
{
    use HasFactory;

    use SoftDeletes, LogsActivity;

        protected $table = 'seat_maps';

        protected $fillable = [
            'uuid', 'tenant_id', 'venue_id', 'title',
            'layout', 'description', 'capacity_info',
            'is_active', 'tags', 'correlation_id'
        ];

        protected $casts = [
            'layout' => 'json',
            'capacity_info' => 'json',
            'is_active' => 'boolean',
            'tags' => 'json'
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', function ($builder) {
                if (function_exists('tenant') && tenant()?->id) {
                    $builder->where('tenant_id', tenant()?->id);
                }
            });

            static::creating(function ($model) {
                $model->uuid = (string) Str::uuid();
                if (empty($model->tenant_id) && function_exists('tenant')) {
                    $model->tenant_id = tenant()?->id;
                }
            });
        }

        public function getActivitylogOptions(): LogOptions
        {
            return LogOptions::defaults()
                ->logOnly(['title', 'layout', 'is_active'])
                ->logOnlyDirty()
                ->dontSubmitEmptyLogs()
                ->useLogName('audit');
        }

        /**
         * Площадка для схемы.
         */
        public function venue(): BelongsTo
        {
            return $this->belongsTo(Venue::class);
        }

        /**
         * Эвенты по этой схеме.
         */
        public function events(): HasMany
        {
            return $this->hasMany(Event::class);
        }

        /**
         * Список секторов.
         */
        public function getSectorsAttribute(): array
        {
            return array_column($this->layout['sectors'] ?? [], 'name');
        }

        /**
         * Общий лимит по секторам.
         */
        public function getTotalCapacityAttribute(): int
        {
            $sum = 0;
            foreach ($this->layout['sectors'] ?? [] as $sector) {
                $sum += $sector['capacity'] ?? 0;
            }
            return $sum;
        }

        /**
         * Валидация места по схеме.
         */
        public function isValidSeat(string $sector, ?int $row, ?int $number): bool
        {
            $sectorData = collect($this->layout['sectors'] ?? [])
                ->firstWhere('name', $sector);

            if (!$sectorData) {
                return false;
            }

            if (isset($sectorData['rows']) && !is_null($row)) {
                $rowData = collect($sectorData['rows'])->firstWhere('number', $row);
                if (!$rowData) return false;
                if ($number > ($rowData['seats'] ?? 0)) return false;
            }

            return true;
        }
}
