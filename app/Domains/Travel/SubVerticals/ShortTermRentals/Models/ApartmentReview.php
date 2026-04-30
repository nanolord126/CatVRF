<?php

declare(strict_types=1);

/**
 * ApartmentReview — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @version 2026.1
 *
 * @author CatVRF Team
 * @license Proprietary

 *
 * @see https://catvrf.ru/docs/apartmentreview
 */

namespace App\Domains\Travel\SubVerticals\ShortTermRentals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class ApartmentReview
 *
 * Part of the ShortTermRentals vertical domain.
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
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
final class ApartmentReview extends Model
{
    use TenantScoped;

    protected $table = 'short_term_apartment_reviews';

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
        self::creating(function ($model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });
    }
}
