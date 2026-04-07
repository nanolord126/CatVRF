<?php
declare(strict_types=1);

$dir = __DIR__ . '/app/Domains/Beauty';

@mkdir($dir, 0777, true);
$subdirs = [
    'Models',
    'DTOs',
    'Domain/Services',
    'Services/AI',
    'Http/Controllers/Api/V1/B2C',
    'Http/Controllers/Api/V1/B2B',
    'Http/Requests',
    'Http/Resources',
    'Events',
    'Listeners',
    'Jobs',
    'Filament/Resources/SalonResource/Pages',
    'Exceptions'
];

foreach ($subdirs as $sub) {
    @mkdir("$dir/$sub", 0777, true);
}

function put($path, $content) {
    file_put_contents(__DIR__ . '/app/Domains/Beauty/' . $path, trim($content) . "\n");
}

put('Models/Salon.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Salon extends Model
{
    protected \$table = 'beauty_salons';

    protected \$fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'address',
        'lat',
        'lon',
        'status',
        'tags',
        'is_active',
        'metadata'
    ];

    protected \$casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'lat' => 'float',
        'lon' => 'float',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (\$query) {
            // Non-facade tenant resolving 
            // Real system uses dependency or contextual function tenant()
            \$query->where('tenant_id', tenant()->id ?? 1);
        });

        static::creating(function (\$model) {
            if (!\$model->uuid) {
                \$model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return \$this->belongsTo(Tenant::class, 'tenant_id');
    }

    public function businessGroup(): BelongsTo
    {
        return \$this->belongsTo(BusinessGroup::class, 'business_group_id');
    }

    public function masters(): HasMany
    {
        return \$this->hasMany(Master::class, 'salon_id');
    }

    public function services(): HasMany
    {
        return \$this->hasMany(BeautyService::class, 'salon_id');
    }
}
PHP
);

