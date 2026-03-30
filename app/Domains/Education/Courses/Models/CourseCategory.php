<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class CourseCategory extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasUuids, SoftDeletes;

        protected $fillable = [
            'tenant_id',
            'name',
            'description',
            'slug',
            'icon_url',
            'sort_order',
            'course_count',
            'is_active',
            'correlation_id',
        ];

        protected $casts = [
            'is_active' => 'boolean',
        ];

        public function booted(): void
        {
            static::addGlobalScope('tenant', fn ($q) => $q->where('tenant_id', tenant('id') ?? 0));
        }
}
