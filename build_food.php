<?php
declare(strict_types=1);

$base = __DIR__ . "/app/Domains/Food";
@mkdir($base . "/Models", 0777, true);
@mkdir($base . "/DTOs", 0777, true);
@mkdir($base . "/Services", 0777, true);
@mkdir(__DIR__ . "/app/Filament/Tenant/Resources/Food/RestaurantResource/Pages", 0777, true);
@mkdir(__DIR__ . "/app/Http/Controllers/Api/V1/Food", 0777, true);

$restaurantModel = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\BusinessGroup;

final class Restaurant extends Model
{
    protected \$table = "food_restaurants";

    protected \$fillable = [
        "tenant_id", "business_group_id", "uuid", "correlation_id",
        "name", "description", "address", "phone", "email",
        "lat", "lon", "is_active", "rating", "delivery_radius_km",
        "working_hours", "metadata"
    ];

    protected \$casts = [
        "working_hours" => "json",
        "metadata" => "json",
        "is_active" => "boolean",
        "lat" => "decimal:8",
        "lon" => "decimal:8",
        "rating" => "decimal:2",
        "delivery_radius_km" => "decimal:2",
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
        return \$this->belongsTo(Tenant::class, "tenant_id");
    }

    public function businessGroup(): BelongsTo
    {
        return \$this->belongsTo(BusinessGroup::class, "business_group_id");
    }

    public function dishes(): HasMany
    {
        return \$this->hasMany(Dish::class, "restaurant_id");
    }

    public function orders(): HasMany
    {
        return \$this->hasMany(FoodOrder::class, "restaurant_id");
    }
}
PHP;
file_put_contents($base . "/Models/Restaurant.php", trim($restaurantModel) . "\n");

$dishModel = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;

final class Dish extends Model
{
    protected \$table = "food_dishes";

    protected \$fillable = [
        "restaurant_id", "uuid", "correlation_id",
        "name", "description", "price", "weight_grams",
        "calories", "proteins", "fats", "carbohydrates",
        "is_available", "modifiers", "image_url"
    ];

    protected \$casts = [
        "modifiers" => "json",
        "is_available" => "boolean",
        "price" => "decimal:2",
        "weight_grams" => "integer",
        "calories" => "integer",
        "proteins" => "decimal:2",
        "fats" => "decimal:2",
        "carbohydrates" => "decimal:2",
    ];

    protected static function booted(): void
    {
        static::creating(function (Model \$model): void {
            if (!\$model->uuid) {
                \$model->uuid = (string) \Illuminate\Support\Str::uuid();
            }
            if (!\$model->correlation_id) {
                \$model->correlation_id = request()->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
            }
        });
    }

    public function restaurant(): BelongsTo
    {
        return \$this->belongsTo(Restaurant::class, "restaurant_id");
    }
}
PHP;
file_put_contents($base . "/Models/Dish.php", trim($dishModel) . "\n");

$orderModel = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Tenant;
use App\Models\User;

final class FoodOrder extends Model
{
    protected \$table = "food_orders";

    protected \$fillable = [
        "tenant_id", "restaurant_id", "customer_id", "uuid", "correlation_id",
        "items", "total_price", "status", "delivery_address", 
        "delivery_lat", "delivery_lon", "courier_id", "estimated_delivery_time",
        "payment_status", "special_instructions"
    ];

    protected \$casts = [
        "items" => "json",
        "total_price" => "decimal:2",
        "delivery_lat" => "decimal:8",
        "delivery_lon" => "decimal:8",
        "estimated_delivery_time" => "datetime",
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
        return \$this->belongsTo(Tenant::class, "tenant_id");
    }

    public function restaurant(): BelongsTo
    {
        return \$this->belongsTo(Restaurant::class, "restaurant_id");
    }

    public function customer(): BelongsTo
    {
        return \$this->belongsTo(User::class, "customer_id");
    }
}
PHP;
file_put_contents($base . "/Models/FoodOrder.php", trim($orderModel) . "\n");

$searchDtoCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\DTOs;

use Illuminate\Http\Request;

