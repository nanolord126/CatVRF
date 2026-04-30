<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class MasterScheduleModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_master_schedules';

    protected $fillable = [
        'master_id',
        'venue_id',
        'day_of_week',
        'start_time',
        'end_time',
        'break_start',
        'break_end',
        'is_working_day',
        'effective_from',
        'effective_until',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'break_start' => 'datetime',
        'break_end' => 'datetime',
        'is_working_day' => 'boolean',
        'effective_from' => 'date',
        'effective_until' => 'date',
    ];

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class);
    }

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }
}
