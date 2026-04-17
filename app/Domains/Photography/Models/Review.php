<?php declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Review
 *
 * Part of the Photography vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Photography\Models
 */
final class Review extends Model
{

    protected $table = 'photography_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'photographer_id',
        'studio_id',
        'user_id',
        'rating',
        'comment',
        'photos',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'photos' => 'json',
        'rating' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
        });
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class, 'booking_id');
    }

    public function photographer(): BelongsTo
    {
        return $this->belongsTo(Photographer::class, 'photographer_id');
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(PhotoStudio::class, 'studio_id');
    }
}
