<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class TenderStatistic extends Model
{
    use HasFactory;

    protected $fillable = [
        'supplier_id',
        'tenant_id',
        'vertical_id',
        'total_participated',
        'total_won',
        'total_lost',
        'total_withdrawn',
        'total_rejected',
        'average_rating',
        'total_reviews',
        'total_value_won',
        'last_participated_at',
        'last_won_at',
    ];

    protected $casts = [
        'average_rating' => 'decimal:2',
        'total_value_won' => 'decimal:2',
        'last_participated_at' => 'date',
        'last_won_at' => 'date',
    ];

    // Relations
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    // Methods
    public function getWinRate(): float
    {
        if ($this->total_participated === 0) {
            return 0;
        }
        return ($this->total_won / $this->total_participated) * 100;
    }

    public function getCompletionRate(): float
    {
        $completed = $this->total_won + $this->total_lost;
        if ($this->total_participated === 0) {
            return 0;
        }
        return ($completed / $this->total_participated) * 100;
    }

    public function incrementParticipated(): void
    {
        $this->total_participated++;
        $this->last_participated_at = now();
        $this->save();
    }

    public function incrementWon(float $value): void
    {
        $this->total_won++;
        $this->total_value_won += $value;
        $this->last_won_at = now();
        $this->save();
    }

    public function incrementLost(): void
    {
        $this->total_lost++;
        $this->save();
    }

    public function incrementWithdrawn(): void
    {
        $this->total_withdrawn++;
        $this->save();
    }

    public function incrementRejected(): void
    {
        $this->total_rejected++;
        $this->save();
    }

    public function updateRating(int $newRating): void
    {
        $this->total_reviews++;
        $currentTotal = $this->average_rating * ($this->total_reviews - 1);
        $this->average_rating = ($currentTotal + $newRating) / $this->total_reviews;
        $this->save();
    }

    public function toPublicArray(): array
    {
        return [
            'vertical_id' => $this->vertical_id,
            'total_participated' => $this->total_participated,
            'total_won' => $this->total_won,
            'total_lost' => $this->total_lost,
            'total_withdrawn' => $this->total_withdrawn,
            'total_rejected' => $this->total_rejected,
            'average_rating' => $this->average_rating,
            'total_reviews' => $this->total_reviews,
            'win_rate' => $this->getWinRate(),
            'completion_rate' => $this->getCompletionRate(),
            // Supplier ID is NOT included - anonymized
        ];
    }
}
