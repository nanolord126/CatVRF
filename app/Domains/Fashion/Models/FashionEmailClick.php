<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionEmailClick extends Model
{
    protected $table = 'fashion_email_clicks';
    protected $fillable = ['campaign_id', 'tenant_id', 'user_id', 'link', 'clicked_at', 'correlation_id'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FashionEmailCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
