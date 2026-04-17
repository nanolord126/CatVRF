<?php declare(strict_types=1);

namespace App\Domains\Fashion\Models;

use Illuminate\Database\Eloquent\Model;

final class FashionEmailCampaign extends Model
{
    protected $table = 'fashion_email_campaigns';
    protected $fillable = ['tenant_id', 'name', 'subject', 'template', 'segmentation_rules', 'trigger_type', 'trigger_config', 'status', 'scheduled_for', 'sent_count', 'opened_count', 'clicked_count', 'converted_count', 'sent_at', 'correlation_id'];
    protected $casts = ['segmentation_rules' => 'array', 'trigger_config' => 'array'];
}
