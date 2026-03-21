<?php declare(strict_types=1);

namespace App\Domains\FarmDirect\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\TenantScoped;
use Database\Factories\FarmOrderFactory;

final class FarmOrder extends Model
{
    use HasFactory, SoftDeletes, TenantScoped;

    protected $table = "farm_orders";
    protected $guarded = [];
    protected $casts = [
        "tags"            => "json",
    ];

    protected static function newFactory()
    {
        return FarmOrderFactory::new();
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

