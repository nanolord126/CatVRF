<?php
declare(strict_types=1);

$base = __DIR__ . "/app/Domains/RealEstate";
@mkdir($base . "/Models", 0777, true);
@mkdir($base . "/DTOs", 0777, true);
@mkdir($base . "/Services/AI", 0777, true);
@mkdir(__DIR__ . "/app/Filament/Tenant/Resources/RealEstate/PropertyResource/Pages", 0777, true);
@mkdir(__DIR__ . "/app/Http/Controllers/Api/V1/RealEstate", 0777, true);

$propertyCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Property extends Model
{
    protected \$table = "real_estate_properties";

    protected \$fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id", "title",
        "description", "address", "lat", "lon", "price", "type", "status",
        "photos", "documents", "features", "area_sqm", "is_active"
    ];

    protected \$casts = [
        "photos" => "json", "documents" => "json", "features" => "json",
        "is_active" => "boolean", "price" => "decimal:2", "lat" => "decimal:8",
        "lon" => "decimal:8", "area_sqm" => "decimal:2"
    ];

    protected static function booted(): void
    {
        static::addGlobalScope("tenant", function (Builder \$query): void {
            if (app()->bound("tenant") && app("tenant") instanceof Tenant) {
                \$query->where("tenant_id", app("tenant")->id);
            }
        });

        static::creating(function (Model \$model): void {
            if (!\$model->uuid) { \$model->uuid = (string) \Illuminate\Support\Str::uuid(); }
            if (!\$model->correlation_id) {
                \$model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function tenant(): BelongsTo { return \$this->belongsTo(Tenant::class); }
    public function businessGroup(): BelongsTo { return \$this->belongsTo(BusinessGroup::class); }
}
PHP;
file_put_contents($base . "/Models/Property.php", trim($propertyCode) . "\n");

$dtoCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\DTOs;

use Illuminate\Http\Request;

final readonly class SearchPropertyDto
{
    public function __construct(
        public float \$lat,
        public float \$lon,
        public float \$radiusKm,
        public ?string \$type,
        public ?float \$minPrice,
        public ?float \$maxPrice,
        public string \$correlationId
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(
            lat: (float) \$request->input("lat", 0.0),
            lon: (float) \$request->input("lon", 0.0),
            radiusKm: (float) \$request->input("radius_km", 10.0),
            type: \$request->input("type"),
            minPrice: \$request->has("min_price") ? (float) \$request->input("min_price") : null,
            maxPrice: \$request->has("max_price") ? (float) \$request->input("max_price") : null,
            correlationId: (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid())
        );
    }
}
PHP;
file_put_contents($base . "/DTOs/SearchPropertyDto.php", trim($dtoCode) . "\n");

$serviceCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\RealEstate\Services;

use App\Domains\RealEstate\Models\Property;
use App\Domains\RealEstate\DTOs\SearchPropertyDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class PropertyService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private DatabaseManager \$db,
        private LogManager \$log
    ) {}

    public function searchNearby(SearchPropertyDto \$dto): Collection
    {
        \$this->fraud->check([
            "action" => "search_real_estate",
            "lat" => \$dto->lat,
            "lon" => \$dto->lon,
            "correlation_id" => \$dto->correlationId,
        ]);

        \$query = Property::query()
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [\$dto->lat, \$dto->lon, \$dto->lat]
            )
            ->having("distance", "<", \$dto->radiusKm)
            ->where("is_active", true)
            ->where("status", "active");

        if (\$dto->type !== null) { \$query->where("type", \$dto->type); }
        if (\$dto->minPrice !== null) { \$query->where("price", ">=", \$dto->minPrice); }
        if (\$dto->maxPrice !== null) { \$query->where("price", "<=", \$dto->maxPrice); }

        \$results = \$query->orderBy("distance")->limit(100)->get();

        \$this->log->channel("audit")->info("Real Estate public search executed", [
            "results_count" => \$results->count(),
            "correlation_id" => \$dto->correlationId,
        ]);

        return \$results;
    }

    public function toggleStatus(Property \$property, string \$newStatus, string \$correlationId): Property
    {
        return \$this->db->transaction(function () use (\$property, \$newStatus, \$correlationId): Property {
            \$oldStatus = \$property->status;
            \$property->update(["status" => \$newStatus]);
            
            \$this->audit->log(
                action: "property_status_changed",
                subjectType: Property::class,
                subjectId: \$property->id,
                old: ["status" => \$oldStatus],
                new: ["status" => \$newStatus],
                correlationId: \$correlationId
            );

            return \$property;
        });
    }
}
PHP;
file_put_contents($base . "/Services/PropertyService.php", trim($serviceCode) . "\n");

$controllerCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\RealEstate;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\RealEstate\Services\PropertyService;
use App\Domains\RealEstate\DTOs\SearchPropertyDto;

final class PropertyController extends Controller
{
    public function __construct(
        private readonly PropertyService \$propertyService,
    ) {}

    public function search(Request \$request): JsonResponse
    {
        \$dto = SearchPropertyDto::fromRequest(\$request);
        \$properties = \$this->propertyService->searchNearby(\$dto);

        \$responsePayload = \$properties->map(function (\$property) {
            return [
                "id" => \$property->id,
                "title" => \$property->title,
                "price" => \$property->price,
                "type" => \$property->type,
                "lat" => \$property->lat,
                "lon" => \$property->lon,
                "distance" => round((float) \$property->distance, 2),
                "photos" => \$property->photos,
            ];
        });

        return new JsonResponse([
            "success" => true,
            "data" => \$responsePayload,
            "correlation_id" => \$dto->correlationId,
        ], 200);
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Http/Controllers/Api/V1/RealEstate/PropertyController.php", trim($controllerCode) . "\n");

$filamentCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\RealEstate;

use App\Domains\RealEstate\Models\Property;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;

final class PropertyResource extends Resource
{
    protected static ?string \$model = Property::class;
    protected static ?string \$navigationIcon = "heroicon-o-home-modern";
    protected static ?string \$navigationGroup = "Real Estate";
    protected static ?string \$tenantOwnershipRelationshipName = "tenant";

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Section::make("Property Information")->schema([
                TextInput::make("title")->required()->maxLength(255),
                Textarea::make("description")->required(),
                TextInput::make("address")->required(),
                TextInput::make("lat")->numeric()->required(),
                TextInput::make("lon")->numeric()->required(),
                TextInput::make("area_sqm")->numeric()->required(),
            ])->columns(2),

            Section::make("Pricing & Classification")->schema([
                TextInput::make("price")->numeric()->required(),
                Select::make("type")->options([
                    "residential" => "Residential",
                    "commercial" => "Commercial",
                    "land" => "Land",
                ])->required(),
                Select::make("status")->options([
                    "draft" => "Draft",
                    "active" => "Active",
                    "sold" => "Sold",
                    "rented" => "Rented",
                ])->required(),
            ])->columns(3),
            
            Section::make("Media")->schema([
                Repeater::make("photos")
                    ->schema([
                        FileUpload::make("url")->image()->directory("real_estate/photos")->required(),
                        TextInput::make("caption")->maxLength(255),
                    ])
                    ->defaultItems(1)
                    ->columnSpanFull(),
            ]),

            Section::make("B2B Documents & Contracts")->schema([
                Repeater::make("documents")
                    ->schema([
                        TextInput::make("title")->required(),
                        FileUpload::make("file")->acceptedFileTypes(["application/pdf"])->directory("real_estate/docs")->required(),
                    ])
                    ->defaultItems(0)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table \$table): Table
    {
        return \$table
            ->columns([
                TextColumn::make("title")->searchable()->sortable(),
                TextColumn::make("type")->sortable(),
                TextColumn::make("price")->money("RUB")->sortable(),
                TextColumn::make("status")->badge()->colors([
                    "primary" => "draft",
                    "success" => "active",
                    "danger" => "sold",
                    "warning" => "rented",
                ]),
                BooleanColumn::make("is_active"),
            ])
            ->filters([
                SelectFilter::make("status")->options([
                    "active" => "Active",
                    "draft" => "Draft",
                    "sold" => "Sold",
                    "rented" => "Rented",
                ]),
            ]);
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
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/RealEstate/PropertyResource.php", trim($filamentCode) . "\n");

$filPagesIndex = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListProperties extends ListRecords { protected static string \$resource = PropertyResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/RealEstate/PropertyResource/Pages/ListProperties.php", trim($filPagesIndex) . "\n");

$filPagesCreate = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Resources\Pages\CreateRecord;
class CreateProperty extends CreateRecord { protected static string \$resource = PropertyResource::class; }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/RealEstate/PropertyResource/Pages/CreateProperty.php", trim($filPagesCreate) . "\n");

$filPagesEdit = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditProperty extends EditRecord { protected static string \$resource = PropertyResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/RealEstate/PropertyResource/Pages/EditProperty.php", trim($filPagesEdit) . "\n");

echo "REAL ESTATE DONE";

