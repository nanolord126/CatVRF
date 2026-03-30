<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class ApartmentReview extends Model
{
    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasFactory;

        protected $fillable = [
            'tenant_id', 'apartment_id', 'booking_id',
            'user_id', 'rating', 'comment', 'images',
            'uuid', 'correlation_id', 'tags',
        ];

        protected $casts = [
            'images' => 'json', 'tags' => 'json',
            'rating' => 'integer',
        ];

        protected static function booted(): void
        {
            static::addGlobalScope('tenant', fn($q) =>
                $q->where('tenant_id', tenant()->id ?? 0)
            );
        }

        public function apartment(): BelongsTo
        {
            return $this->belongsTo(Apartment::class);
        }

        public function booking(): BelongsTo
        {
            return $this->belongsTo(ApartmentBooking::class);
        }
}
