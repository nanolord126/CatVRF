<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionOutfitItem extends Model
{
    protected $table = 'fashion_outfit_items';
    protected $fillable = ['outfit_id', 'tenant_id', 'wardrobe_item_id'];

    public function outfit(): BelongsTo { return $this->belongsTo(FashionOutfit::class, 'outfit_id'); }
    public function wardrobeItem(): BelongsTo { return $this->belongsTo(FashionVirtualWardrobe::class, 'wardrobe_item_id'); }
}
