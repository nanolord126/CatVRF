<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Behavioral Profile Model
 *
 * Stores user behavioral biometrics patterns for continuous authentication.
 *
 * PRODUCTION MANDATORY — CatVRF 2026 Enterprise Security
 */
final class BehavioralProfile extends Model
{
    use HasFactory;

    protected $table = 'behavioral_profiles';

    protected $fillable = [
        'tenant_id',
        'user_id',
        'typing_patterns',
        'mouse_patterns',
        'touch_patterns',
        'session_patterns',
        'sample_count',
        'last_analyzed_at',
        'is_active',
        'avg_typing_score',
        'avg_mouse_score',
        'avg_overall_score',
    ];

    protected $casts = [
        'typing_patterns' => 'array',
        'mouse_patterns' => 'array',
        'touch_patterns' => 'array',
        'session_patterns' => 'array',
        'sample_count' => 'integer',
        'last_analyzed_at' => 'datetime',
        'is_active' => 'boolean',
        'avg_typing_score' => 'float',
        'avg_mouse_score' => 'float',
        'avg_overall_score' => 'float',
    ];

    /**
     * Relationship to User
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope for active profiles
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope for mature profiles (sufficient samples)
     */
    public function scopeMature($query, int $minSamples = 5)
    {
        return $query->where('sample_count', '>=', $minSamples);
    }

    /**
     * Check if profile is mature (has enough samples for reliable scoring)
     */
    public function isMature(int $minSamples = 5): bool
    {
        return $this->sample_count >= $minSamples;
    }
}
