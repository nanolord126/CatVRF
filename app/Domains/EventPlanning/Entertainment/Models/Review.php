<?php declare(strict_types=1);

namespace App\Domains\EventPlanning\Entertainment\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\TenantScoped;

final class Review extends Model
{
    use HasFactory, TenantScoped;

    protected $table = 'entertainment_reviews';

        protected $fillable = [
            'uuid',
            'venue_id',
            'user_id',
            'tenant_id',
            'rating',
            'comment',
            'photos',
            'correlation_id',
        ];

        protected $casts = [
            'rating' => 'integer',
            'photos' => 'json',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];

        protected $hidden = [
            'id',
            'tenant_id',
        ];

        /**
         * Получить оценку (Rating)
         */
        public function getRating(): int
        {
            return $this->rating;
        }

        /**
         * Получить текст отзыва
         */
        public function getComment(): string
        {
            return (string) $this->comment;
        }

        /**
         * Получить список фото как массив
         */
        public function getPhotosArray(): array
        {
            return is_array($this->photos) ? $this->photos : [];
        }

        /**
         * Получение correlation_id
         */
        public function getCorrelationId(): string
        {
            return (string) $this->correlation_id;
        }
}