final readonly class SearchRestaurantDto
{
    public function __construct(
        public float \$lat,
        public float \$lon,
        public float \$radiusKm,
        public string \$correlationId,
        public ?string \$query = null
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(
            lat: (float) \$request->input("lat", 0.0),
            lon: (float) \$request->input("lon", 0.0),
            radiusKm: (float) \$request->input("radius_km", 10.0),
            correlationId: (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid()),
            query: \$request->input("query")
        );
    }
}
PHP;
file_put_contents($base . "/DTOs/SearchRestaurantDto.php", trim($searchDtoCode) . "\n");

$orderDtoCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\DTOs;

use Illuminate\Http\Request;

final readonly class CreateFoodOrderDto
{
    public function __construct(
        public int \$restaurantId,
        public int \$customerId,
        public array \$items,
        public float \$deliveryLat,
        public float \$deliveryLon,
        public string \$deliveryAddress,
        public ?string \$specialInstructions,
        public string \$correlationId
    ) {}

    public static function fromRequest(Request \$request): self
    {
        return new self(
            restaurantId: (int) \$request->input("restaurant_id", 0),
            customerId: (int) (auth()->id() ?? 0),
            items: (array) \$request->input("items", []),
            deliveryLat: (float) \$request->input("delivery_lat", 0.0),
            deliveryLon: (float) \$request->input("delivery_lon", 0.0),
            deliveryAddress: (string) \$request->input("delivery_address", ""),
            specialInstructions: \$request->input("special_instructions"),
            correlationId: (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid())
        );
    }
}
PHP;
file_put_contents($base . "/DTOs/CreateFoodOrderDto.php", trim($orderDtoCode) . "\n");

$catalogServiceCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\Dish;
use App\Domains\Food\DTOs\SearchRestaurantDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;
use Illuminate\Database\Eloquent\Collection;

final readonly class RestaurantCatalogService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private DatabaseManager \$db,
        private LogManager \$log
    ) {}

    public function searchNearby(SearchRestaurantDto \$dto): Collection
    {
        \$this->fraud->check([
            "action" => "search_restaurants",
            "lat" => \$dto->lat,
            "lon" => \$dto->lon,
            "correlation_id" => \$dto->correlationId,
        ]);

        \$query = Restaurant::query()
            ->with(["dishes" => function (\$q) {
                \$q->where("is_available", true);
            }])
            ->selectRaw(
                "*, (6371 * acos(cos(radians(?)) * cos(radians(lat)) * cos(radians(lon) - radians(?)) + sin(radians(?)) * sin(radians(lat)))) AS distance",
                [\$dto->lat, \$dto->lon, \$dto->lat]
            )
            ->having("distance", "<", \$dto->radiusKm)
            ->where("is_active", true);

        if (!empty(\$dto->query)) {
            \$query->where("name", "LIKE", "%" . \$dto->query . "%");
        }

        \$results = \$query->orderBy("distance")->limit(50)->get();

        \$this->log->channel("audit")->info("Restaurant catalog searched", [
            "results_count" => \$results->count(),
            "correlation_id" => \$dto->correlationId,
        ]);

        return \$results;
    }

    public function getRestaurantMenu(int \$restaurantId, string \$correlationId): Collection
    {
        \$restaurant = Restaurant::findOrFail(\$restaurantId);

        \$this->log->channel("audit")->info("Fetched restaurant menu", [
            "restaurant_id" => \$restaurantId,
            "correlation_id" => \$correlationId,
        ]);

        return \$restaurant->dishes()->where("is_available", true)->get();
    }
}
PHP;
file_put_contents($base . "/Services/RestaurantCatalogService.php", trim($catalogServiceCode) . "\n");

$orderServiceCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Domains\Food\Services;

use App\Domains\Food\Models\FoodOrder;
use App\Domains\Food\Models\Restaurant;
use App\Domains\Food\Models\Dish;
use App\Domains\Food\DTOs\CreateFoodOrderDto;
use App\Services\FraudControlService;
use App\Services\AuditService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Log\LogManager;

