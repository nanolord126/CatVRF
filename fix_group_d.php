<?php
declare(strict_types=1);

function ensureDirectoryExists($path) {
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
    }
}

function writeCanonFile($path, $content) {
    ensureDirectoryExists(dirname($path));
    file_put_contents($path, $content);
    echo "Создан/Обновлен: {$path}\n";
}

// ==========================================
// 1. FINANCES VERTICAL
// ==========================================
writeCanonFile(__DIR__ . "/app/Domains/Finances/Models/FinanceTransaction.php", '<?php
declare(strict_types=1);

namespace App\Domains\Finances\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class FinanceTransaction extends Model
{
    use HasFactory, HasUuids;

    protected $table = "finance_transactions";

    protected $fillable = [
        "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
        "amount", "type", "status", "description"
    ];

    protected $casts = [
        "tags" => "json",
        "amount" => "integer",
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/Finances/Services/PaymentService.php", '<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Finances\Models\FinanceTransaction;

final readonly class PaymentService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function processPayment(array $data, string $correlationId): FinanceTransaction
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel("audit")->info("ОБРАБОТКА ПЛАТЕЖА ЗАПУЩЕНА", ["correlation_id" => $correlationId, "data" => $data]);
            
            // Проверка на фрод ОБЯЗАТЕЛЬНА
            $this->fraudControlService->check($data, $correlationId);

            $transaction = FinanceTransaction::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "amount" => $data["amount"] ?? 0,
                "type" => "payment",
                "status" => "processed",
                "tags" => []
            ]);

            Log::channel("audit")->info("ПЛАТЕЖ УСПЕШНО ОБРАБОТАН", ["correlation_id" => $correlationId, "id" => $transaction->id]);

            return $transaction;
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/Finances/Services/FinanceService.php", '<?php
declare(strict_types=1);

namespace App\Domains\Finances\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;

final readonly class FinanceService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function generateReport(string $correlationId): array
    {
        Log::channel("audit")->info("ГЕНЕРАЦИЯ ФИНАНСОВОГО ОТЧЕТА", ["correlation_id" => $correlationId]);
        
        $this->fraudControlService->check(["action" => "report"], $correlationId);
        
        return ["status" => "success", "report_url" => "/reports/{$correlationId}.pdf"];
    }
}
');

