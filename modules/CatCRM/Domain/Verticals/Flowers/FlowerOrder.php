<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Flowers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;

/**
 * Flower Order — Заказ в вертикали Флористы
 * 
 * Расширяет базовую Deal модель специфичными полями для флористов.
 */
final class FlowerOrder extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'bouquet_type',
        'flowers',
        'card_message',
        'delivery_date',
        'delivery_time_slot',
        'delivery_address',
        'recipient_name',
        'recipient_phone',
        'occasion',
        'budget',
        'actual_price',
        'design_status',
        'assembly_status',
        'delivery_status',
        'courier_id',
        'tracking_number',
        'photo_report_url',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'delivery_date' => 'datetime',
        'budget' => 'integer',
        'actual_price' => 'integer',
        'flowers' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_flower_orders';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByDeliveryDate($query, $date)
    {
        return $query->whereDate('delivery_date', $date);
    }

    public function scopeTodayDelivery($query)
    {
        return $query->whereDate('delivery_date', today());
    }

    public function scopePendingDelivery($query)
    {
        return $query->where('delivery_status', 'pending');
    }

    public function scopeDelivered($query)
    {
        return $query->where('delivery_status', 'delivered');
    }

    // ========================
    // METHODS
    // ========================

    public function markAsDesigned(): bool
    {
        $this->design_status = 'completed';
        return $this->save();
    }

    public function markAsAssembled(): bool
    {
        $this->assembly_status = 'completed';
        return $this->save();
    }

    public function markAsDelivered(?string $photoUrl = null): bool
    {
        $this->delivery_status = 'delivered';
        if ($photoUrl) {
            $this->photo_report_url = $photoUrl;
        }
        return $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
