<?php declare(strict_types=1);

namespace Modules\Advertising\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Creative extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    protected $table = 'ad_creatives';
        protected $fillable = ['campaign_id', 'title', 'content', 'link', 'type', 'erid'];
        protected $casts = ['campaign_id' => 'integer'];
    
        public function campaign(): BelongsTo {
            return $this->belongsTo(Campaign::class);
        }
    
        public function getLabeledLinkAttribute(): string {
            $label = config('advertising.defaults.label');
            return "{$this->link}?erid={$this->erid} ({$label})";
        }
}
