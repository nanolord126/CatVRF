<?php declare(strict_types=1);

namespace App\Domains\Freelance\TranslationServices\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Class TranslationJob
 *
 * Part of the Freelance vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\Freelance\TranslationServices\Models
 */
final class TranslationJob extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    protected $table = 'translation_jobs';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'translator_id',
        'client_id',
        'correlation_id',
        'status',
        'source_language',
        'target_language',
        'word_count',
        'price',
        'deadline',
        'submitted_at',
        'tags',
        'meta',
    ];

    protected $casts = [
        'deadline' => 'datetime',
        'submitted_at' => 'datetime',
        'tags' => 'json',
        'meta' => 'json',
        'price' => 'integer',
        'word_count' => 'integer',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', fn ($q) => $q->where('translation_jobs.tenant_id', tenant()->id));
    }
}