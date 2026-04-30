<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ManagerKPILog extends Model
{
    use HasFactory;

    protected $fillable = [
        'manager_kpi_id',
        'manager_id',
        'previous_value',
        'new_value',
        'delta',
        'event_type',
        'reference_type',
        'reference_id',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'previous_value' => 'decimal:2',
        'new_value' => 'decimal:2',
        'delta' => 'decimal:2',
        'metadata' => 'array',
    ];

    // Relations
    public function kpi(): BelongsTo
    {
        return $this->belongsTo(ManagerKPI::class, 'manager_kpi_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_id');
    }
}
