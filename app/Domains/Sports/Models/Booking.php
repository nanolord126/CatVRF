<?php declare(strict_types=1);

namespace App\Domains\Sports\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Casts\AsCollection;
use Illuminate\Database\Eloquent\SoftDeletes;

final class Booking extends Model
{
    use SoftDeletes;

    protected $table = 'bookings';
    protected $fillable = [
        'tenant_id',
        'class_id',
        'member_id',
        'trainer_id',
        'type',
        'status',
        'price',
        'class_credits_used',
        'is_trial',
        'notes',
        'attended_at',
        'payment_status',
        'correlation_id',
        'tags',
    ];

    protected $casts = [
        'tags' => AsCollection::class,
        'is_trial' => 'boolean',
        'payment_status' => 'boolean',
        'attended_at' => 'datetime',
        'price' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant_id', function ($query) {
            $query->where('tenant_id', tenant('id'));
        });
    }

    public function class(): BelongsTo
    {
        return $this->belongsTo(Class$this->session->class, 'class_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'member_id');
    }

    public function trainer(): BelongsTo
    {
        return $this->belongsTo(Trainer::class);
    }
}
