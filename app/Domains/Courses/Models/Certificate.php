declare(strict_types=1);

<?php declare(strict_types=1);

namespace App\Domains\Courses\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final /**
 * Certificate
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class Certificate extends Model
{
    use HasUuids, SoftDeletes;

    protected $fillable = [
        'tenant_id',
        'enrollment_id',
        'course_id',
        'student_id',
        'certificate_number',
        'issued_at',
        'certificate_url',
        'verification_code',
        'student_name',
        'achievement_description',
        'correlation_id',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
    ];

    public function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }
}
