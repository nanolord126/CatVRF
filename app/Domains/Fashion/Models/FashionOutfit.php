<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class FashionOutfit extends Model
{
    protected $table = 'fashion_outfits';
    protected $fillable = ['user_id', 'tenant_id', 'name', 'occasion', 'season', 'is_favorite', 'times_worn'];
    protected $casts = ['is_favorite' => 'boolean'];

    public function user(): BelongsTo { return $this->belongsTo(\App\Models\User::class, 'user_id'); }
    public function items(): HasMany { return $this->hasMany(FashionOutfitItem::class, 'outfit_id'); }
}