writeCanonFile(__DIR__ . "/app/Filament/Tenant/Resources/FinancesResource.php", '<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Finances\Models\FinanceTransaction;

class FinancesResource extends Resource
{
    protected static ?string $model = FinanceTransaction::class;
    protected static ?string $navigationIcon = "heroicon-o-banknotes";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
');


// ==========================================
// 2. EVENTS VERTICAL
// ==========================================
writeCanonFile(__DIR__ . "/app/Domains/Events/Models/Event.php", '<?php
declare(strict_types=1);

namespace App\Domains\Events\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class Event extends Model
{
    use HasFactory, HasUuids;

    protected $table = "events_b2b";

    protected $fillable = [
        "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
        "title", "start_date"
    ];

    protected $casts = [
        "tags" => "json",
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/Events/Services/EventService.php", '<?php
declare(strict_types=1);

namespace App\Domains\Events\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Events\Models\Event;

final readonly class EventService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createEvent(array $data, string $correlationId): Event
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel("audit")->info("СОЗДАНИЕ МЕРОПРИЯТИЯ", ["correlation_id" => $correlationId, "data" => $data]);
            
            $this->fraudControlService->check($data, $correlationId);

            $event = Event::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "title" => $data["title"] ?? "Новое событие",
                "tags" => []
            ]);

            Log::channel("audit")->info("МЕРОПРИЯТИЕ СОЗДАНО", ["correlation_id" => $correlationId, "id" => $event->id]);

            return $event;
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Filament/Tenant/Resources/EventsResource.php", '<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Events\Models\Event;

class EventsResource extends Resource
{
    protected static ?string $model = Event::class;
    protected static ?string $navigationIcon = "heroicon-o-calendar";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
');

// ==========================================
// 3. SPORTING GOODS VERTICAL
// ==========================================
writeCanonFile(__DIR__ . "/app/Domains/SportingGoods/Models/SportProduct.php", '<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class SportProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = "sport_products";

    protected $fillable = [
        "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
        "name", "price"
    ];

    protected $casts = [
        "tags" => "json",
        "price" => "integer",
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/SportingGoods/Services/SizeGuideService.php", '<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use InvalidArgumentException;

final readonly class SizeGuideService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function calculateSize(array $data, string $correlationId): array
    {
        Log::channel("audit")->info("РАСЧЕТ РАЗМЕРА", ["correlation_id" => $correlationId]);
        
        $this->fraudControlService->check($data, $correlationId);
        
        if (empty($data["height"])) {
            Log::channel("audit")->error("Ошибка расчета размера", ["correlation_id" => $correlationId]);
            throw new InvalidArgumentException("Missing height parameter.");
        }
        
        return ["size" => "L"];
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/SportingGoods/Services/SportingGoodsService.php", '<?php
declare(strict_types=1);

namespace App\Domains\SportingGoods\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\SportingGoods\Models\SportProduct;

final readonly class SportingGoodsService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createProduct(array $data, string $correlationId): SportProduct
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel("audit")->info("СОЗДАНИЕ СПОРТТОВАРА", ["correlation_id" => $correlationId]);
            
            $this->fraudControlService->check($data, $correlationId);

            $product = SportProduct::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "name" => $data["name"] ?? "Спорттовар",
                "price" => $data["price"] ?? 0,
                "tags" => []
            ]);

            Log::channel("audit")->info("СПОРТТОВАР СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

            return $product;
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Filament/Tenant/Resources/SportingGoodsResource.php", '<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\SportingGoods\Models\SportProduct;

class SportingGoodsResource extends Resource
{
    protected static ?string $model = SportProduct::class;
    protected static ?string $navigationIcon = "heroicon-o-shopping-bag";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
');

// ==========================================
// 4. GIFTS VERTICAL
// ==========================================
writeCanonFile(__DIR__ . "/app/Domains/Gifts/Models/GiftProduct.php", '<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

final class GiftProduct extends Model
{
    use HasFactory, HasUuids;

    protected $table = "gift_products";

    protected $fillable = [
        "uuid", "tenant_id", "business_group_id", "correlation_id", "tags",
        "name", "price"
    ];

    protected $casts = [
        "tags" => "json",
        "price" => "integer",
    ];

    protected static function booted(): void
    {
        parent::booted();
        static::addGlobalScope("tenant_id", function ($query) {
            if (function_exists("tenant") && tenant("id")) {
                $query->where("tenant_id", tenant("id"));
            }
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/Gifts/Services/GiftSelectionService.php", '<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;

final readonly class GiftSelectionService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function recommendGift(array $criteria, string $correlationId): array
    {
        Log::channel("audit")->info("ПОДБОР ПОДАРКА", ["correlation_id" => $correlationId]);
        
        $this->fraudControlService->check($criteria, $correlationId);
        
        return [
            "recommended_ids" => [1, 2, 3]
        ];
    }
}
');

writeCanonFile(__DIR__ . "/app/Domains/Gifts/Services/GiftService.php", '<?php
declare(strict_types=1);

namespace App\Domains\Gifts\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Services\Security\FraudControlService;
use App\Domains\Gifts\Models\GiftProduct;

final readonly class GiftService
{
    public function __construct(
        private FraudControlService $fraudControlService
    ) {}

    public function createGift(array $data, string $correlationId): GiftProduct
    {
        return DB::transaction(function () use ($data, $correlationId) {
            Log::channel("audit")->info("СОЗДАНИЕ ПОДАРКА", ["correlation_id" => $correlationId]);
            
            $this->fraudControlService->check($data, $correlationId);

            $product = GiftProduct::create([
                "tenant_id" => tenant("id") ?? 1,
                "correlation_id" => $correlationId,
                "name" => $data["name"] ?? "Подарок",
                "price" => $data["price"] ?? 0,
                "tags" => []
            ]);

            Log::channel("audit")->info("ПОДАРОК СОЗДАН", ["correlation_id" => $correlationId, "id" => $product->id]);

            return $product;
        });
    }
}
');

writeCanonFile(__DIR__ . "/app/Filament/Tenant/Resources/GiftsResource.php", '<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;
use App\Domains\Gifts\Models\GiftProduct;

class GiftsResource extends Resource
{
    protected static ?string $model = GiftProduct::class;
    protected static ?string $navigationIcon = "heroicon-o-gift";

    public static function form(Form $form): Form
    {
        return $form->schema([]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([]);
    }
}
');

echo "Все файлы группы D успешно обновлены по Канону 2026.\n";
