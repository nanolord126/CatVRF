<?php declare(strict_types=1);

namespace App\Domains\Confectionery\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\ConfectioneryShopFactory;

final class ConfectioneryShop extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = "confectionery_shops";
    protected $guarded = [];

    protected static function newFactory()
    {
        return ConfectioneryShopFactory::new();
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