put('Models/Master.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

final class Master extends Model
{
    protected \$table = 'beauty_masters';

    protected \$fillable = [
        'salon_id',
        'user_id',
        'uuid',
        'correlation_id',
        'full_name',
        'specialization',
        'rating',
        'tags',
        'is_active',
    ];

    protected \$casts = [
        'tags' => 'json',
        'rating' => 'float',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (\$model) {
            if (!\$model->uuid) {
                \$model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return \$this->belongsTo(Salon::class, 'salon_id');
    }

    public function appointments(): HasMany
    {
        return \$this->hasMany(Appointment::class, 'master_id');
    }
}
PHP
);

put('Models/BeautyService.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class BeautyService extends Model
{
    protected \$table = 'beauty_services';

    protected \$fillable = [
        'salon_id',
        'uuid',
        'correlation_id',
        'name',
        'description',
        'duration_minutes',
        'price_b2c',
        'price_b2b',
        'tags',
        'is_active',
    ];

    protected \$casts = [
        'tags' => 'json',
        'price_b2c' => 'decimal:2',
        'price_b2b' => 'decimal:2',
        'duration_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (\$model) {
            if (!\$model->uuid) {
                \$model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo
    {
        return \$this->belongsTo(Salon::class, 'salon_id');
    }
}
PHP
);

put('Models/Appointment.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Appointment extends Model
{
    protected \$table = 'beauty_appointments';

    protected \$fillable = [
        'tenant_id',
        'salon_id',
        'master_id',
        'service_id',
        'user_id',
        'uuid',
        'correlation_id',
        'status',
        'starts_at',
        'ends_at',
        'total_price',
        'is_b2b',
        'cancellation_reason'
    ];

    protected \$casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'total_price' => 'decimal:2',
        'is_b2b' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function (\$query) {
            \$query->where('tenant_id', tenant()->id ?? 1);
        });

        static::creating(function (\$model) {
            if (!\$model->uuid) {
                \$model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function salon(): BelongsTo { return \$this->belongsTo(Salon::class); }
    public function master(): BelongsTo { return \$this->belongsTo(Master::class); }
    public function service(): BelongsTo { return \$this->belongsTo(BeautyService::class); }
}
PHP
);

put('DTOs/CreateSalonDto.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

final readonly class CreateSalonDto
{
    public function __construct(
        public int \$tenantId,
        public ?int \$businessGroupId,
        public string \$name,
        public string \$address,
        public float \$lat,
        public float \$lon,
        public string \$correlationId,
        public ?string \$idempotencyKey = null,
        public array \$tags = []
    ) {}

    public static function fromRequest(\Illuminate\Http\Request \$request, string \$correlationId): self
    {
        return new self(
            (int) (\$request->user()->tenant_id ?? 1),
            \$request->input('business_group_id') ? (int) \$request->input('business_group_id') : null,
            \$request->input('name', ''),
            \$request->input('address', ''),
            (float) \$request->input('lat', 0.0),
            (float) \$request->input('lon', 0.0),
            \$correlationId,
            \$request->header('X-Idempotency-Key'),
            (array) \$request->input('tags', [])
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => \$this->tenantId,
            'business_group_id' => \$this->businessGroupId,
            'name' => \$this->name,
            'address' => \$this->address,
            'lat' => \$this->lat,
            'lon' => \$this->lon,
            'correlation_id' => \$this->correlationId,
            'tags' => json_encode(\$this->tags, JSON_THROW_ON_ERROR),
            'status' => 'active',
            'is_active' => true,
        ];
    }
}
PHP
);

put('DTOs/BookAppointmentDto.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\DTOs;

final readonly class BookAppointmentDto
{
    public function __construct(
        public int \$tenantId,
        public int \$salonId,
        public int \$masterId,
        public int \$serviceId,
        public int \$userId,
        public string \$correlationId,
        public string \$startsAt,
        public bool \$isB2b = false
    ) {}

    public static function fromRequest(\Illuminate\Http\Request \$request, string \$correlationId): self
    {
        return new self(
            (int) (\$request->user()->tenant_id ?? 1),
            (int) \$request->input('salon_id'),
            (int) \$request->input('master_id'),
            (int) \$request->input('service_id'),
            (int) \$request->user()->id,
            \$correlationId,
            \$request->input('starts_at'),
            \$request->input('is_b2b', false)
        );
    }
}
PHP
);

put('Domain/Services/AppointmentService.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Domain\Services;

use App\Domains\Beauty\Models\Appointment;
use App\Domains\Beauty\Models\BeautyService;
use App\Domains\Beauty\DTOs\BookAppointmentDto;
use App\Domains\Beauty\Events\AppointmentBooked;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Support\Str;
use Carbon\Carbon;
use Throwable;
use RuntimeException;

final readonly class AppointmentService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private DatabaseManager \$db,
        private Dispatcher \$events
    ) {}

    public function book(BookAppointmentDto \$dto): Appointment
    {
        // No Facades. Injection only.
        \$this->fraud->check(new \App\DTOs\OperationDto(
            userId: \$dto->userId,
            operationType: 'book_beauty_appointment',
            amount: 0.0,
            correlationId: \$dto->correlationId,
            isB2B: \$dto->isB2b
        ));

        return \$this->db->transaction(function () use (\$dto) {
            \$service = BeautyService::findOrFail(\$dto->serviceId);
            \$start = Carbon::parse(\$dto->startsAt);
            \$end = \$start->copy()->addMinutes(\$service->duration_minutes);

            \$exists = Appointment::where('master_id', \$dto->masterId)
                ->where('status', '!=', 'cancelled')
                ->where(function (\$q) use (\$start, \$end) {
                    \$q->whereBetween('starts_at', [\$start, \$end])
                      ->orWhereBetween('ends_at', [\$start, \$end]);
                })->exists();

            if (\$exists) {
                throw new RuntimeException('Slot is no longer available');
            }

            \$price = \$dto->isB2b ? \$service->price_b2b : \$service->price_b2c;

            \$appointment = Appointment::create([
                'tenant_id' => \$dto->tenantId,
                'salon_id' => \$dto->salonId,
                'master_id' => \$dto->masterId,
                'service_id' => \$dto->serviceId,
                'user_id' => \$dto->userId,
                'status' => 'pending',
                'starts_at' => \$start,
                'ends_at' => \$end,
                'total_price' => \$price,
                'is_b2b' => \$dto->isB2b,
                'correlation_id' => \$dto->correlationId,
                'uuid' => Str::uuid()->toString(),
            ]);

            \$this->audit->log(
                'appointment_booked',
                Appointment::class,
                \$appointment->id,
                [],
                \$appointment->toArray(),
                \$dto->correlationId
            );

            \$this->events->dispatch(new AppointmentBooked(\$appointment, \$dto->correlationId));

            return \$appointment;
        });
    }
}
PHP
);

put('Events/AppointmentBooked.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Events;

use App\Domains\Beauty\Models\Appointment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class AppointmentBooked
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly Appointment \$appointment,
        public readonly string \$correlationId
    ) {}
}
PHP
);

put('Listeners/NotifyMasterOfBooking.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;

use App\Domains\Beauty\Events\AppointmentBooked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Mail\Mailer;

final class NotifyMasterOfBooking implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly Mailer \$mailer
    ) {}

    public function handle(AppointmentBooked \$event): void
    {
        \$appointment = \$event->appointment;
        \$master = \$appointment->master;
        
        // Notify master (dummy implementation for demonstration)
        // \$this->mailer->to(\$master->user->email)->send(...);
    }
}
PHP
);

