<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionReviewModeration extends Model
{
    protected $table = 'fashion_review_moderations';
    protected $fillable = ['review_id', 'tenant_id', 'spam_score', 'toxicity_score', 'fake_score', 'sentiment', 'action', 'manual_review_required', 'moderated_at', 'correlation_id'];
    protected $casts = ['spam_score' => 'decimal:2', 'toxicity_score' => 'decimal:2', 'fake_score' => 'decimal:2', 'manual_review_required' => 'boolean'];

    public function review(): BelongsTo
    {
        return $this->belongsTo(FashionReview::class, 'review_id');
    }
}
