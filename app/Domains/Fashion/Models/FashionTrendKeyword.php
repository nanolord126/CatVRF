<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;

final class FashionTrendKeyword extends Model
{
    protected $table = 'fashion_trend_keywords';
    protected $fillable = ['tenant_id', 'keyword', 'type', 'platform', 'trend_score', 'velocity', 'category', 'correlation_id'];
    protected $casts = ['trend_score' => 'decimal:2', 'velocity' => 'decimal:2'];
}
