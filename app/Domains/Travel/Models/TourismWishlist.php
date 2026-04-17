<?php declare(strict_types=1);

namespace App\Domains\Travel\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * Tourism Wishlist Model
 * 
 * Model for user wishlist items with AI-powered recommendations.
 * When a user adds a tour to wishlist, the system automatically
 * generates personalized recommendations based on that tour.
 * 
 * @package App\Domains\Travel\Models
 */
final class TourismWishlist extends Model
{
    use HasFactory;

    protected $table = 'tourism_wishlists';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'tour_id',
        'priority',
        'notes',
        'budget_range',
        'preferred_dates',
        'group_size',
        'special_requests',
        'metadata',
    ];

    protected $casts = [
        'priority' => 'integer',
        'budget_range' => 'json',
        'preferred_dates' => 'json',
        'group_size' => 'integer',
        'metadata' => 'json',
    ];

    protected static function booted(): void
    {
        static::creating(function (TourismWishlist $model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });

        static::addGlobalScope('tenant', function ($query) {
            $tenantId = function_exists('tenant') && tenant() ? tenant()->id : 1;
            $query->where('tenant_id', $tenantId);
        });
    }

    public function tour(): BelongsTo
    {
        return $this->belongsTo(Tour::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Check if wishlist item is high priority.
     */
    public function isHighPriority(): bool
    {
        return $this->priority >= 8;
    }

    /**
     * Check if wishlist item has budget specified.
     */
    public function hasBudget(): bool
    {
        return !empty($this->budget_range) && is_array($this->budget_range);
    }

    /**
     * Check if wishlist item has preferred dates.
     */
    public function hasPreferredDates(): bool
    {
        return !empty($this->preferred_dates) && is_array($this->preferred_dates);
    }
}
