<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Review extends Model
{
    use SoftDeletes;

    protected $table = 'reviews';
    protected $fillable = [
        'tenant_id',
        'studio_id',
        'trainer_id',
        'reviewer_id',
        'booking_id',
        'rating',
        'title',
        'content',
        'categories',
        'verified_purchase',
        'published_at',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'categories' => AsCollection::class,
        'tags' => AsCollection::class,
        'verified_purchase' => 'boolean',
        'published_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function studio(): BelongsTo
    {
        return $this->belongsTo(Studio::class);
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewer_id');
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
