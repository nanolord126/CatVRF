<?php declare(strict_types=1);

namespace Modules\Common\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class UserDeviceFingerprint extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    use HasEcosystemFeatures;
    
        protected $guarded = [];
    
        protected $casts = [
            'screen_resolution' => 'array',
            'browser_features' => 'array',
            'device_memory' => 'float',
            'hardware_concurrency' => 'integer',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
            'correlation_id' => 'string',
            'tenant_id' => 'string',
            'fingerprint_hash' => 'string',
            'is_trusted' => 'boolean',
            'risk_score' => 'integer',
        ];
    
        protected static function booted(): void
        {
            static::creating(function (UserDeviceFingerprint $model) {
                $model->correlation_id ??= Str::uuid();
                $model->tenant_id ??= Auth::guard('tenant')->id();
                $model->is_trusted ??= false;
                $model->risk_score ??= 0;
                $model->last_seen_at ??= now();
    
                // Генерируем хэш отпечатка для быстрого поиска дубликатов
                if ($model->fingerprint_hash === null) {
                    $fingerprint_data = [
                        'screen_resolution' => json_encode($model->screen_resolution ?? []),
                        'browser_features' => json_encode($model->browser_features ?? []),
                        'device_memory' => $model->device_memory,
                        'hardware_concurrency' => $model->hardware_concurrency,
                    ];
                    $model->fingerprint_hash = hash('sha256', json_encode($fingerprint_data));
                }
    
                Log::channel('security')->info('UserDeviceFingerprint creating', [
                    'correlation_id' => $model->correlation_id,
                    'user_id' => $model->user_id,
                    'fingerprint_hash' => $model->fingerprint_hash,
                ]);
            });
    
            static::created(function (UserDeviceFingerprint $model) {
                try {
                    AuditLog::create([
                        'entity_type' => UserDeviceFingerprint::class,
                        'entity_id' => $model->id,
                        'action' => 'created',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => [
                            'user_id' => $model->user_id,
                            'fingerprint_hash' => $model->fingerprint_hash,
                            'is_trusted' => $model->is_trusted,
                            'risk_score' => $model->risk_score,
                        ],
                        'metadata' => [
                            'device_memory' => $model->device_memory,
                            'hardware_concurrency' => $model->hardware_concurrency,
                            'screen_resolution' => $model->screen_resolution,
                            'browser_features' => $model->browser_features,
                        ],
                    ]);
    
                    Log::channel('security')->info('UserDeviceFingerprint created', [
                        'correlation_id' => $model->correlation_id,
                        'fingerprint_id' => $model->id,
                        'user_id' => $model->user_id,
                        'risk_score' => $model->risk_score,
                    ]);
                } catch (Throwable $e) {
                    Log::error('UserDeviceFingerprint audit creation failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
    
            static::updating(function (UserDeviceFingerprint $model) {
                $model->correlation_id ??= Str::uuid();
    
                Log::channel('security')->info('UserDeviceFingerprint updating', [
                    'correlation_id' => $model->correlation_id,
                    'fingerprint_id' => $model->id,
                ]);
            });
    
            static::updated(function (UserDeviceFingerprint $model) {
                try {
                    AuditLog::create([
                        'entity_type' => UserDeviceFingerprint::class,
                        'entity_id' => $model->id,
                        'action' => 'updated',
                        'user_id' => Auth::id(),
                        'tenant_id' => $model->tenant_id,
                        'correlation_id' => $model->correlation_id,
                        'changes' => $model->getChanges(),
                        'metadata' => [
                            'is_trusted' => $model->is_trusted,
                            'risk_score' => $model->risk_score,
                            'last_seen_at' => $model->last_seen_at?->toIso8601String(),
                        ],
                    ]);
    
                    Log::channel('security')->info('UserDeviceFingerprint updated', [
                        'correlation_id' => $model->correlation_id,
                        'fingerprint_id' => $model->id,
                    ]);
                } catch (Throwable $e) {
                    Log::error('UserDeviceFingerprint audit update failed', [
                        'correlation_id' => $model->correlation_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            });
        }
    
        public function user(): BelongsTo
        {
            return $this->belongsTo(\App\Models\User::class);
        }
}
