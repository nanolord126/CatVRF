<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use App\Traits\TenantScoped;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * AppointmentPhoto — фото "до/после" для записи.
 * 
 * Хранит фотографии работ мастера (маникюр, макияж, брови и т.д.)
 * для демонстрации клиенту и портфолио мастера.
 */
final class AppointmentPhoto extends Model
{
    use TenantScoped;

    protected $table = 'beauty_appointment_photos';

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'client_id',
        'master_id',
        'photo_type', // before, after, both
        'photo_path',
        'thumbnail_path',
        'description',
        'is_public', // можно ли показывать в портфолио
        'taken_at',
    ];

    protected $casts = [
        'taken_at' => 'datetime',
        'is_public' => 'boolean',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(Appointment::class);
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function master(): BelongsTo
    {
        return $this->belongsTo(Master::class);
    }

    protected static function booted(): void
    {
        self::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id ?? 1);
        });
    }
}
