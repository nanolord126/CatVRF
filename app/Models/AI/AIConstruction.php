<?php declare(strict_types=1);

namespace App\Models\AI;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class AIConstruction
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
 * @package App\Models\AI
 */
final class AIConstruction extends Model
{
    use HasFactory, HasUuids, TenantScoped;

    protected $table = 'ai_constructions';

        protected $fillable = [
            'uuid',
            'user_id',
            'tenant_id',
            'correlation_id',
            'constructor_type',
            'input_parameters',
            'used_taste_profile',
            'result',
            'confidence_score',
        ];

        protected $casts = [
            'input_parameters' => 'json',
            'used_taste_profile' => 'json',
            'result' => 'json',
            'confidence_score' => 'float',
        ];

        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
}
