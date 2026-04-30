<?php

declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class FashionVisualSearch extends Model
{
    use TenantScoped;

    protected $table = 'fashion_visual_searches';

    protected $fillable = ['user_id', 'tenant_id', 'image_url', 'embedding', 'searched_at', 'correlation_id'];

    protected $casts = ['embedding' => 'array'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
