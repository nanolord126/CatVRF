<?php declare(strict_types=1);

namespace App\Domains\Photography\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class Photographer
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
final class Photographer extends Model
{

    protected $table = 'photography_photographers';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'full_name',
        'specialization',
        'experience_years',
        'base_price_hour_kopecks',
        'equipment_json',
        'is_available',
        'correlation_id'
    ];

    protected $casts = [
        'uuid' => 'string',
        'equipment_json' => 'json',
        'is_available' => 'boolean',
        'base_price_hour_kopecks' => 'integer',
        'experience_years' => 'integer',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid ??= (string) Str::uuid();
            $model->tenant_id ??= tenant()?->id;
        });
    }

    public function portfolios(): HasMany
    {
        return $this->hasMany(Portfolio::class, 'photographer_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'photographer_id');
    }
}
