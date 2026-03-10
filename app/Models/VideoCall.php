<?php

namespace App\Models;

use App\Traits\StrictTenantIsolation;
use App\Traits\HasEcosystemTracing;

use Illuminate\Database\Eloquent\{Model, SoftDeletes};
use App\Traits\Common\{HasEcosystemFeatures, HasEcosystemAuth};

class VideoCall extends Model

{
    use BelongsToTenant;
    use StrictTenantIsolation;
    use HasEcosystemTracing;
    use SoftDeletes, HasEcosystemFeatures, HasEcosystemAuth;

    protected $fillable = [
        'room_id', 'caller_id', 'receiver_id', 'status',
        'started_at', 'ended_at', 'recording_path', 'correlation_id'
    ];

    protected $casts = [
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];

    public function caller() { return $this->belongsTo(User::class, 'caller_id'); }
    public function receiver() { return $this->belongsTo(User::class, 'receiver_id'); }
}










