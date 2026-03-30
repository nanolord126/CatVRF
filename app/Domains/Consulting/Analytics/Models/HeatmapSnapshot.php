<?php declare(strict_types=1);

namespace App\Domains\Consulting\Analytics\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class HeatmapSnapshot extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'heatmap_snapshots';

        protected $fillable = [
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
