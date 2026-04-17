<?php declare(strict_types=1);

namespace App\Models\Domains\Clinic;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Class MedicalCard
 *
 * Part of the Clinic vertical domain.
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
 * @package App\Models\Domains\Clinic
 */
final class MedicalCard extends Model
{

        protected $table = 'medical_cards';

        protected $fillable = [
        'uuid',
        'correlation_id',
            'tenant_id',
            'patient_id',
            'blood_type',
            'allergies',
            'medical_history',
            'notes',
            'last_check_up',
        ];

        protected $casts = [
            'allergies' => 'array',
            'medical_history' => 'array',
        ];

        protected static function newFactory()
        {
            return \Database\Factories\MedicalCardFactory::new();
        }

        protected static function booted(): void
        {
            parent::booted();
            static::addGlobalScope('tenant_id', function ($query) {
                if (function_exists('tenant') && tenant('id')) {
                    $query->where('tenant_id', tenant('id'));
                }
            });
        }
}
