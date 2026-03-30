<?php declare(strict_types=1);

namespace App\Domains\Content\VideoEditing\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class VideoEditor extends Model
{
    use HasFactory;
    use HasUuids;
    use SoftDeletes;
    use TenantScoped;

    protected $table = 'video_editors';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'user_id',
        'correlation_id',
        'name',
        'specialties',
        'price_kopecks_per_hour',
        'rating',
        'is_verified',
        'tags',
    ];

    protected $casts = [
        'specialties' => 'json',
        'price_kopecks_per_hour' => 'integer',
        'rating' => 'float',
        'is_verified' => 'boolean',
        'tags' => 'json',
    ];
}
