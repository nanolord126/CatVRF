<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class FashionEmailLog extends Model
{
    protected $table = 'fashion_email_logs';
    protected $fillable = ['campaign_id', 'user_id', 'subject', 'content', 'sent_at', 'correlation_id'];

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(FashionEmailCampaign::class, 'campaign_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
