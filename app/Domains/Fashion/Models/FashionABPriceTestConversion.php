<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionABPriceTestConversion extends Model
{
    protected $table = 'fashion_ab_price_test_conversions';
    protected $fillable = ['test_id', 'tenant_id', 'user_id', 'group', 'price', 'converted_at', 'correlation_id'];
    protected $casts = ['price' => 'decimal:2'];

    public function test(): BelongsTo
    {
        return $this->belongsTo(FashionABPriceTest::class, 'test_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
