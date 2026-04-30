<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Verticals\Beauty;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\CatCRM\Domain\Entities\Deal;
use Modules\CatCRM\Domain\Entities\Customer;
use App\Models\User;

/**
 * Beauty Appointment — Запись в вертикали Бьюти
 * 
 * Расширяет базовую Deal модель специфичными полями для бьюти-салонов.
 */
final class BeautyAppointment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'deal_id',
        'customer_id',
        'master_id',
        'service_type',
        'services',
        'appointment_date',
        'appointment_time',
        'duration_minutes',
        'status',
        'no_show',
        'cancellation_reason',
        'notes',
        'products_used',
        'total_price',
        'discount_percent',
        'final_price',
        'reminder_sent',
        'follow_up_sent',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'appointment_date' => 'datetime',
        'duration_minutes' => 'integer',
        'no_show' => 'boolean',
        'reminder_sent' => 'boolean',
        'follow_up_sent' => 'boolean',
        'total_price' => 'integer',
        'discount_percent' => 'integer',
        'final_price' => 'integer',
        'services' => 'json',
        'products_used' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_beauty_appointments';

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

    public function master(): BelongsTo
    {
        return $this->belongsTo(User::class, 'master_id');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByMaster($query, int $masterId)
    {
        return $query->where('master_id', $masterId);
    }

    public function scopeByDate($query, $date)
    {
        return $query->whereDate('appointment_date', $date);
    }

    public function scopeToday($query)
    {
        return $query->whereDate('appointment_date', today());
    }

    public function scopeUpcoming($query)
    {
        return $query->where('appointment_date', '>=', now())
            ->whereIn('status', ['confirmed', 'pending']);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeNoShow($query)
    {
        return $query->where('no_show', true);
    }

    // ========================
    // METHODS
    // ========================

    public function confirm(): bool
    {
        $this->status = 'confirmed';
        return $this->save();
    }

    public function complete(?array $productsUsed = null): bool
    {
        $this->status = 'completed';
        if ($productsUsed) {
            $this->products_used = $productsUsed;
        }
        return $this->save();
    }

    public function cancel(string $reason): bool
    {
        $this->status = 'cancelled';
        $this->cancellation_reason = $reason;
        return $this->save();
    }

    public function markNoShow(): bool
    {
        $this->status = 'no_show';
        $this->no_show = true;
        return $this->save();
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Автоматический расчет final_price
            if ($model->final_price === 0 && $model->total_price > 0) {
                $discount = $model->total_price * ($model->discount_percent / 100);
                $model->final_price = $model->total_price - $discount;
            }
        });
    }
}
