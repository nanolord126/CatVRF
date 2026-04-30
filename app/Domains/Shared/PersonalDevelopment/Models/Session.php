<?php

declare(strict_types=1);

namespace App\Domains\PersonalDevelopment\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

final class Session extends Model
{
    use TenantScoped;

    protected $table = 'pd_sessions';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'coach_id',
        'client_id',
        'scheduled_at',
        'duration_minutes',
        'status',
        'video_link',
        'notes_after',
        'amount_kopecks',
        'correlation_id',
    ];

    protected $hidden = [
        'id',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'scheduled_at' => 'datetime',
        'duration_minutes' => 'integer',
        'amount_kopecks' => 'integer',
        'tenant_id' => 'integer',
        'coach_id' => 'integer',
        'client_id' => 'integer',
    ];

    /**
     * Преподаватель (коуч), проводящий сессию.
     */
    public function coach(): BelongsTo
    {
        return $this->belongsTo(Coach::class, 'coach_id');
    }

    /**
     * Клиент, проходящий обучение.
     */
    public function client(): BelongsTo
    {
        /** @var User $userModel */
        return $this->belongsTo(User::class, 'client_id');
    }

    /**
     * Завершение сессии с добавлением заметок.
     */
    public function complete(string $notes): void
    {
        $this->update([
            'status' => 'completed',
            'notes_after' => $notes,
            'correlation_id' => (string) Str::uuid(),
        ]);
    }

    /**
     * Booted method for global scoping and UUID generation.
     */
    protected static function booted(): void
    {
        // Изоляция данных на уровне базы (Tenant Scoping)
        self::addGlobalScope('tenant', function (Builder $builder) {
            if (function_exists('tenant') && tenant()?->id) {
                $builder->where('tenant_id', tenant()?->id);
            }
        });

        // Автогенерация UUID и Correlation ID
        self::creating(function (Session $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
            if (empty($model->correlation_id)) {
                $model->correlation_id = (string) Str::uuid();
            }
            if (empty($model->tenant_id) && function_exists('tenant')) {
                $model->tenant_id = (int) tenant()?->id;
            }
        });
    }
}
