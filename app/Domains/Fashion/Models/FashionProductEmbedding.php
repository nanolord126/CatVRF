<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionProductEmbedding extends Model
{
    protected $table = 'fashion_product_embeddings';
    protected $fillable = ['product_id', 'tenant_id', 'embedding', 'embedding_dimension', 'correlation_id'];
    protected $casts = ['embedding' => 'array'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(FashionProduct::class, 'product_id');
    }
}
