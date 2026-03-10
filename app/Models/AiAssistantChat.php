<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Stancl\Tenancy\Database\Concerns\BelongsToTenant;

class AiAssistantChat extends Model
{
    use BelongsToTenant;
    protected $guarded = [];
}
