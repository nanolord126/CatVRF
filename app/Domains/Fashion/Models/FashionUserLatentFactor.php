<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionUserLatentFactor extends Model
{
    use TenantScoped;

    protected $table = 'fashion_user_latent_factors';

    protected $fillable = ['user_id', 'tenant_id', 'factors', 'correlation_id'];

    protected $casts = ['factors' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