final readonly class FoodOrderingService
{
    public function __construct(
        private FraudControlService \$fraud,
        private AuditService \$audit,
        private DatabaseManager \$db,
        private LogManager \$log
    ) {}

    public function placeOrder(CreateFoodOrderDto \$dto): FoodOrder
    {
        \$this->fraud->check([
            "action" => "place_food_order",
            "customer_id" => \$dto->customerId,
            "restaurant_id" => \$dto->restaurantId,
            "correlation_id" => \$dto->correlationId,
        ]);

        return \$this->db->transaction(function () use (\$dto): FoodOrder {
            \$restaurant = Restaurant::findOrFail(\$dto->restaurantId);
            
            \$totalPrice = \$this->calculateTotal(\$dto->items);
            
            \$order = FoodOrder::create([
                "restaurant_id" => \$restaurant->id,
                "customer_id" => \$dto->customerId,
                "items" => \$dto->items,
                "total_price" => \$totalPrice,
                "status" => "pending",
                "delivery_address" => \$dto->deliveryAddress,
                "delivery_lat" => \$dto->deliveryLat,
                "delivery_lon" => \$dto->deliveryLon,
                "special_instructions" => \$dto->specialInstructions,
                "payment_status" => "unpaid",
                "correlation_id" => \$dto->correlationId,
            ]);

            // Dispatch event for Delivery Integration to assign a courier
            event(new \App\Events\FoodOrderPlacedEvent(\$order));

            \$this->audit->log(
                action: "food_order_placed",
                subjectType: FoodOrder::class,
                subjectId: \$order->id,
                old: [],
                new: \$order->toArray(),
                correlationId: \$dto->correlationId
            );

            \$this->log->channel("audit")->info("Food order successfully placed", [
                "order_id" => \$order->id,
                "total_price" => \$totalPrice,
                "correlation_id" => \$dto->correlationId,
            ]);

            return \$order;
        });
    }

    private function calculateTotal(array \$items): float
    {
        \$total = 0.0;
        foreach (\$items as \$item) {
            \$dish = Dish::findOrFail(\$item["dish_id"]);
            \$quantity = (int) (\$item["quantity"] ?? 1);
            \$total += (\$dish->price * \$quantity);
            // Ignore modifiers pricing for now, but can be added here
        }
        return \$total;
    }
}
PHP;
file_put_contents($base . "/Services/FoodOrderingService.php", trim($orderServiceCode) . "\n");

$catalogControllerCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Food\Services\RestaurantCatalogService;
use App\Domains\Food\DTOs\SearchRestaurantDto;

final class RestaurantCatalogController extends Controller
{
    public function __construct(
        private readonly RestaurantCatalogService \$catalogService,
    ) {}

    public function index(Request \$request): JsonResponse
    {
        \$dto = SearchRestaurantDto::fromRequest(\$request);
        
        try {
            \$restaurants = \$this->catalogService->searchNearby(\$dto);
            return new JsonResponse([
                "success" => true,
                "data" => \$restaurants,
                "correlation_id" => \$dto->correlationId,
            ], 200);
        } catch (\Throwable \$e) {
            return new JsonResponse([
                "success" => false,
                "message" => \$e->getMessage(),
                "correlation_id" => \$dto->correlationId,
            ], 400);
        }
    }

