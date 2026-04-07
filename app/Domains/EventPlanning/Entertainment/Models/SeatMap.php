<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SeatMap extends Model
{
    use HasFactory;

    protected $table = 'entertainment_seat_maps';

        protected $fillable = [
            'uuid',
            'venue_id',
            'tenant_id',
            'name',
            'layout',
            'categories',
            'correlation_id',
        ];

        protected $casts = [
            'layout' => 'json',
            'categories' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
        ];

        /**
         * Получить структуру раскладки в виде массива
         */
        public function getLayoutArray(): array
        {
            return is_array($this->layout) ? $this->layout : [];
        }

        /**
         * Получить список категорий мест (VIP, Standard и т.д.)
         */
        public function getCategoriesArray(): array
        {
            return is_array($this->categories) ? $this->categories : [];
        }

        /**
         * Название схемы (для UI)
         */
        public function getName(): string
        {
            return (string) $this->name;
        }

        /**
         * Получение correlation_id
         */
        public function getCorrelationId(): string
        {
            return (string) $this->correlation_id;
        }
}
