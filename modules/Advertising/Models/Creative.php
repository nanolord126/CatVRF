<?php
namespace Modules\Advertising\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Creative extends Model {
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
