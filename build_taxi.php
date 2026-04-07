<?php
declare(strict_types=1);

$base = __DIR__ . "/app/Domains/Taxi";
@mkdir($base . "/Models", 0777, true);
@mkdir($base . "/DTOs", 0777, true);
@mkdir($base . "/Services", 0777, true);
@mkdir($base . "/Events", 0777, true);
@mkdir(__DIR__ . "/app/Filament/Tenant/Resources/Taxi/DriverResource/Pages", 0777, true);
@mkdir(__DIR__ . "/app/Http/Controllers/Api/V1/Taxi", 0777, true);

$driverModel = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Driver extends Model
{
    protected \$table = "taxi_drivers";

    protected \$fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id", 
        "first_name", "last_name", "license_number", "phone_number",
        "rating", "is_active", "is_available", "current_lat", "current_lon", "documents", "metadata"
    ];

    protected \$casts = [
        "documents" => "json",
        "metadata" => "json",
        "is_active" => "boolean",
        "is_available" => "boolean",
        "rating" => "decimal:2",
        "current_lat" => "decimal:8",
        "current_lon" => "decimal:8"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder \$query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                \$query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model \$model): void {
            if (!\$model->uuid) {
                \$model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!\$model->correlation_id) {
                \$model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return \$this->belongsTo(Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return \$this->belongsTo(BusinessGroup::class);
    }

    public function rides(): HasMany
    {
        return \$this->hasMany(Ride::class);
    }
}
PHP;
file_put_contents($base . "/Models/Driver.php", trim($driverModel) . "\n");

$rideModel = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Taxi\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;
use App\Models\User;

final class Ride extends Model
{
    protected \$table = "taxi_rides";

    protected \$fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id",
        "driver_id", "customer_id", "pickup_lat", "pickup_lon", "pickup_address",
        "dropoff_lat", "dropoff_lon", "dropoff_address", "status", "price", "distance_km",
        "route_details", "metadata"
    ];

    protected \$casts = [
        "route_details" => "json",
        "metadata" => "json",
        "price" => "decimal:2",
        "pickup_lat" => "decimal:8",
        "pickup_lon" => "decimal:8",
        "dropoff_lat" => "decimal:8",
        "dropoff_lon" => "decimal:8",
        "distance_km" => "decimal:2"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder \$query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                \$query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model \$model): void {
            if (!\$model->uuid) {
                \$model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!\$model->correlation_id) {
                \$model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo
    {
        return \$this->belongsTo(Tenant::class);
    }

    public function driver(): BelongsTo
    {
        return \$this->belongsTo(Driver::class);
    }

    public function customer(): BelongsTo
    {
        return \$this->belongsTo(User::class, "customer_id");
    }
}
PHP;
file_put_contents($base . "/Models/Ride.php", trim($rideModel) . "\n");

$dtoCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Taxi\DTOs;

use Illuminate\Http\Request;

final readonly class OrderRideDto
{
    public function __construct(
        public float \$pickupLat,
        public float \$pickupLon,
        public string \$pickupAddress,
        public float \$dropoffLat,
        public float \$dropoffLon,
        public string \$dropoffAddress,
        public string \$vehicleClass,
        public string \$correlationId,
        public ?int \$customerId = null
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(
            pickupLat: (float) \$request->input("pickup_lat", 0.0),
            pickupLon: (float) \$request->input("pickup_lon", 0.0),
            pickupAddress: (string) \$request->input("pickup_address", ""),
            dropoffLat: (float) \$request->input("dropoff_lat", 0.0),
            dropoffLon: (float) \$request->input("dropoff_lon", 0.0),
            dropoffAddress: (string) \$request->input("dropoff_address", ""),
            vehicleClass: (string) \$request->input("vehicle_class", "standard"),
            correlationId: (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            customerId: auth()->id() ?: null
        );
    }
}
PHP;
file_put_contents($base . "/DTOs/OrderRideDto.php", trim($dtoCode) . "\n");

$serviceCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Taxi\Services;

use App\Domains\Taxi\Models\Driver;
use App\Domains\Taxi\Models\Ride;
use App\Domains\Taxi\DTOs\OrderRideDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class TaxiBookingService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private DatabaseManager \$db,
        private LogManager \$log
    ) {}

    public function createRide(OrderRideDto \$dto): Ride
    {
        \$this->fraud->check([
            "action" => "taxi_order",
            "customer_id" => \$dto->customerId,
            "pickup_lat" => \$dto->pickupLat,
            "pickup_lon" => \$dto->pickupLon,
            "correlation_id" => \$dto->correlationId,
        ]);

        return \$this->db->transaction(function () use (\$dto): Ride {
            \$driver = \$this->findNearestAvailableDriver(\$dto->pickupLat, \$dto->pickupLon);
            
            if (!\$driver) {
                throw new \RuntimeException("No drivers available nearby", 404);
            }

            \$distance = \$this->calculateDistance(\$dto->pickupLat, \$dto->pickupLon, \$dto->dropoffLat, \$dto->dropoffLon);
            \$price = \$this->calculatePrice(\$distance, \$dto->vehicleClass);

            \$ride = Ride::create([
                "driver_id" => \$driver->id,
                "customer_id" => \$dto->customerId,
                "pickup_lat" => \$dto->pickupLat,
                "pickup_lon" => \$dto->pickupLon,
                "pickup_address" => \$dto->pickupAddress,
                "dropoff_lat" => \$dto->dropoffLat,
                "dropoff_lon" => \$dto->dropoffLon,
                "dropoff_address" => \$dto->dropoffAddress,
                "status" => "pending",
                "price" => \$price,
                "distance_km" => \$distance,
                "correlation_id" => \$dto->correlationId,
            ]);

            \$driver->update(["is_available" => false]);

            \$this->audit->log(
                action: "ride_created",
                subjectType: Ride::class,
                subjectId: \$ride->id,
                old: [],
                new: \$ride->toArray(),
                correlationId: \$dto->correlationId
            );

            \$this->log->channel("audit")->info("Taxi ride created successfully", [
                "ride_id" => \$ride->id,
                "driver_id" => \$driver->id,
                "correlation_id" => \$dto->correlationId,
            ]);

            return \$ride;
        });
    }

    private function findNearestAvailableDriver(float \$lat, float \$lon): ?Driver
    {
        return Driver::query()
            ->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians(current_lat)) * cos(radians(current_lon) - radians(?)) + sin(radians(?)) * sin(radians(current_lat)))) AS distance", [\$lat, \$lon, \$lat])
            ->where("is_active", true)
            ->where("is_available", true)
            ->orderBy("distance")
            ->first();
    }

    private function calculateDistance(float \$lat1, float \$lon1, float \$lat2, float \$lon2): float
    {
        \$theta = \$lon1 - \$lon2;
        \$dist = sin(deg2rad(\$lat1)) * sin(deg2rad(\$lat2)) +  cos(deg2rad(\$lat1)) * cos(deg2rad(\$lat2)) * cos(deg2rad(\$theta));
        \$dist = acos(\$dist);
        \$dist = rad2deg(\$dist);
        \$miles = \$dist * 60 * 1.1515;
        return \$miles * 1.609344;
    }

    private function calculatePrice(float \$distanceKm, string \$vehicleClass): float
    {
        \$baseFare = 100.0;
        \$perKm = match (\$vehicleClass) {
            "comfort" => 30.0,
            "business" => 60.0,
            default => 20.0,
        };
        return \$baseFare + (\$distanceKm * \$perKm);
    }
}
PHP;
file_put_contents($base . "/Services/TaxiBookingService.php", trim($serviceCode) . "\n");

$controllerCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Taxi;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Taxi\Services\TaxiBookingService;
use App\Domains\Taxi\DTOs\OrderRideDto;

final class RideController extends Controller
{
    public function __construct(
        private readonly TaxiBookingService \$bookingService,
    ) {}

    public function order(Request \$request): JsonResponse
    {
        \$dto = OrderRideDto::fromRequest(\$request);
        
        try {
            \$ride = \$this->bookingService->createRide(\$dto);
            return new JsonResponse([
                "success" => true,
                "data" => [
                    "ride_id" => \$ride->id,
                    "driver" => [
                        "id" => \$ride->driver->id,
                        "name" => \$ride->driver->first_name . " " . \$ride->driver->last_name,
                        "rating" => \$ride->driver->rating,
                    ],
                    "price" => \$ride->price,
                    "distance_km" => \$ride->distance_km,
                    "status" => \$ride->status,
                ],
                "correlation_id" => \$dto->correlationId,
            ], 201);
        } catch (\Throwable \$e) {
            return new JsonResponse([
                "success" => false,
                "message" => \$e->getMessage(),
                "correlation_id" => \$dto->correlationId,
            ], \$e->getCode() ?: 400);
        }
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Http/Controllers/Api/V1/Taxi/RideController.php", trim($controllerCode) . "\n");

$filamentCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Taxi;

use App\Domains\Taxi\Models\Driver;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\TernaryFilter;
use App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;

final class DriverResource extends Resource
{
    protected static ?string \$model = Driver::class;
    protected static ?string \$navigationIcon = "heroicon-o-truck";
    protected static ?string \$navigationGroup = "Taxi Fleet";
    protected static ?string \$tenantOwnershipRelationshipName = "tenant";

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Section::make("Driver Information")->schema([
                TextInput::make("first_name")->required()->maxLength(255),
                TextInput::make("last_name")->required()->maxLength(255),
                TextInput::make("phone_number")->required()->maxLength(20),
                TextInput::make("license_number")->required()->maxLength(50),
            ])->columns(2),

            Section::make("Status & Location")->schema([
                Checkbox::make("is_active")->default(true),
                Checkbox::make("is_available")->default(true),
                TextInput::make("rating")->numeric()->default(5.0),
                TextInput::make("current_lat")->numeric()->required(),
                TextInput::make("current_lon")->numeric()->required(),
            ])->columns(3),
            
            Section::make("B2B Documents (License, Medical, Insurance)")->schema([
                Repeater::make("documents")
                    ->schema([
                        TextInput::make("doc_type")->required()->placeholder("e.g. License, Med Card"),
                        FileUpload::make("file_url")->acceptedFileTypes(["application/pdf", "image/*"])->directory("taxi/docs")->required(),
                        TextInput::make("expires_at")->type("date"),
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                TextColumn::make("first_name")->searchable()->sortable(),
                TextColumn::make("last_name")->searchable()->sortable(),
                TextColumn::make("phone_number")->searchable(),
                TextColumn::make("rating")->sortable(),
                BooleanColumn::make("is_active"),
                BooleanColumn::make("is_available"),
            ])
            ->filters([
                TernaryFilter::make("is_active"),
                TernaryFilter::make("is_available"),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListDrivers::route("/"),
            "create" => Pages\CreateDriver::route("/create"),
            "edit" => Pages\EditDriver::route("/{record}/edit"),
        ];
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Taxi/DriverResource.php", trim($filamentCode) . "\n");

$filPagesIndex = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDrivers extends ListRecords { protected static string \$resource = DriverResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Taxi/DriverResource/Pages/ListDrivers.php", trim($filPagesIndex) . "\n");

$filPagesCreate = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Resources\Pages\CreateRecord;
class CreateDriver extends CreateRecord { protected static string \$resource = DriverResource::class; }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Taxi/DriverResource/Pages/CreateDriver.php", trim($filPagesCreate) . "\n");

$filPagesEdit = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditDriver extends EditRecord { protected static string \$resource = DriverResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Taxi/DriverResource/Pages/EditDriver.php", trim($filPagesEdit) . "\n");

echo "TAXI DONE";

