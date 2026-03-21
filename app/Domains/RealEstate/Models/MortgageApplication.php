<?php declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Casts\AsCollection;

/**
 * Model для заявки на ипотеку.
 * Production 2026.
 */
final class MortgageApplication extends Model
{
    use SoftDeletes;

    protected $table = 'mortgage_applications';
    protected $fillable = [
        'tenant_id', 'property_id', 'client_id', 'property_price', 'loan_amount',
        'initial_payment', 'loan_term_months', 'interest_rate', 'bank', 'status',
        'bank_notes', 'submitted_at', 'approved_at', 'correlation_id', 'tags',
    ];

    protected $casts = [
        'property_price' => 'integer',
        'loan_amount' => 'integer',
        'initial_payment' => 'integer',
        'loan_term_months' => 'integer',
        'interest_rate' => 'float',
        'submitted_at' => 'datetime',
        'approved_at' => 'datetime',
        'tags' => AsCollection::class,
    ];

    protected $hidden = ['deleted_at'];

    public function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant('id') ?? 0);
        });
    }

    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class, 'property_id');
    }
}
