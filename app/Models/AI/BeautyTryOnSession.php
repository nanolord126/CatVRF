<?php

namespace App\Models\AI;

use App\Models\User;
use App\Traits\HasCorrelationId;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class BeautyTryOnSession extends Model
{
    use BelongsToTenant, HasCorrelationId, HasAuditLog;

    protected $guarded = [];

    protected $casts = [
        "parameters" => "json",
        "used_inventory_items" => "json",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}