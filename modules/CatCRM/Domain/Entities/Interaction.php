<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

/**
 * Interaction — Взаимодействие с клиентом
 * 
 * Представляет любое взаимодействие с клиентом: звонок, email, встреча и т.д.
 */
final class Interaction extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'customer_id',
        'deal_id',
        'task_id',
        'user_id',
        'type',
        'channel',
        'direction', // inbound, outbound
        'subject',
        'content',
        'duration_minutes',
        'outcome',
        'next_step',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'type' => \Modules\CatCRM\Domain\Enums\InteractionType::class,
        'duration_minutes' => 'integer',
        'metadata' => 'json',
    ];

    protected $table = 'crm_interactions';

    // ========================
    // RELATIONSHIPS
    // ========================

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function deal(): BelongsTo
    {
        return $this->belongsTo(Deal::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeByDeal($query, int $dealId)
    {
        return $query->where('deal_id', $dealId);
    }

    public function scopeByType($query, \Modules\CatCRM\Domain\Enums\InteractionType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByChannel($query, string $channel)
    {
        return $query->where('channel', $channel);
    }

    public function scopeInbound($query)
    {
        return $query->where('direction', 'inbound');
    }

    public function scopeOutbound($query)
    {
        return $query->where('direction', 'outbound');
    }

    // ========================
    // METHODS
    // ========================

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
            
            // Обновляем last_interaction_at у клиента
            if ($model->customer_id) {
                Customer::find($model->customer_id)?->update([
                    'last_interaction_at' => now(),
                ]);
            }
        });
    }
}
