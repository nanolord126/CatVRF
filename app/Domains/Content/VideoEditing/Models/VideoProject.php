<?php declare(strict_types=1);

namespace App\Domains\Content\VideoEditing\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VideoProject extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

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
}
