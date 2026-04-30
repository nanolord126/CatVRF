<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * PeerReviewModel — Layer 9: Database/Persistence Layer (Eloquent Model)
 */
final class PeerReviewModel extends Model
{
    use HasFactory;

    protected $table = 'staff_peer_reviews';

    protected $fillable = [
        'tenant_id',
        'reviewer_id',
        'reviewee_id',
        'rating',
        'feedback',
        'strengths',
        'areas_for_improvement',
        'status',
        'reviewed_at',
        'metadata',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
        'metadata' => 'json',
    ];

    public function reviewer()
    {
        return $this->belongsTo(EmployeeModel::class, 'reviewer_id');
    }

    public function reviewee()
    {
        return $this->belongsTo(EmployeeModel::class, 'reviewee_id');
    }
}
