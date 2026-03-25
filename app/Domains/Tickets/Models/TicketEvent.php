<?php

declare(strict_types=1);



/**
 * TicketEvent
 * 
 * Производитель: CatVRF Platform
 * Версия: 1.0.0
 * 
 * Примеры использования:
 * 
 * ```php
 * // Базовое использование
 * $instance = new TicketEvent();
 * ```
 * 
 * Требования:
 * - Laravel 10+
 * - PHP 8.2+
 * - Все методы должны быть явно типизированы
 * 
 * @author CatVRF
 * @package namespace App\Domains\Tickets\Models
 * @see https://github.com/iyegorovskyi_clemny/CatVRF
 */
namespace App\Domains\Tickets\Models;

use App\Traits\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class TicketEvent extends Model
{
    use HasFactory;
    use SoftDeletes;
    use BelongsToTenant;

    protected $table = 'tickets_events';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'business_group_id',
        'title',
        'description',
        'type',
        'start_at',
        'end_at',
        'settings',
        'status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'settings' => 'json',
        'tags' => 'json',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    /**
     * Выполнить операцию
     * 
     * @return mixed
     * @throws \Exception
     */
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }
}
