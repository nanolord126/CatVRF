<?php declare(strict_types=1);

namespace App\Models\Dental;



use Illuminate\Http\Request;
use Psr\Log\LoggerInterface;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

final class DentalReview extends Model
{
    public function __construct(
        private readonly Request $request,
        private readonly LoggerInterface $logger,
    ) {}

    use HasFactory, SoftDeletes;

        protected $table = 'dental_reviews';

        protected $fillable = [
            'uuid',
            'tenant_id',
            'clinic_id',
            'dentist_id',
            'client_id',
            'rating',
            'comment',
            'is_verified',
            'correlation_id',
            'tags',
        ];

        protected $casts = [
            'tags' => 'json',
            'rating' => 'integer',
            'is_verified' => 'boolean',
            'tenant_id' => 'integer',
        ];

    /**
     * Boot logic for automatic UUID and tenant scoping.
     */
    protected static function booted(): void
    {
        static::creating(function (self $model) {
            $model->uuid = $model->uuid ?? (string) Str::uuid();
            $model->correlation_id = $model->correlation_id ?? $this->request->header('X-Correlation-ID', (string) Str::uuid());

            if (empty($model->tenant_id) && function_exists('tenant') && tenant()) {
                $model->tenant_id = tenant()->id;
            }
        });

        static::created(function (self $model) {
            // Re-calculate dentist and clinic ratings
            $model->recalculateRatings();
        });

        static::addGlobalScope('tenant', function ($builder) {
            if (function_exists('tenant') && tenant()) {
                $builder->where('tenant_id', tenant()->id);
            }
        });
    }

    /**
     * Relations: Clinic being reviewed.
     */
    public function clinic(): BelongsTo
    {
        return $this->belongsTo(DentalClinic::class, 'clinic_id');
    }

    /**
     * Relations: Dentist being reviewed.
     */
    public function dentist(): BelongsTo
    {
        return $this->belongsTo(Dentist::class, 'dentist_id');
    }

    /**
     * Recalculate clinic and dentist ratings on review creation.
     */
    public function recalculateRatings(): void
    {
        $clinicReviews = self::where('clinic_id', $this->clinic_id)->where('is_verified', true)->avg('rating');
        $this->clinic()->update(['rating' => (int) ($clinicReviews * 100)]);

        $dentistReviews = self::where('dentist_id', $this->dentist_id)->where('is_verified', true)->avg('rating');
        $this->dentist()->update(['rating' => (int) ($dentistReviews * 100)]);

        $this->logger->info('Professional ratings recalculated', [
            'clinic_id' => $this->clinic_id,
            'dentist_id' => $this->dentist_id,
            'review_id' => $this->id,
            'correlation_id' => $this->correlation_id,
        ]);
    }
}
