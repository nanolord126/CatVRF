<?php

declare(strict_types=1);

namespace App\Domains\Shared\Notifications\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class NotificationExperimentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'experiment_id',
        'user_id',
        'variant',
        'event_type',
        'opened_at',
        'clicked_at',
        'converted_at',
    ];

    protected $casts = [
        'opened_at' => 'datetime',
        'clicked_at' => 'datetime',
        'converted_at' => 'datetime',
    ];

    public function experiment(): BelongsTo
    {
        return $this->belongsTo(NotificationExperiment::class);
    }

    public function scopeByVariant($query, string $variant)
    {
        return $query->where('variant', $variant);
    }

    public function scopeOpened($query)
    {
        return $query->whereNotNull('opened_at');
    }

    public function scopeClicked($query)
    {
        return $query->whereNotNull('clicked_at');
    }

    public function scopeConverted($query)
    {
        return $query->whereNotNull('converted_at');
    }
}