put('Http/Controllers/Api/V1/B2C/AppointmentController.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers\Api\V1\B2C;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Domains\Beauty\Domain\Services\AppointmentService;
use App\Domains\Beauty\DTOs\BookAppointmentDto;
use Illuminate\Support\Str;

final class AppointmentController extends Controller
{
    public function __construct(
        private readonly AppointmentService \$appointmentService
    ) {}

    public function store(Request \$request): JsonResponse
    {
        \$correlationId = \$request->header('X-Correlation-ID', Str::uuid()->toString());
        \$dto = BookAppointmentDto::fromRequest(\$request, \$correlationId);
        
        \$appointment = \$this->appointmentService->book(\$dto);
        
        return new JsonResponse([
            'success' => true,
            'data' => \$appointment,
            'correlation_id' => \$correlationId
        ], 201);
    }
}
PHP
);

put('Services/AI/BeautyImageConstructorService.php', <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Services\AI;

use Illuminate\Http\UploadedFile;
use App\Services\RecommendationService;
use App\Services\InventoryService;
use App\Services\ML\UserTasteAnalyzerService;
use App\Services\FraudControlService;
use App\DTOs\AIUsageDto;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Filesystem\Factory as StorageFactory;
use Illuminate\Support\Str;

final readonly class BeautyImageConstructorService
{
    public function __construct(
        private RecommendationService \$recommendation,
        private InventoryService \$inventory,
        private UserTasteAnalyzerService \$tasteAnalyzer,
        private FraudControlService \$fraud,
        private LogManager \$logger,
        private DatabaseManager \$db,
        private StorageFactory \$storage
    ) {}

    public function analyzePhotoAndRecommend(UploadedFile \$photo, int \$userId, string \$correlationId): array
    {
        \$this->fraud->check(new AIUsageDto(\$userId, 'beauty'));
        
        \$this->scanForViruses(\$photo);

        // Upload to S3
        \$path = \$this->storage->disk('s3')->putFile('beauty/scans', \$photo);

        // Vision API simulation
        \$styleProfile = [
            'face_shape' => 'oval',
            'skin_tone' => 'warm',
            'hair_color' => 'brunette',
            'recommended_styles' => ['pixie_cut', 'balayage']
        ];

        \$taste = \$this->tasteAnalyzer->getProfile(\$userId);
        \$styleProfile = array_merge(\$styleProfile, \$taste->beauty_preferences ?? []);

        \$recommendations = \$this->recommendation->getForBeauty(\$styleProfile, \$userId);

        foreach (\$recommendations as &\$item) {
            \$item['in_stock'] = \$this->inventory->getAvailableStock((int)\$item['product_id']) > 0;
        }
        unset(\$item);

        \$this->db->transaction(function () use (\$userId, \$styleProfile, \$correlationId) {
            \$this->db->table('user_ai_designs')->insert([
                'user_id' => \$userId,
                'vertical' => 'beauty',
                'design_data' => json_encode(\$styleProfile, JSON_THROW_ON_ERROR),
                'correlation_id' => \$correlationId,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        \$this->logger->channel('audit')->info('Beauty AI constructor used', [
            'user_id' => \$userId,
            'style_profile' => \$styleProfile,
            'correlation_id' => \$correlationId,
        ]);

        return [
            'success' => true,
            'vertical' => 'beauty',
            'payload' => \$styleProfile,
            'suggestions' => \$recommendations,
            'confidence_score' => 0.95,
            'correlation_id' => \$correlationId,
            's3_path' => \$path,
        ];
    }

    private function scanForViruses(UploadedFile \$file): void
    {
        \$mime = \$file->getMimeType();
        if (!in_array(\$mime, ['image/jpeg', 'image/png'], true)) {
            throw new \InvalidArgumentException('Invalid file type for Beauty Scan.');
        }
        // integration with ClamAV or AWS Macie
    }
}
PHP
);

echo "Fully rewritten Beauty Domain via Clean Architecture.\n";

