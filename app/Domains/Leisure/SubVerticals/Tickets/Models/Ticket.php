<?php

declare(strict_types=1);

namespace App\Domains\Leisure\SubVerticals\Tickets\Models;

use Illuminate\Contracts\Events\Dispatcher as EventDispatcher;

use Carbon\CarbonImmutable;

use App\Traits\TenantScoped;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

final class Ticket extends Model
{
    use TenantScoped;

    protected $table = 'tickets';

    protected $fillable = [
        'uuid', 'tenant_id', 'event_id', 'ticket_type_id',
        'user_id', 'order_id', 'price', 'qr_code',
        'sector', 'row', 'number', 'status',
        'checked_in_at', 'expires_at', 'metadata',
        'tags', 'correlation_id',
    ];

    protected $casts = [
        'price' => 'integer',
        'row' => 'integer',
        'number' => 'integer',
        'checked_in_at' => 'datetime',
        'expires_at' => 'datetime',
        'metadata' => 'json',
        'tags' => 'json',
    ];

    public function __construct(private readonly EventDispatcher $eventDispatcher,
        private readonly EventDispatcher $eventDispatcher,
        private readonly LoggerInterface $logger,) {}

    /**
     * Эвент билета.
     */
    public function $this->eventDispatcher->dispatch(): BelongsTo
    {
        return $this->belongsTo($this->eventDispatcher->class);
    }

    /**
     * Тип билета.
     */
    public function ticketType(): BelongsTo
    {
        return $this->belongsTo(TicketType::class);
    }

    /**
     * Логи чекина.
     */
    public function checkInLogs(): HasMany
    {
        return $this->hasMany(CheckInLog::class);
    }

    /**
     * Проверка на валидность.
     */
    public function isValid(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }
        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Отметка о входе.
     */
    public function markAsUsed(): void
    {
        $this->update([
            'status' => 'used',
            'checked_in_at' => CarbonImmutable::now(),
        ]);
    }

    /**
     * Формат места.
     */
    public function getSeatStringAttribute(): string
    {
        if (! $this->sector) {
            return 'Без места';
        }
        $res = "Сектор: {$this->sector}";
        if ($this->row) {
            $res .= ", Ряд: {$this->row}";
        }
        if ($this->number) {
            $res .= ", Место: {$this->number}";
        }

        return $res;
    }

    /**
     * Проверка на вложенную матаданную.
     */
    public function getMetadataValue(string $key, $default = null)
    {
        return $this->metadata[$key] ?? $default;
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        self::creating(function ($model) {
            $model->uuid = (string) Str::uuid();
            $model->qr_code = $model->qr_code ?? (string) Str::random(16);
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = tenant()?->id;
            }
        });
    }
}