    public function menu(int \$restaurantId, Request \$request): JsonResponse
    {
        \$correlationId = (string) \$request->header("X-Correlation-ID", (string) \Illuminate\Support\Str::uuid());
        
        try {
            \$menu = \$this->catalogService->getRestaurantMenu(\$restaurantId, \$correlationId);
            return new JsonResponse([
                "success" => true,
                "data" => \$menu,
                "correlation_id" => \$correlationId,
            ], 200);
        } catch (\Throwable \$e) {
            return new JsonResponse([
                "success" => false,
                "message" => \$e->getMessage(),
                "correlation_id" => \$correlationId,
            ], 404);
        }
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Http/Controllers/Api/V1/Food/RestaurantCatalogController.php", trim($catalogControllerCode) . "\n");

$orderControllerCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Food;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use App\Domains\Food\Services\FoodOrderingService;
use App\Domains\Food\DTOs\CreateFoodOrderDto;

final class FoodOrderController extends Controller
{
    public function __construct(
        private readonly FoodOrderingService \$orderingService,
    ) {}

    public function store(Request \$request): JsonResponse
    {
        \$dto = CreateFoodOrderDto::fromRequest(\$request);
        
        try {
            \$order = \$this->orderingService->placeOrder(\$dto);
            return new JsonResponse([
                "success" => true,
                "data" => [
                    "order_id" => \$order->id,
                    "total_price" => \$order->total_price,
                    "status" => \$order->status,
                ],
                "correlation_id" => \$dto->correlationId,
            ], 201);
        } catch (\Throwable \$e) {
            return new JsonResponse([
                "success" => false,
                "message" => \$e->getMessage(),
                "correlation_id" => \$dto->correlationId,
            ], 400);
        }
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Http/Controllers/Api/V1/Food/FoodOrderController.php", trim($orderControllerCode) . "\n");

$filamentCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Food;

use App\Domains\Food\Models\Restaurant;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Checkbox;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\BooleanColumn;
use App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;

final class RestaurantResource extends Resource
{
    protected static ?string \$model = Restaurant::class;
    protected static ?string \$navigationIcon = "heroicon-o-building-storefront";
    protected static ?string \$navigationGroup = "Food & Catering";
    protected static ?string \$tenantOwnershipRelationshipName = "tenant";

    public static function form(Form \$form): Form
    {
        return \$form->schema([
            Section::make("Restaurant Details")->schema([
                TextInput::make("name")->required()->maxLength(255),
                Textarea::make("description")->maxLength(1000),
                TextInput::make("phone")->required()->maxLength(20),
                TextInput::make("email")->email()->maxLength(255),
                TextInput::make("address")->required()->maxLength(255),
                TextInput::make("lat")->numeric()->required(),
                TextInput::make("lon")->numeric()->required(),
                TextInput::make("delivery_radius_km")->numeric()->default(5.0)->required(),
                Checkbox::make("is_active")->default(true),
            ])->columns(2),

            Section::make("Menu & Dishes")->schema([
                Repeater::make("dishes")
                    ->relationship("dishes")
                    ->schema([
                        TextInput::make("name")->required()->maxLength(255),
                        Textarea::make("description")->maxLength(500),
                        TextInput::make("price")->numeric()->required(),
                        TextInput::make("weight_grams")->numeric(),
                        TextInput::make("calories")->numeric(),
                        Checkbox::make("is_available")->default(true),
                        FileUpload::make("image_url")->image()->directory("food/dishes"),
                        
                        Repeater::make("modifiers")
                            ->schema([
                                TextInput::make("name")->required()->placeholder("e.g. Extra Cheese"),
                                TextInput::make("price_addition")->numeric()->default(0),
                            ])
                            ->defaultItems(0)
                            ->columnSpanFull()
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
                TextColumn::make("name")->searchable()->sortable(),
                TextColumn::make("phone")->searchable(),
                TextColumn::make("rating")->sortable(),
                BooleanColumn::make("is_active"),
            ]);
    }

    public static function getPages(): array
    {
        return [
            "index" => Pages\ListRestaurants::route("/"),
            "create" => Pages\CreateRestaurant::route("/create"),
            "edit" => Pages\EditRestaurant::route("/{record}/edit"),
        ];
    }
}
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Food/RestaurantResource.php", trim($filamentCode) . "\n");

$filPagesIndex = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;
use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListRestaurants extends ListRecords { protected static string \$resource = RestaurantResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Food/RestaurantResource/Pages/ListRestaurants.php", trim($filPagesIndex) . "\n");

$filPagesCreate = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;
use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Resources\Pages\CreateRecord;
class CreateRestaurant extends CreateRecord { protected static string \$resource = RestaurantResource::class; }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Food/RestaurantResource/Pages/CreateRestaurant.php", trim($filPagesCreate) . "\n");

$filPagesEdit = <<<PHP
<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Food\RestaurantResource\Pages;
use App\Filament\Tenant\Resources\Food\RestaurantResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
class EditRestaurant extends EditRecord { protected static string \$resource = RestaurantResource::class; protected function getHeaderActions(): array { return [Actions\DeleteAction::make()]; } }
PHP;
file_put_contents(__DIR__ . "/app/Filament/Tenant/Resources/Food/RestaurantResource/Pages/EditRestaurant.php", trim($filPagesEdit) . "\n");

$eventCode = <<<PHP
<?php
declare(strict_types=1);

namespace App\Events;

use App\Domains\Food\Models\FoodOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class FoodOrderPlacedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly FoodOrder \$order
    ) {}
}
PHP;
file_put_contents(__DIR__ . "/app/Events/FoodOrderPlacedEvent.php", trim($eventCode) . "\n");

echo "FOOD DONE";

