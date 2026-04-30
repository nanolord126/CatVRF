<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class BlockedSlotModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_blocked_slots';

    protected $fillable = [
        'venue_id',
        'master_id',
        'start_time',
        'end_time',
        'reason',
        'description',
        'created_by',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];

    public function venue(): BelongsTo
    {
        return $this->belongsTo(VenueModel::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(MasterModel::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }
}
