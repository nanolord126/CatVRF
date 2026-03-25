declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\ShortTermRentals\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

final /**
 * ApartmentReview
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class ApartmentReview extends Model
{
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
