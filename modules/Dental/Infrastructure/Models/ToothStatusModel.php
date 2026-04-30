<?php

declare(strict_types=1);

namespace Modules\Dental\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class ToothStatusModel extends Model
{
    use HasFactory;

    protected $table = 'dental_tooth_statuses';

    protected $fillable = [
        'tooth_chart_id',
        'tooth_number',
        'status',
        'color',
        'description',
        'notes',
        'performed_by',
        'performed_at',
        'metadata',
    ];

    protected $casts = [
        'performed_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function toothChart(): BelongsTo
    {
        return $this->belongsTo(ToothChartModel::class, 'tooth_chart_id');
    }

    public function performedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'performed_by');
    }

    public function scopeForToothChart($query, int $toothChartId)
    {
        return $query->where('tooth_chart_id', $toothChartId);
    }

    public function scopeForTooth($query, string $toothNumber)
    {
        return $query->where('tooth_number', $toothNumber);
    }
}
