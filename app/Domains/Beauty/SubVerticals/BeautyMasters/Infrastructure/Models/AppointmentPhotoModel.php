<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class AppointmentPhotoModel extends Model
{
    use HasFactory;

    protected $table = 'beauty_appointment_photos';

    protected $fillable = [
        'appointment_id',
        'type',
        'image_path',
        'thumbnail_path',
        'description',
        'is_public',
        'uploaded_by',
    ];

    protected $casts = [
        'is_public' => 'boolean',
    ];

    public function appointment(): BelongsTo
    {
        return $this->belongsTo(AppointmentModel::class);
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'uploaded_by');
    }
}
