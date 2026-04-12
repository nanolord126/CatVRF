<?php

declare(strict_types=1);

namespace App\Domains\CRM\Models;
use Illuminate\Database\Eloquent\SoftDeletes;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * CRM Flower Client Profile — профиль клиента цветочного магазина.
 * Любимые цветы, поводы, получатели, корпоративные данные.
 * Канон CatVRF 2026 — PRODUCTION MANDATORY.
 */
final class CrmFlowerClientProfile extends Model
{
use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'crm_flower_client_profiles';

    protected $fillable = [
        'tenant_id',
        'uuid',
        'crm_client_id',
        'correlation_id',
        'favorite_flowers',
        'disliked_flowers',
        'preferred_styles',
        'preferred_colors',
        'average_budget',
        'occasions',
        'packaging_preferences',
        'flower_allergies',
        'frequent_recipients',
        'is_corporate',
        'corporate_holidays',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', static function ($query) {
            if (app()->has('tenant') && tenant()) {
                $query->where('tenant_id', tenant()->id);
            }
        });
    }

    protected $casts = [
        'favorite_flowers' => 'json',
        'disliked_flowers' => 'json',
        'preferred_styles' => 'json',
        'preferred_colors' => 'json',
        'average_budget' => 'decimal:2',
        'occasions' => 'json',
        'packaging_preferences' => 'json',
        'flower_allergies' => 'json',
        'frequent_recipients' => 'json',
        'is_corporate' => 'boolean',
        'corporate_holidays' => 'json',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(CrmClient::class, 'crm_client_id');
    }

    /**
     * Возвращает ближайшие поводы (в течение N дней).
     *
     * @return array<int, array{label: string, date: string, recipient_name: string|null}>
     */
    public function getUpcomingOccasions(int $withinDays = 30): array
    {
        $occasions = $this->occasions ?? [];
        $upcoming = [];

        foreach ($occasions as $occasion) {
            $dateThisYear = date('Y') . '-' . date('m-d', strtotime($occasion['date']));
            $daysUntil = (int) ((strtotime($dateThisYear) - time()) / 86400);

            if ($daysUntil >= 0 && $daysUntil <= $withinDays) {
                $occasion['days_until'] = $daysUntil;
                $upcoming[] = $occasion;
            }
        }

        usort($upcoming, static fn (array $a, array $b): int => $a['days_until'] <=> $b['days_until']);

        return $upcoming;
    }

    /**
     * Добавляет нового частого получателя.
     */
    public function addFrequentRecipient(string $name, string $phone, string $address): void
    {
        $recipients = $this->frequent_recipients ?? [];

        $recipients[] = [
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
        ];

        $this->update(['frequent_recipients' => $recipients]);
    }
}
