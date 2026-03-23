<?php

declare(strict_types=1);

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

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'event_id');
    }
}
