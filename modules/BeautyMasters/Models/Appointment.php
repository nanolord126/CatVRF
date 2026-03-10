<?php

namespace Modules\BeautyMasters\Models;

use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    protected $fillable = ['master_id', 'service_name', 'client_name', 'start_time', 'end_time', 'status'];

    public function master()
    {
        return $this->belongsTo(Master::class);
    }
}
