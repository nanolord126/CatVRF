<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionUserSizeProfile extends Model
{
    protected $table = 'fashion_user_size_profiles';
    protected $fillable = ['user_id', 'tenant_id', 'height', 'weight', 'chest', 'waist', 'hips', 'shoe_size', 'correlation_id'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
