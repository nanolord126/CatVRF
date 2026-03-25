declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * Lesson
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Lesson extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'course_id',
        'title',
        'description',
        'content',
        'video_url',
        'duration_minutes',
        'sort_order',
        'is_published',
        'resources',
        'correlation_id',
    ];

    protected $casts = [
        'resources' => 'json',
        'is_published' => 'boolean',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
