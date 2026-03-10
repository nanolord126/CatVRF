<?php

namespace App\Models\AI;

use App\Models\User;
use App\Traits\HasCorrelationId;
use App\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class InteriorDesignSession extends Model
{
    use BelongsToTenant, HasCorrelationId, HasAuditLog;

    protected $guarded = [];

    protected $casts = [
        "ai_analysis" => "json",
        "selected_items" => "json",
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}