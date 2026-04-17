<?php declare(strict_types=1);

namespace App\Domains\Content\VideoEditing\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VideoProject extends Model
{

    protected $table = 'video_projects';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'editor_id',
        'client_id',
        'correlation_id',
        'status',
        'total_kopecks',
        'payout_kopecks',
        'payment_status',
        'project_type',
        'editing_hours',
        'due_date',
        'tags',
    ];

    protected $casts = [
        'total_kopecks' => 'integer',
        'payout_kopecks' => 'integer',
        'editing_hours' => 'integer',
        'due_date' => 'datetime',
        'tags' => 'json',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            if (function_exists('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

}
