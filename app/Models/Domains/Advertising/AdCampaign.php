<?php

declare(strict_types=1);


namespace App\Models\Domains\Advertising;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * AdCampaign
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class AdCampaign extends Model
{
    use HasFactory;

    protected $table = "ad_campaigns";
    protected $guarded = [];

    protected static function newFactory()
    {
        return \Database\Factories\AdCampaignFactory::new();
    }

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
