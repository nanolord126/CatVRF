<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

/**
 * CRM Interaction — запись одного взаимодействия с клиентом.
 * Звонки, письма, визиты, жалобы, системные события.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmInteraction extends Model
{


    protected static function newFactory(): \Database\Factories\CRM\CrmInteractionFactory
    {
        return \Database\Factories\CRM\CrmInteractionFactory::new();
    }
    protected $table = 'crm_interactions';

    protected $fillable = [
        'tenant_id',
        'crm_client_id',
        'user_id',
        'uuid',
        'correlation_id',
        'type',
        'channel',
        'direction',
        'subject',
        'content',
        'metadata',
        'interacted_at',
    ];

    protected $casts = [
        'metadata' => 'json',
        'interacted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model): void {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
