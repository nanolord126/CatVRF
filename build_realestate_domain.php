<?php
declare(strict_types=1);

$basePath = __DIR__ . "/app/Domains/RealEstate";
$filamentPath = __DIR__ . "/app/Filament/Tenant/Resources/RealEstate";
$httpPath = __DIR__ . "/app/Http/Controllers/Api/V1/RealEstate";

$dirs = [
    $basePath . "/Models",
    $basePath . "/DTOs",
    $basePath . "/Services/AI",
    $basePath . "/Requests",
    $basePath . "/Resources",
    $basePath . "/Events",
    $basePath . "/Listeners",
    $basePath . "/Jobs",
    $filamentPath,
    $httpPath
];

foreach ($dirs as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
}

$files = [];

// 1. Model: Property
$files[$basePath . "/Models/Property.php"] = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;

/**
 * Real Estate Property Model.
 * Represents a house, apartment, or commercial space.
 * Follows strict 9-layer architecture rules.
 */
final class Property extends Model
{
    protected \$table = "real_estate_properties";

    protected \$fillable = [
        "tenant_id",
        "business_group_id",
        "uuid",
        "correlation_id",
        "title",
        "description",
        "address",
        "lat",
        "lon",
        "price",
        "type", // residential, commercial, land
        "status", // draft, active, sold, rented
        "photos",
        "documents",
        "features",
        "area_sqm",
        "is_active",
    ];

    protected \$casts = [
        "photos" => "json",
        "documents" => "json",
        "features" => "json",
        "is_active" => "boolean",
        "price" => "decimal:2",
        "lat" => "decimal:8",
        "lon" => "decimal:8",
        "area_sqm" => "decimal:2",
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
                \$model->correlation_id = request()->header("X-Correlation-ID") ?? (string) \Illuminate\Support\Str::uuid();
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

    public function listings(): HasMany
    {
        return \$this->hasMany(Listing::class);
    }
}
PHP;

// 2. DTO: CreatePropertyDto
$files[$basePath . "/DTOs/CreatePropertyDto.php"] = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class CreatePropertyDto
{
    public function __construct(
        public int \$tenantId,
        public ?int \$businessGroupId,
        public string \$title,
        public string \$description,
        public string \$address,
        public float \$lat,
        public float \$lon,
        public float \$price,
        public string \$type,
        public array \$photos,
        public array \$documents,
        public array \$features,
        public float \$areaSqm,
        public string \$correlationId,
        public int \$userId
    ) {}

    public static function fromRequest(Request \$request, int \$tenantId, int \$userId, ?int \$businessGroupId = null): self
    {
        return new self(
            tenantId: \$tenantId,
            businessGroupId: \$businessGroupId,
            title: (string) \$request->input("title"),
            description: (string) \$request->input("description"),
            address: (string) \$request->input("address"),
            lat: (float) \$request->input("lat"),
            lon: (float) \$request->input("lon"),
            price: (float) \$request->input("price"),
            type: (string) \$request->input("type", "residential"),
            photos: (array) \$request->input("photos", []),
            documents: (array) \$request->input("documents", []),
            features: (array) \$request->input("features", []),
            areaSqm: (float) \$request->input("area_sqm"),
            correlationId: (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            userId: \$userId
        );
    }

    public function toArray(): array
    {
        return [
            "tenant_id" => \$this->tenantId,
            "business_group_id" => \$this->businessGroupId,
            "title" => \$this->title,
            "description" => \$this->description,
            "address" => \$this->address,
            "lat" => \$this->lat,
            "lon" => \$this->lon,
            "price" => \$this->price,
            "type" => \$this->type,
            "photos" => \$this->photos,
            "documents" => \$this->documents,
            "features" => \$this->features,
            "area_sqm" => \$this->areaSqm,
            "correlation_id" => \$this->correlationId,
            "status" => "active", // Default status on creation
            "is_active" => true,
        ];
    }
}
PHP;

// 3. Service: PropertyService
$files[$basePath . "/Services/PropertyService.php"] = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\CreatePropertyDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use App\Services\IdempotencyService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class PropertyService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private IdempotencyService \$idempotency,
        private DatabaseManager \$db,
        private LogManager \$log,
        private AI\RealEstateDesignConstructorService \$aiConstructor
    ) {}

    /**
     * Creates a new Real Estate Property with full strict validations.
     * Evaluates B2B and B2C context via DTO injection.
     */
    public function create(CreatePropertyDto \$dto, ?string \$idempotencyKey = null): Property
    {
        if (\$idempotencyKey !== null) {
            return \$this->idempotency->execute(\$idempotencyKey, function () use (\$dto): Property {
                return \$this->processCreation(\$dto);
            });
        }

        return \$this->processCreation(\$dto);
    }

