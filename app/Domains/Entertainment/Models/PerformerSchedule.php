<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\Entertainer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PerformerSchedule extends Model
{
    use SoftDeletes;

    protected $table = 'performer_schedules';
    protected $fillable = ['tenant_id', 'entertainer_id', 'day_of_week', 'start_time', 'end_time', 'is_available', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'start_time' => 'time',
        'end_time' => 'time',
        'is_available' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function entertainer(): BelongsTo
    {
        return $this->belongsTo(Entertainer::class, 'entertainer_id');
    }
}
