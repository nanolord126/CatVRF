<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionSustainabilityScore extends Model
{
    protected $table = 'fashion_sustainability_scores';
    protected $fillable = ['product_id', 'score', 'breakdown', 'calculated_at'];
    protected $casts = ['score' => 'decimal:2', 'breakdown' => 'array', 'calculated_at' => 'datetime'];

    public function product(): BelongsTo { return $this->belongsTo(FashionProduct::class, 'product_id'); }
}
