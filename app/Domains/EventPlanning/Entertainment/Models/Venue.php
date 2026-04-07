<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Venue extends Model
{
    use HasFactory;

    use SoftDeletes;

        protected $table = 'entertainment_venues';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'business_group_id',
            'name',
            'type',
            'address',
            'geo_point',
            'schedule',
            'rating',
            'review_count',
            'is_active',
            'is_b2b_enabled',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'geo_point' => 'json',
            'schedule' => 'json',
            'tags' => 'json',
            'is_active' => 'boolean',
            'is_b2b_enabled' => 'boolean',
            'rating' => 'float',
            'review_count' => 'integer',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
        ];

        /**
         * Проверка: активно ли заведение
         */
        public function isActive(): bool
        {
            return $this->is_active && $this->deleted_at === null;
        }

        /**
         * Проверка: разрешено ли B2B бронирование
         */
        public function isB2BEnabled(): bool
        {
            return $this->is_b2b_enabled;
        }

        /**
         * Получить средний рейтинг из поля модели (кешируется ML/Jobs)
         */
        public function getRating(): float
        {
            return $this->rating;
        }
}
