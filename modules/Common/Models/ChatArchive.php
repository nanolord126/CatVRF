<?php

namespace App\Domains\Common\Models;

use App\Traits\Common\HasEcosystemFeatures;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use App\Models\AuditLog;
use Throwable;

/**
 * Модель архива сообщений согласно требованиям 374-ФЗ (Закон Яровой).
 * Хранение метаданных и содержания сообщений в течение 3 лет.
 * Production: полное логирование, audit trail, компаиа трехлетнего хранения.
 */
class ChatArchive extends Model
{
    use HasEcosystemFeatures;

    protected $guarded = [];

    protected $casts = [
        'metadata' => 'array',
        'is_sanitized' => 'boolean',
        'sent_at' => 'datetime',
        'correlation_id' => 'string',
        'tenant_id' => 'string',
        'content_hash' => 'string',
        'compliance_checked_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (ChatArchive $model) {
            $model->correlation_id ??= Str::uuid();
            $model->tenant_id ??= $this->auth->guard('tenant')->id();
            $model->is_sanitized ??= false;
            
            // Вычисляем хэш для проверки целостности контента
            if ($model->content ?? null) {
                $model->content_hash = hash('sha256', $model->content);
            }

            $this->log->channel('chat')->info('ChatArchive creating', [
                'correlation_id' => $model->correlation_id,
                'sender_id' => $model->sender_id,
                'receiver_id' => $model->receiver_id,
            ]);
        });

        static::created(function (ChatArchive $model) {
            try {
                Audit$this->log->create([
                    'entity_type' => ChatArchive::class,
                    'entity_id' => $model->id,
                    'action' => 'created',
                    'user_id' => $this->auth->id(),
                    'tenant_id' => $model->tenant_id,
                    'correlation_id' => $model->correlation_id,
                    'changes' => [
                        'sender_id' => $model->sender_id,
                        'receiver_id' => $model->receiver_id,
                        'sent_at' => $model->sent_at?->toIso8601String(),
                    ],
                    'metadata' => [
                        'compliance_374ФЗ' => true,
                        'content_hash' => $model->content_hash,
                        'is_sanitized' => $model->is_sanitized,
                        'retention_until' => $model->sent_at?->addYears(3)->toIso8601String(),
                    ],
                ]);

                $this->log->channel('chat')->info('ChatArchive created (374-ФЗ compliant)', [
                    'correlation_id' => $model->correlation_id,
                    'archive_id' => $model->id,
                    'content_hash' => $model->content_hash,
                ]);
            } catch (Throwable $e) {
                $this->log->error('ChatArchive audit creation failed', [
                    'correlation_id' => $model->correlation_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::updating(function (ChatArchive $model) {
            $model->correlation_id ??= Str::uuid();
            
            $this->log->channel('chat')->info('ChatArchive updating', [
                'correlation_id' => $model->correlation_id,
                'archive_id' => $model->id,
            ]);
        });

        static::updated(function (ChatArchive $model) {
            try {
                Audit$this->log->create([
                    'entity_type' => ChatArchive::class,
                    'entity_id' => $model->id,
                    'action' => 'updated',
                    'user_id' => $this->auth->id(),
                    'tenant_id' => $model->tenant_id,
                    'correlation_id' => $model->correlation_id,
                    'changes' => $model->getChanges(),
                    'metadata' => [
                        'is_sanitized' => $model->is_sanitized,
                        'compliance_checked_at' => $model->compliance_checked_at?->toIso8601String(),
                    ],
                ]);

                $this->log->channel('chat')->info('ChatArchive updated', [
                    'correlation_id' => $model->correlation_id,
                    'archive_id' => $model->id,
                ]);
            } catch (Throwable $e) {
                $this->log->error('ChatArchive audit update failed', [
                    'correlation_id' => $model->correlation_id,
                    'error' => $e->getMessage(),
                ]);
            }
        });

        static::deleting(function (ChatArchive $model) {
            $this->log->channel('chat')->warning('ChatArchive deletion attempted', [
                'correlation_id' => $model->correlation_id,
                'archive_id' => $model->id,
                'sent_at' => $model->sent_at,
                'reason' => 'Deletion of archived chats should be restricted by 374-ФЗ',
            ]);
        });
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'sender_id');
    }

    public function receiver(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'receiver_id');
    }
}
