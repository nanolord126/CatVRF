<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\EventPlanning\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class EventVendor extends Model
{
    use TenantScoped;

    protected $table = 'event_planning_vendors';

    protected $fillable = [
        'uuid',
        'event_id',
        'tenant_id',
        'vertical',
        'vendor_id',
        'vendor_name',
        'status',
        'agreed_price_kopecks',
        'deposit_paid_kopecks',
        'agreed_conditions',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'agreed_price_kopecks' => 'integer',
        'deposit_paid_kopecks' => 'integer',
        'agreed_conditions' => 'array',
    ];

    /**
     * Relations.
     */
    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly EventDispatcher $eventDispatcher,) {}

    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo($this->eventDispatcher->class, 'event_id');
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Helpers.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isContracted(): bool
    {
        return $this->status === 'contracted';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function getPriceInRublesAttribute(): float
    {
        return $this->agreed_price_kopecks / 100;
    }

    public function getDepositInRublesAttribute(): float
    {
        return $this->deposit_paid_kopecks / 100;
    }

    /**
     * Маркировка вертикали для UI/Business Logic.
     */
    public function getVerticalLabelAttribute(): string
    {
        return match ($this->vertical) {
            'photo' => 'Фотография и Видео',
            'beauty' => 'Красота и Стилисты',
            'auto' => 'Транспорт и Логистика',
            'decoration' => 'Декор и Оформление',
            'music' => 'Музыка и Развлечения',
            default => 'Другое',
        };
    }

    /**
     * Booted method with Global Scopes.
     */
    protected static function booted(): void
    {
        self::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->tenant_id)) {
                $model->tenant_id = tenant()->id ?? $this->guard->user()?->current_tenant_id ?? 1;
            }
        });

        self::addGlobalScope('tenant', function ($query) {
            if ($tenant = tenant()) {
                $query->where('tenant_id', $tenant->id);
            }
        });
    }
}
