<?php declare(strict_types=1);

namespace Modules\RealEstate\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\RealEstate\Enums\PropertyType;
use Modules\RealEstate\Enums\PropertyStatus;

final class Property extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'real_estate_properties';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'owner_id',
        'title',
        'description',
        'address',
        'city',
        'region',
        'lat',
        'lon',
        'property_type',
        'status',
        'price',
        'area',
        'rooms',
        'floor',
        'total_floors',
        'year_built',
        'features',
        'images',
        'virtual_tour_url',
        'ar_model_url',
        'document_hashes',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'property_type' => PropertyType::class,
        'status' => PropertyStatus::class,
        'price' => 'decimal:14',
        'area' => 'decimal:8',
        'lat' => 'decimal:10',
        'lon' => 'decimal:10',
        'features' => 'json',
        'images' => 'json',
        'document_hashes' => 'json',
        'tags' => 'json',
        'metadata' => 'json',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
            if (!$model->correlation_id) {
                $model->correlation_id = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'owner_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(PropertyBooking::class, 'property_id');
    }

    public function scopeForTenant($query, $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeAvailable($query)
    {
        return $query->where('status', PropertyStatus::AVAILABLE);
    }

    public function scopeSold($query)
    {
        return $query->where('status', PropertyStatus::SOLD);
    }

    public function scopeRented($query)
    {
        return $query->where('status', PropertyStatus::RENTED);
    }

    public function scopeByType($query, PropertyType $type)
    {
        return $query->where('property_type', $type);
    }

    public function scopeInCity($query, string $city)
    {
        return $query->where('city', 'like', "%{$city}%");
    }

    public function markAsSold(): self
    {
        $this->update(['status' => PropertyStatus::SOLD]);
        return $this;
    }

    public function markAsRented(): self
    {
        $this->update(['status' => PropertyStatus::RENTED]);
        return $this;
    }

    public function markAsAvailable(): self
    {
        $this->update(['status' => PropertyStatus::AVAILABLE]);
        return $this;
    }

    public function hasVirtualTour(): bool
    {
        return !empty($this->virtual_tour_url);
    }

    public function hasARModel(): bool
    {
        return !empty($this->ar_model_url);
    }

    public function getPricePerSquareMeter(): float
    {
        return $this->area > 0 ? round($this->price / $this->area, 2) : 0.0;
    }
}
