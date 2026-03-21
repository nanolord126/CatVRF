<?php
declare(strict_types=1);

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Event extends Model
{
    use HasFactory, HasUuids;

    protected $table = "events_b2b";

    protected $fillable = [
        "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
        "title", "start_date"
    ];

    protected $casts = [
        "tags" => "json",
    ];

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
