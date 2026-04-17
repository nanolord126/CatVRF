<?php declare(strict_types=1);

namespace App\Models\Insurance;


use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class InsuranceReview extends Model
{

    protected $table = 'insurance_reviews';

    protected $fillable = [
        'uuid',
        'tenant_id',
        'company_id',
        'user_id',
        'rating',
        'comment',
        'correlation_id',
    ];

    protected $casts = [
        'rating' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $model) {
            if (empty($model->uuid)) {
                $model->uuid = (string) Str::uuid();
            }
        });

        static::addGlobalScope('tenant', function ($builder) {
            if ($this->guard->check()) {
                $builder->where('tenant_id', $this->guard->user()->tenant_id);
            }
        });
    }

    /**
     * The company being reviewed.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(InsuranceCompany::class, 'company_id');
    }

    /**
     * The user that submitted the review.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
