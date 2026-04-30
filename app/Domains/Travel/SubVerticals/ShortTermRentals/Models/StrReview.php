<?php

declare(strict_types=1);

namespace App\Domains\Travel\SubVerticals\ShortTermRentals\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Class StrReview
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
final class StrReview extends Model
{
    use TenantScoped;

    protected $table = 'str_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'booking_id',
        'apartment_id',
        'user_id',
        'rating',
        'comment',
        'media',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'media' => 'json',
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
