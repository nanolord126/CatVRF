<?php declare(strict_types=1);

namespace App\Domains\UserProfile\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Tenant;
use App\Models\User;

final class UserProfile extends Model
{
    protected $fillable = [
        'tenant_id',
        'user_id',
        'first_name',
        'last_name',
        'phone',
        'avatar_url',
        'birth_date',
        'gender',
        'preferred_language',
        'timezone',
        'bio',
        'metadata',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'metadata' => 'array',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getFullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getAge(): ?int
    {
        return $this->birth_date ? $this->birth_date->age : null;
    }
}
