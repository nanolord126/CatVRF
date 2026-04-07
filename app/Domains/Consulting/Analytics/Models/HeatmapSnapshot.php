<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HeatmapSnapshot extends Model
{
    use HasFactory;

    protected $table = 'heatmap_snapshots';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'heatmap_type',
            'vertical',
            'snapshot_date',
            'data',
            'file_path',
            'status',
            'data_points_count',
            'correlation_id',
        ];

        protected $casts = [
            'data' => AsJson::class,
            'snapshot_date' => 'date',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }


        public function tenant(): BelongsTo
        {
            return $this->belongsTo(Tenant::class);
        }

        public function scopeByType(Builder $query, string $type): Builder
        {
            return $query->where('heatmap_type', $type);
        }

        public function scopeReady(Builder $query): Builder
        {
            return $query->where('status', 'ready');
        }

        public function scopeLatest(Builder $query): Builder
        {
            return $query->orderByDesc('snapshot_date');
        }

        /**
         * Установить статус на 'ready' после генерации
         */
        public function markAsReady(): void
        {
            $this->update(['status' => 'ready']);
        }

        /**
         * Установить статус на 'failed' при ошибке
         */
        public function markAsFailed(): void
        {
            $this->update(['status' => 'failed']);
        }
}
