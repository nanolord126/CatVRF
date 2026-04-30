<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * FSTEC Threat Model
 * 
 * Represents a threat from the FSTEC Threat Database (БДУ ФСТЭК).
 * Used for dynamic threat model updates and compliance documentation.
 * 
 * Reference: https://bdu.fstec.ru/
 * Methodology: Методика ФСТЭК 2021
 */
final class FstecThreat extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'uuid',
        'fstec_id',
        'name',
        'description',
        'threat_type',
        'threat_class',
        'is_relevant',
        'relevance_reason',
        'probability',
        'impact',
        'risk_level',
        'affected_assets',
        'affected_systems',
        'mitigation_measures',
        'is_mitigated',
        'source',
        'source_updated_at',
        'synced_at',
    ];

    protected $casts = [
        'is_relevant' => 'boolean',
        'is_mitigated' => 'boolean',
        'affected_assets' => 'json',
        'affected_systems' => 'json',
        'mitigation_measures' => 'json',
        'source_updated_at' => 'datetime',
        'synced_at' => 'datetime',
    ];

    protected $table = 'fstec_threats';

    // ========================
    // SCOPES
    // ========================

    public function scopeRelevant($query)
    {
        return $query->where('is_relevant', true);
    }

    public function scopeNotMitigated($query)
    {
        return $query->where('is_mitigated', false);
    }

    public function scopeHighRisk($query)
    {
        return $query->whereIn('risk_level', ['high', 'critical']);
    }

    public function scopeByType($query, string $type)
    {
        return $query->where('threat_type', $type);
    }

    public function scopeByClass($query, string $class)
    {
        return $query->where('threat_class', $class);
    }

    // ========================
    // BUSINESS LOGIC
    // ========================

    /**
     * Calculate risk score (1-10) based on probability and impact
     */
    public function calculateRiskScore(): float
    {
        $probabilityScore = match ($this->probability) {
            'very_low' => 1,
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'very_high' => 5,
        };

        $impactScore = match ($this->impact) {
            'very_low' => 1,
            'low' => 2,
            'medium' => 3,
            'high' => 4,
            'very_high' => 5,
        };

        return ($probabilityScore * $impactScore) / 2.5; // Normalize to 1-10
    }

    /**
     * Update risk level based on probability and impact
     */
    public function updateRiskLevel(): void
    {
        $score = $this->calculateRiskScore();
        
        $this->risk_level = match (true) {
            $score <= 2 => 'low',
            $score <= 4 => 'medium',
            $score <= 7 => 'high',
            default => 'critical',
        };

        $this->save();
    }

    /**
     * Mark threat as relevant to CatVRF
     */
    public function markAsRelevant(string $reason): void
    {
        $this->update([
            'is_relevant' => true,
            'relevance_reason' => $reason,
        ]);
    }

    /**
     * Mark threat as mitigated
     */
    public function markAsMitigated(): void
    {
        $this->update(['is_mitigated' => true]);
    }

    /**
     * Get FSTEC #21 measure groups for this threat
     */
    public function getFstecMeasureGroups(): array
    {
        return $this->mitigation_measures ?? [];
    }

    protected static function boot()
    {
        parent::boot();

        // Generate UUID on create
        self::creating(function ($model) {
            $model->uuid ??= \Illuminate\Support\Str::uuid()->toString();
        });
    }
}
