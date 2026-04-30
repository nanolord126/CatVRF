<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;

final class FashionInfluencer extends Model
{
    use TenantScoped;

    protected $table = 'fashion_influencers';

    protected $fillable = ['tenant_id', 'name', 'platform', 'handle', 'followers_count', 'engagement_rate', 'category', 'is_active', 'correlation_id'];

    protected $casts = ['is_active' => 'boolean', 'engagement_rate' => 'decimal:2'];
}