    private function processCreation(CreatePropertyDto \$dto): Property
    {
        // 1. Mandatory Fraud Check before mutation
        \$this->fraud->check([
            "action" => "create_property",
            "user_id" => \$dto->userId,
            "tenant_id" => \$dto->tenantId,
            "amount" => \$dto->price,
            "correlation_id" => \$dto->correlationId,
        ]);

        return \$this->db->transaction(function () use (\$dto): Property {
            \$property = Property::create(\$dto->toArray());

            // 2. Audit Log
            \$this->audit->log(
                action: "property_created",
                subjectType: Property::class,
                subjectId: \$property->id,
                old: [],
                new: \$property->toArray(),
                correlationId: \$dto->correlationId
            );

            // 3. Standard application logs
            \$this->log->channel("audit")->info("Real estate property created successfully", [
                "property_id" => \$property->id,
                "correlation_id" => \$dto->correlationId,
                "tenant_id" => \$dto->tenantId,
                "business_group_id" => \$dto->businessGroupId,
            ]);

            // 4. Optional AI Analysis integration (analyze property pictures or generate description improvements)
            // \$this->aiConstructor->analyzeNewProperty(\$property);

            return \$property;
        });
    }

    /**
     * Finds properties by Geo mapping for B2C public searches
     */
    public function findNearbyProperties(float \$lat, float \$lon, float \$radiusKm): \Illuminate\Database\Eloquent\Collection
    {
        // Simple Haversine formulation via Eloquent
        return Property::selectRaw("*, ( 6371 * acos( cos( radians(?) ) * cos( radians( lat ) ) * cos( radians( lon ) - radians(?) ) + sin( radians(?) ) * sin( radians( lat ) ) ) ) AS distance", [\$lat, \$lon, \$lat])
            ->having("distance", "<", \$radiusKm)
            ->where("is_active", true)
            ->where("status", "active")
            ->orderBy("distance")
            ->limit(50)
            ->get();
    }
}
PHP;

// 4. Filament Resource: PropertyResource (B2B usage)
$files[$filamentPath . "/PropertyResource.php"] = <<<PHP
<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Models\Property;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;

/**
 * Filament Resource for B2B representation in Tenant Dashboard.
 * Strict requirements: No static calls inside logic, full DI where possible (Filament constraints limit some DI).
 */
final class PropertyResource extends Resource
{
    protected static ?string \$model = Property::class;
    protected static ?string \$navigationIcon = "heroicon-o-home-modern";
    protected static ?string \$navigationGroup = "Real Estate";
    protected static ?string \$tenantOwnershipRelationshipName = "tenant";

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            TextInput::make("title")->required()->maxLength(255),
            Textarea::make("description")->required(),
            TextInput::make("address")->required(),
            TextInput::make("lat")->numeric()->required(),
            TextInput::make("lon")->numeric()->required(),
            TextInput::make("price")->numeric()->required(),
            Select::make("type")->options([
                "residential" => "Residential (B2C/B2B)",
                "commercial" => "Commercial (B2B)",
                "land" => "Land (B2B)",
            ])->required(),
            Select::make("status")->options([
                "draft" => "Draft",
                "active" => "Active",
                "sold" => "Sold",
                "rented" => "Rented",
            ])->required(),
            TextInput::make("area_sqm")->numeric()->required(),
            
            // Reapeater for Photos
            Repeater::make("photos")
                ->schema([
                    FileUpload::make("url")->image()->directory("real_estate/photos")->required(),
                    TextInput::make("caption")->maxLength(255),
                ])->columnSpanFull(),

            // Repeater for Documents (B2B contracts, plans)
            Repeater::make("documents")
                ->schema([
                    TextInput::make("title")->required(),
                    FileUpload::make("file")->acceptedFileTypes(["application/pdf"])->directory("real_estate/docs")->required(),
                ])->columnSpanFull(),
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                TextColumn::make("title")->searchable()->sortable(),
                TextColumn::make("type")->sortable(),
                TextColumn::make("price")->money("RUB")->sortable(),
                TextColumn::make("status")->sortable(),
            ])
            ->filters([])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListProperties::route("/"),
            "create" => Pages\CreateProperty::route("/create"),
            "edit" => Pages\EditProperty::route("/{record}/edit"),
        ];
    }
}
PHP;

foreach (\$files as \$path => \$content) {
    file_put_contents(\$path, trim(\$content) . "\n");
}
echo "Real Estate Domain scaffolding created successfully.\n";

