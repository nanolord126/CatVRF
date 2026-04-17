<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionSocialMention extends Model
{
    protected $table = 'fashion_social_mentions';
    protected $fillable = ['product_id', 'tenant_id', 'platform', 'content', 'likes', 'shares', 'comments', 'sentiment_score', 'correlation_id'];
    protected $casts = ['sentiment_score' => 'decimal:2'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
