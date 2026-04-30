<?php

declare(strict_types=1);

namespace Modules\Restaurant\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Modules\Restaurant\Enums\TableStatus;

final class Table extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'restaurant_tables';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'restaurant_id',
        'uuid',
        'table_number',
        'name',
        'zone',
        'seats',
        'status',
        'is_accessible',
        'is_vip',
        'description',
        'metadata',
        'correlation_id',
    ];

    protected $casts = [
        'seats' => 'integer',
        'status' => TableStatus::class,
        'is_accessible' => 'boolean',
        'is_vip' => 'boolean',
        'metadata' => 'json',
    ];

    protected $hidden = ['deleted_at'];

    // ========================
    // RELATIONSHIPS
    // ========================

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class, 'restaurant_id');
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class, 'table_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeAvailable($query)
    {
        return $query->where('status', TableStatus::AVAILABLE);
    }

    public function scopeOccupied($query)
    {
        return $query->where('status', TableStatus::OCCUPIED);
    }

    public function scopeReserved($query)
    {
        return $query->where('status', TableStatus::RESERVED);
    }

    public function scopeByZone($query, string $zone)
    {
        return $query->where('zone', $zone);
    }

    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    public function scopeAccessible($query)
    {
        return $query->where('is_accessible', true);
    }

    public function scopeBySeats($query, int $minSeats)
    {
        return $query->where('seats', '>=', $minSeats);
    }

    // ========================
    // METHODS
    // ========================

    public function occupy(): bool
    {
        if (!$this->canBeOccupied()) {
            return false;
        }

        $this->status = TableStatus::OCCUPIED;
        return $this->save();
    }

    public function reserve(): bool
    {
        if (!$this->canBeReserved()) {
            return false;
        }

        $this->status = TableStatus::RESERVED;
        return $this->save();
    }

    public function release(): bool
    {
        if (!$this->canBeReleased()) {
            return false;
        }

        $this->status = TableStatus::AVAILABLE;
        return $this->save();
    }

    public function markForCleaning(): bool
    {
        $this->status = TableStatus::CLEANING;
        return $this->save();
    }

    public function markForMaintenance(): bool
    {
        $this->status = TableStatus::MAINTENANCE;
        $this->is_accessible = false;
        return $this->save();
    }

    public function canBeOccupied(): bool
    {
        return $this->status === TableStatus::AVAILABLE && $this->is_accessible;
    }

    public function canBeReserved(): bool
    {
        return in_array($this->status, [TableStatus::AVAILABLE, TableStatus::RESERVED], true) && $this->is_accessible;
    }

    public function canBeReleased(): bool
    {
        return in_array($this->status, [TableStatus::OCCUPIED, TableStatus::RESERVED], true);
    }

    public function getCurrentOrder(): ?Order
    {
        return $this->orders()
            ->where('status', '!=', 'completed')
            ->where('status', '!=', 'cancelled')
            ->first();
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant_scoped', function ($query) {
            if ($tenantId = tenant('id')) {
                $query->where('tenant_id', $tenantId);
            }
        });

        self::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }
}
