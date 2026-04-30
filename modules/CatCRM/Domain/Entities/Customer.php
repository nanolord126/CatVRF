<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Entities;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Tenant;
use App\Models\User;

/**
 * Customer — Клиент в CRM
 * 
 * Представляет клиента (физическое или юридическое лицо) в CRM системе.
 * Хранит контактную информацию, историю взаимодействий и LTV.
 */
final class Customer extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'user_id', // Связь с пользователем платформы (если есть)
        'type',
        'first_name',
        'last_name',
        'middle_name',
        'company_name',
        'inn',
        'email',
        'phone',
        'address',
        'city',
        'country',
        'postal_code',
        'birth_date',
        'gender',
        'avatar',
        'source',
        'loyalty_tier',
        'total_spent',
        'orders_count',
        'last_order_at',
        'last_interaction_at',
        'is_vip',
        'is_blocked',
        'block_reason',
        'notes',
        'preferences',
        'communication_preferences',
        'metadata',
        'correlation_id',
        'uuid',
    ];

    protected $casts = [
        'birth_date' => 'datetime',
        'last_order_at' => 'datetime',
        'last_interaction_at' => 'datetime',
        'total_spent' => 'integer',
        'orders_count' => 'integer',
        'is_vip' => 'boolean',
        'is_blocked' => 'boolean',
        'type' => \Modules\CatCRM\Domain\Enums\CustomerType::class,
        'loyalty_tier' => \Modules\CatCRM\Domain\Enums\LoyaltyTier::class,
        'preferences' => 'json',
        'communication_preferences' => 'json',
        'metadata' => 'json',
    ];

    protected $table = 'crm_customers';

    // ========================
    // RELATIONSHIPS
    // ========================

    /**
     * Tenant
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Business Group (филиал)
     */
    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    /**
     * Связь с пользователем платформы (если клиент зарегистрирован)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Сделки клиента
     */
    public function deals(): HasMany
    {
        return $this->hasMany(Deal::class);
    }

    /**
     * Взаимодействия с клиентом
     */
    public function interactions(): HasMany
    {
        return $this->hasMany(Interaction::class);
    }

    /**
     * Задачи по клиенту
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Теги клиента
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\CatCRM\Domain\Entities\Tag::class, 'crm_customer_tags');
    }

    /**
     * Сегменты клиента
     */
    public function segments(): BelongsToMany
    {
        return $this->belongsToMany(\Modules\CatCRM\Domain\Entities\Segment::class, 'crm_customer_segments');
    }

    // ========================
    // SCOPES
    // ========================

    public function scopeByTenant($query, int $tenantId)
    {
        return $query->where('tenant_id', $tenantId);
    }

    public function scopeByBusinessGroup($query, ?int $businessGroupId)
    {
        if ($businessGroupId === null) {
            return $query->whereNull('business_group_id');
        }

        return $query->where('business_group_id', $businessGroupId);
    }

    public function scopeByType($query, \Modules\CatCRM\Domain\Enums\CustomerType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    public function scopeVip($query)
    {
        return $query->where('is_vip', true);
    }

    public function scopeBlocked($query)
    {
        return $query->where('is_blocked', true);
    }

    public function scopeActive($query)
    {
        return $query->where('is_blocked', false);
    }

    public function scopeByLoyaltyTier($query, \Modules\CatCRM\Domain\Enums\LoyaltyTier $tier)
    {
        return $query->where('loyalty_tier', $tier);
    }

    public function scopeSleeping($query, int $days = 60)
    {
        return $query->where('last_interaction_at', '<', now()->subDays($days));
    }

    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('last_interaction_at', '>=', now()->subDays($days));
    }

    // ========================
    // METHODS
    // ========================

    /**
     * Получить полное имя
     */
    public function getFullName(): string
    {
        return trim("{$this->last_name} {$this->first_name} {$this->middle_name}");
    }

    /**
     * Получить отображаемое имя
     */
    public function getDisplayName(): string
    {
        if ($this->type === \Modules\CatCRM\Domain\Enums\CustomerType::Business && $this->company_name) {
            return $this->company_name;
        }

        return $this->getFullName() ?: $this->email ?: $this->phone;
    }

    /**
     * Обновить LTV и статистику
     */
    public function updateStatistics(): bool
    {
        $deals = $this->deals()->won()->get();
        
        $this->total_spent = $deals->sum('value');
        $this->orders_count = $deals->count();
        $this->last_order_at = $deals->max('actual_close_date');
        
        // Обновляем loyalty tier
        $this->updateLoyaltyTier();
        
        return $this->save();
    }

    /**
     * Обновить loyalty tier на основе total_spent
     */
    public function updateLoyaltyTier(): void
    {
        $tiers = config('crm.loyalty_tiers');
        
        $currentTier = \Modules\CatCRM\Domain\Enums\LoyaltyTier::Bronze;
        
        foreach ($tiers as $tierName => $tierConfig) {
            if ($this->total_spent >= $tierConfig['min_spent']) {
                $currentTier = \Modules\CatCRM\Domain\Enums\LoyaltyTier::from($tierName);
            }
        }
        
        $this->loyalty_tier = $currentTier;
    }

    /**
     * Проверить, является ли клиент "спящим"
     */
    public function isSleeping(int $days = 60): bool
    {
        return $this->last_interaction_at === null
            || $this->last_interaction_at->lt(now()->subDays($days));
    }

    /**
     * Получить LTV (Lifetime Value)
     */
    public function getLTV(): int
    {
        return $this->total_spent;
    }

    /**
     * Получить средний чек
     */
    public function getAverageOrderValue(): float
    {
        if ($this->orders_count === 0) {
            return 0;
        }

        return $this->total_spent / $this->orders_count;
    }

    /**
     * Добавить тег
     */
    public function addTag(Tag $tag): void
    {
        $this->tags()->syncWithoutDetaching([$tag->id]);
    }

    /**
     * Удалить тег
     */
    public function removeTag(Tag $tag): void
    {
        $this->tags()->detach($tag->id);
    }

    protected static function booted(): void
    {
        parent::booted();

        static::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
