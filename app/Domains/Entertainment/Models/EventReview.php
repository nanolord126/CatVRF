<?php declare(strict_types=1);

namespace App\Domains\Entertainment\Models;

use App\Domains\Entertainment\Models\EntertainmentEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

final class EventReview extends Model
{
    use SoftDeletes;

    protected $table = 'event_reviews';
    protected $fillable = ['tenant_id', 'entertainment_event_id', 'reviewer_id', 'rating', 'comment', 'media', 'verified_purchase', 'correlation_id'];
    protected $hidden = [];
    protected $casts = [
        'media' => 'collection',
        'verified_purchase' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check()) {
                $query->where('tenant_id', tenant('id'));
            }
        });
    }

    public function entertainmentEvent(): BelongsTo
    {
        return $this->belongsTo(EntertainmentEvent::class, 'entertainment_event_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }
}
