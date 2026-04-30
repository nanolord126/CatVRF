<?php

declare(strict_types=1);

namespace App\Models\Legal;

use Carbon\CarbonImmutable;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

final class LegalReview extends Model
{
    protected $table = 'legal_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'lawyer_id',
        'client_id',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'uuid' => 'string',
        'tenant_id' => 'integer',
        'lawyer_id' => 'integer',
        'client_id' => 'integer',
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function client(): BelongsTo
    {
        return $this->belongsTo(User::class, 'client_id');
    }

    public function lawyer(): BelongsTo
    {
        return $this->belongsTo(Lawyer::class, 'lawyer_id');
    }

    public function scopePositive(Builder $query): Builder
    {
        return $query->where('rating', '>=', 4);
    }

    public function scopeCritical(Builder $query): Builder
    {
        return $query->where('rating', '<=', 2);
    }

    public function isVerifiedReview(): bool
    {
        return LegalConsultation::where([
            'client_id' => $this->client_id,
            'lawyer_id' => $this->lawyer_id,
            'status' => 'completed',
        ])->exists();
    }

    public function getStarsDisplay(): string
    {
        return str_repeat('★', $this->rating).str_repeat('☆', 5 - $this->rating);
    }

    public function canClientEdit(): bool
    {
        return $this->created_at->isAfter(CarbonImmutable::now()->subDays(30));
    }

    public function lengthOfComment(): int
    {
        return strlen($this->comment);
    }

    protected static function booted(): void
    {
        self::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->tenant_id = $model->tenant_id ?? (tenant()->id ?? 0);
        });

        self::addGlobalScope('tenant_id', function (Builder $builder) {
            if (function_exists('tenant')) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }
}
