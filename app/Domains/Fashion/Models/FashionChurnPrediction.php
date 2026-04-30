<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionChurnPrediction extends Model
{
    use TenantScoped;

    protected $table = 'fashion_churn_predictions';

    protected $fillable = ['user_id', 'tenant_id', 'risk_score', 'predicted_at'];

    protected $casts = ['risk_score' => 'decimal:2', 'predicted_at' => 'datetime'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
