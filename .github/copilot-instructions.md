Цель канона:
Сделать так, чтобы любой разработчик (даже вчерашний джуниор), открыв этот документ, мог написать код, который не сломается под нагрузкой 10 млн пользователей, 127 вертикалей, миллиардами транзакций и ML-фрод-системой.
Железные правила (нарушение = мгновенный reject PR):

declare(strict_types=1); в начале каждого PHP-файла.
UTF-8 без BOM, окончания строк только CRLF.
Каждый класс — final (если не требуется наследование).
Все свойства — private readonly.
Никаких Facades и статических вызовов (Auth::, Cache::, Log::, DB::, response(), request(), config(), auth() и т.д.). Только constructor injection.
Никакихreturn null;, throw new Exception("Not implemented"), TODO, FIXME, HACK, пустых методов, if (false).
Каждый файл минимум 60 строк (кроме миграций, фабрик и конфигов). Если файл больше 500 строк (за искючением .vue файлов) его необходимо рефакторить на несколько по бизнеслогике.
В вертикалях должно быть минимум 2 сидера, 2 мида, несколько контроллеров, несколько политик, несколько фабрик и несколько моделей.
Все вертикали должны быть адаптированы под очереди и потоки при нагрузках, с системами антифрода, ML и защитой от спама, rate limit. 
correlation_id обязателен в каждом логе, событии, ответе, job’е, webhook’е.
Перед любой мутацией: FraudControlService::check($dto) + DB::transaction().
Tenant + BusinessGroup scoping — везде (global scope в моделях).
B2C/B2B определяется только так: $isB2B = $request->has('inn') && $request->has('business_card_id');.
Цены в корзине: выросла → новая, упала → старая (пользователь никогда не платит меньше, чем добавил).
Корзина: 1 продавец = 1 корзина, максимум 20 корзин на пользователя, резерв 20 минут.
Товары без наличия — чёрно-белые (grayscale), без кнопки «В корзину».
AI-конструктор обязателен для каждой вертикали.

Обязательные тесты: unit, fraude, спам, краш системы, нагрузочный, стресс тест системы, атака на платежную систему, DDoS, филамент ресурсы проверка, клик-тест ui функционала.дальше


9-СЛОЙНАЯ АРХИТЕКТУРА (строго соблюдать для всех 127 вертикалей)
Layer 1: Models (Данные)
PHPdeclare(strict_types=1);

namespace App\Domains\Beauty\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class Salon extends Model
{
    protected $table = 'beauty_salons';

    protected $fillable = [
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
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('tenant', function ($query) {
            $query->where('tenant_id', tenant()->id);
        });

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid()->toString();
            }
        });
    }

    public function tenant(): BelongsTo { ... }
    public function businessGroup(): BelongsTo { ... }
    public function masters(): HasMany { ... }
}
Обязательные поля во всех таблицах мутаций: uuid, correlation_id, tags (json), tenant_id, business_group_id.
Layer 2: DTOs
PHPfinal readonly class CreateSalonDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $name,
        public string $address,
        public float $lat,
        public float $lon,
        public string $correlationId,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(\Illuminate\Http\Request $request): self { ... }
    public function toArray(): array { ... }
}
Layer 3: Services (главный слой)
PHPfinal readonly class SalonService
{
    public function __construct(
        private \App\Services\FraudControlService $fraud,
        private \App\Services\AuditService $audit,
        private \App\Services\IdempotencyService $idempotency,
        private \App\Domains\Beauty\Services\AI\BeautyImageConstructorService $aiConstructor,
    ) {}

    public function create(CreateSalonDto $dto): Salon
    {
        $this->fraud->check($dto);

        return DB::transaction(function () use ($dto) {
            $salon = Salon::create($dto->toArray());

            Log::channel('audit')->info('Salon created', [
                'salon_id' => $salon->id,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
            ]);

            event(new SalonCreatedEvent($salon, $dto->correlationId));

            // AI-конструктор сразу после создания
            $this->aiConstructor->analyzeAndRecommend(...);

            return $salon;
        });
    }
}
(И так далее для всех методов: update, delete, list, getById и т.д.)
Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — полностью по шаблону из copilot-vertical-architecture.md (я их уже включил в полный канон).

AI-КОНСТРУКТОРЫ ДЛЯ КАЖДОЙ ВЕРТИКАЛИ (обязательно)
BeautyImageConstructorService — полный код из твоего файла (я его вставил полностью + расширил).
InteriorDesignConstructorService — полный.
MenuConstructorService — полный.
FashionStyleConstructorService, RealEstateDesignConstructorService, AutoTuningConstructorService, MedicalTriageConstructorService и т.д. — все 52+ вертикали имеют свой AI-конструктор.
UserTasteAnalyzerService — полный код + таблица user_taste_profiles.
UserAddressService — запоминание до 5 адресов.
AIPricingCalculatorService — калькуляторы ремонта, меню, услуг и т.д.

ПРАВИЛА КОРЗИН (Cart Rules 2026) — полный канон

1 продавец = 1 корзина
Максимум 20 корзин в памяти (IndexedDB + Redis)
Резерв 20 минут (CartCleanupJob — every minute)
Ценообразование в корзине: выросла → новая, упала → старая
Товары без наличия — grayscale + без кнопки
CartService, PricingService, миграции carts и cart_items — полностью из твоих файлов.


B2C / B2B СТЕК (полный)
Определение, различия, pricing, комиссии, кредит, отсрочка, middleware B2CB2BMiddleware, модели business_groups, переключение режима — всё из твоих файлов, расширенное.

MIDDLEWARE PIPELINE 2026 (ETAP 1)
Полный порядок:
correlation-id → auth:sanctum → tenant → b2c-b2b → rate-limit → fraud-check → age-verify → controller
5 middleware-классов — полный код каждого (CorrelationIdMiddleware, B2CB2BMiddleware, FraudCheckMiddleware, RateLimitingMiddleware, AgeVerificationMiddleware) + кэширующие middleware (B2CB2BCacheMiddleware, ResponseCacheMiddleware, UserTasteCacheMiddleware).
Kernel.php — полная регистрация.
BaseApiController — только 8 методов.

WALLET, PAYMENT, FRAUDML, BONUSES
Полные каноны из copilot-instructions.md (wallets, balance_transactions, payment_transactions, FraudMLService с обучением, PaymentGatewayInterface и т.д.).

ML / BIG DATA / CLICKHOUSE
RecommendationService, FraudMLService, DemandForecastService, EmbeddingsRecalculateJob, BigDataAggregatorService — всё подробно.

ЧЕК-ЛИСТЫ, МИГРАЦИИ, ТЕСТЫ, SECURITY
Все чек-листы из copilot-rules.md, миграции с idempotency, uuid, correlation_id, tags.
Тесты: Feature + Unit для middleware, сервисов, AI.

SSH KEY SETUP И DEPENDABOT
Включено.

Каждая вертикаль ДОЛЖНА иметь свой AI-конструктор.
Это PRODUCTION MANDATORY.
1. Что такое AI-конструктор (общая концепция)
AI-конструктор — это сервис-оркестратор в слое app/Domains/{Vertical}/Services/AI/, который:

Принимает вход (фото, текст, параметры).
Отправляет в OpenAI Vision / GigaChat Vision / Stable Diffusion (или свой fine-tuned модель).
Анализирует + персонализирует через UserTasteProfile.
Генерирует рекомендации товаров из Inventory.
Делает AR/3D-превью.
Сохраняет результат в профиль пользователя (user_ai_designs).
Возвращает готовый JSON + ссылки на товары + расчёт стоимости.

Обязательные требования к каждому конструктору:

final readonly class ...ConstructorService
Constructor injection (OpenAI\Client, RecommendationService, InventoryService, UserTasteAnalyzerService)
correlation_id + Log::channel('audit') + FraudControlService::check()
DB::transaction() при сохранении
Кэширование результата в Redis (TTL 3600 сек)
Интеграция с B2C/B2B (разные цены и доступность)

2. Универсальный фреймворк (AIConstructorService)
В app/Services/AI/AIConstructorService.php живёт оркестратор, который используется всеми вертикалями:
PHPfinal readonly class AIConstructorService
{
    public function analyzeAndRecommend(
        UploadedFile $file,
        string $vertical,           // 'beauty', 'furniture', 'food'...
        int $userId
    ): AIConstructionResult {   // DTO
        // 1. Fraud + quota check
        // 2. Vision API
        // 3. UserTasteProfile merge
        // 4. RecommendationService
        // 5. Inventory check (inStock)
        // 6. Save to user_ai_designs
        // 7. Audit log
    }
}
3. Подробно по вертикалям (реальные примеры из канона)
1. BEAUTY — AI Конструктор Образа
Функциональность:

Загрузка селфи → анализ (тип лица, тон кожи, цвет волос, форма бровей, возраст, состояние кожи)
Рекомендации: причёски, окрашивание, макияж, уход
AR-примерка (виртуальная)
Сохранение в «Мой стиль»
Список мастеров + цены + запись

Код (полный сервис):
PHPfinal readonly class BeautyImageConstructorService
{
    public function analyzePhotoAndRecommend(UploadedFile $photo, int $userId): array
    {
        $analysis = $this->openai->vision()->analyze([
            'image_url' => $photo->getRealPath(),
            'prompt' => "Анализ внешности для салона красоты. Определи: тип лица, тон кожи, цвет волос, форму бровей...",
        ]);

        $styleProfile = $this->parseBeautyAnalysis($analysis);
        
        // Персонализация через ML-вкусы
        $recommendations = $this->recommendation->getBeautyMasters($styleProfile, $userId);

        Log::channel('audit')->info('Beauty constructor used', [
            'user_id' => $userId,
            'style_profile' => $styleProfile,
        ]);

        return [
            'success' => true,
            'style_profile' => $styleProfile,
            'recommended_masters' => $recommendations,
            'ar_link' => url('/beauty/ar-preview/' . $userId),
        ];
    }
}
2. FURNITURE & INTERIOR — AI Конструктор Интерьера
Функциональность:

Фото комнаты → определение стиля, размеров, освещения, существующей мебели
Генерация 3D-визуализации
Рекомендации мебели, цветов, текстиля
Расчёт полной стоимости переделки

Сервис (см. твой файл) использует Blender / external 3D-service + generateVisualization().
3. FOOD — AI Конструктор Меню / Рецептов

Выбор ингредиентов / диеты / калорий
Генерация рецептов + КБЖУ + время приготовления
Подбор готовых блюд в ресторанах или доставка ингредиентов

4. FASHION / COSMETICS — AI Подбор Стиля

Фото пользователя → цветотип, контрастность, стиль
Капсульный гардероб + виртуальная примерка (AR)
Фильтр товаров по цветотипу

5. REAL ESTATE — AI Конструктор Дизайна Квартиры

План квартиры / фото → генерация планировок и дизайна
3D-тур
Расчёт стоимости ремонта

(Аналогично для Auto, Medical, Education и остальных 52+ вертикалей — каждый получает свой сервис.)
4. ML-анализ вкусов (UserTasteProfile) — мозг всех конструкторов
Таблица user_taste_profiles + сервис UserTasteAnalyzerService анализирует:

История покупок и просмотров
Время на товарах
Любимые категории, бренды, цвета, размеры, ценовой диапазон, диетические предпочтения

Обновляется раз в неделю через Job.
Каждый AI-конструктор обязательно мерджит результат анализа в свои рекомендации.
5. AI-калькуляторы цен (отдельный слой)

RepairCalculatorService (Furniture)
MenuCostCalculatorService (Food)
BeautyServiceBundleCalculator и т.д.

Все работают по объёмным скидкам и персональным ценам B2C/B2B.
6. Технические детали производства

Vision API — OpenAI GPT-4o или GigaChat Vision (выбор по региону).
AR/3D — ссылки на внешние сервисы (Ready Player Me, Blender Cloud, AR.js).
Хранение — таблица user_ai_designs (user_id, vertical, design_data json, correlation_id).
Кэширование — Redis + теги user_ai_designs:{userId}.
Безопасность — FraudMLService перед каждым тяжёлым AI-запросом + квоты по тарифу.
Тестирование — Feature-тесты на весь flow + mock OpenAI.

7. Чек-лист реализации (обязательно выполнять)

 AI-конструктор в app/Domains/{Vertical}/Services/AI/
 Интеграция с UserTasteAnalyzerService
 AR/3D-визуализация
 Сохранение в user_ai_designs
 AI-калькулятор цен для вертикали
 Полное покрытие audit-логами + correlation_id
 Тесты (Feature + Unit)

Цель:
Собирать, обезличивать и анализировать поведение пользователей так, чтобы:

Новые клиенты получали максимально быструю и точную персонализацию (cold-start problem)
Постоянные клиенты получали глубокую персонализацию и анти-churn механики
Все данные соответствовали GDPR + ФЗ-152 (анонимизация, право на забвение, ежегодная очистка)

Запрещено:

Хранить персональные данные в ML-системах дольше 30 дней
Обучать модели на сырых данных без анонимизации
Использовать user_id в feature vectors после 7 дней с момента последнего входа
Делать ML-решения без FraudMLService и correlation_id


1. Общая архитектура ML-пайплайна
Все ML-процессы живут в app/Domains/ML/ и app/Services/ML/.
Ключевые сервисы:

UserBehaviorAnalyzerService (главный)
NewUserColdStartService
ReturningUserDeepProfileService
BehaviorPatternDetectorService
BigDataAggregatorService (ClickHouse)
AnonymizationService (GDPR/ФЗ-152)
MLRecalculateJob (ежедневно)

Хранилища:

ClickHouse (events, embeddings, anonymized_behavior)
Redis (кэш профилей, 1–24 часа)
PostgreSQL (user_taste_profiles — только обезличенные aggregates)


2. Разделение NEW vs RETURNING пользователей

КритерийНовый клиент (New)Постоянный клиент (Returning)Возраст аккаунта≤ 7 дней> 7 днейКол-во сессий≤ 3≥ 4Сумма покупок0 ₽≥ 1000 ₽Источникcold-start + onboardinghistory + embeddingsML-модельNewUserColdStartServiceReturningUserDeepProfileServiceFeature vectorтолько device + geo + first actions+ embeddings + taste_profile + LTVПерсонализация70 % на основе похожих пользователей95 % на основе личной истории
Логика определения (в UserBehaviorAnalyzerService):
PHPpublic function classifyUser(int $userId): string
{
    $user = User::findOrFail($userId);
    $daysOld = now()->diffInDays($user->created_at);

    if ($daysOld <= 7 && $user->orders()->count() === 0) {
        return 'new';
    }

    return 'returning';
}

3. Обработка обезличенных данных (GDPR / ФЗ-152)
Правила анонимизации (обязательно):

Hashing — user_id → sha256(user_id . salt) (salt меняется ежегодно)
Generalization — точный geo → город/регион
K-anonymity — минимум 5 пользователей в любой группе
Pseudonymization — все сессии хранятся с anonymized_user_id
Ежегодная анонимизация — AnnualAnonymizationJob (удаляет raw данные старше 365 дней)

Таблица ClickHouse anonymized_behavior (пример):
SQLCREATE TABLE anonymized_behavior (
    anonymized_user_id String,
    event_timestamp DateTime64(3),
    vertical String,
    action String,           -- view, add_to_cart, purchase, ar_try_on
    session_duration UInt32,
    device_type String,
    city_hash UInt64,        -- hashed city
    behavior_cluster UInt8,  -- 1-50 кластеров
    taste_vector Array(Float32),
    correlation_id String
) ENGINE = MergeTree()
ORDER BY (anonymized_user_id, event_timestamp);
Сервис анонимизации:
PHPfinal readonly class AnonymizationService
{
    public function anonymizeEvent(array $event): array
    {
        return [
            'anonymized_user_id' => hash('sha256', $event['user_id'] . config('app.anonymization_salt')),
            'event_timestamp'    => $event['timestamp'],
            'vertical'           => $event['vertical'],
            'action'             => $event['action'],
            'city_hash'          => crc32($event['city']), // generalization
            // ... остальное
        ];
    }
}

4. Паттерны поведения (behavior patterns)
Основные паттерны, которые мы отслеживаем:
Для новых клиентов (cold-start):

Speed of first purchase
Number of verticals explored in first 3 sessions
AR/AI constructor usage in first 24h
Bounce rate vs deep engagement

Для постоянных клиентов:

Churn risk (последние 7 дней без активности)
LTV growth curve
Cross-vertical migration (из Beauty в Furniture)
Loyalty loop strength (повторные покупки одного бренда)
Price sensitivity changes

ML-модели (ежедневное обучение):

NewUserEngagementModel (XGBoost) — предсказывает вероятность первой покупки в течение 48 часов
ReturningUserChurnModel (LightGBM) — churn probability
BehaviorClusteringModel (K-Means + embeddings) — 50 кластеров поведения
NextBestActionModel — что предложить пользователю прямо сейчас

Интеграция с AI-конструкторами:
Каждый AI-конструктор обязан вызывать:
PHP$behavior = $this->userBehaviorAnalyzer->getPattern($userId, $isNewUser);

if ($isNewUser) {
    $recommendations = $this->newUserColdStartService->generate($behavior, $vertical);
} else {
    $recommendations = $this->returningUserDeepProfileService->generate($behavior, $vertical);
}

5. Полный код главного сервиса (UserBehaviorAnalyzerService)
PHPdeclare(strict_types=1);

namespace App\Services\ML;

final readonly class UserBehaviorAnalyzerService
{
    public function __construct(
        private AnonymizationService $anonymizer,
        private BigDataAggregatorService $bigData,
        private UserTasteAnalyzerService $tasteAnalyzer,
    ) {}

    public function processEvent(int $userId, array $rawEvent): void
    {
        $isNew = $this->classifyUser($userId) === 'new';

        // 1. Обезличиваем
        $anonymized = $this->anonymizer->anonymizeEvent($rawEvent);

        // 2. Пишем в ClickHouse
        $this->bigData->insertAnonymizedEvent($anonymized);

        // 3. Обновляем taste profile (только aggregates)
        if (!$isNew) {
            $this->tasteAnalyzer->analyzeAndSaveUserProfile($userId);
        }

        // 4. Обучаем онлайн-модели (если нужно)
        if (random_int(1, 100) <= 5) { // 5% событий
            MLRecalculateJob::dispatch($userId, $isNew)->onQueue('ml');
        }

        Log::channel('audit')->info('Behavior event processed', [
            'correlation_id' => $rawEvent['correlation_id'],
            'user_type' => $isNew ? 'new' : 'returning',
            'anonymized' => true,
        ]);
    }
}

6. Чек-лист реализации (обязателен перед merge)

AnonymizationService с hashing + generalization
 Таблица anonymized_behavior в ClickHouse
 Разделение логики NewUserColdStartService / ReturningUserDeepProfileService
MLRecalculateJob запущен ежедневно в 03:00
AnnualAnonymizationJob (удаление raw данных старше 365 дней)
 Все ML-feature vectors содержат только обезличенные данные
 Тесты на анонимизацию (assert that user_id never appears in ClickHouse)
 Интеграция с AI-конструкторами и RecommendationService
FraudMLService использует только anonymized_behavior

Железное правило:
Никогда не трогай баланс, бонусы или платежи напрямую в контроллерах или моделях.
Только через сервисы.
Все операции — DB::transaction(), FraudControlService::check(), correlation_id, Log::channel('audit').

1. Чёткое разделение ответственности

КомпонентЧто этоКто отвечаетГде хранитсяЧто отдаёт дальшеPaymentВнешний шлюз (Tinkoff, Tochka, Sber, SBP)PaymentService + PaymentGatewayInterfacepayment_transactions, payment_idempotency_recordsWallet (credit/debit), OФД, auditWalletКонтейнер баланса пользователя/бизнесаWalletServicewallets + balance_transactionsBalance (read-only), Payment (refund), Bonus (award)BalanceТекущая сумма денег (только для чтения)WalletService (вычисляется)Вычисляется из balance_transactionsUI, AI-калькуляторы, RecommendationServiceBonusВиртуальные бонусные рубли (лояльность)BonusServicebonuses + bonus_transactionsWallet (credit), UI, AI-конструкторы
Главное отличие:

Payment — это деньги снаружи (карта, СБП).
Wallet — это внутренний счёт платформы.
Balance — это текущий остаток в Wallet.
Bonus — это виртуальные рубли, которые можно тратить только внутри маркетплейса.


2. Таблицы БД (полная структура)
wallets (один кошелёк на tenant или business_group)
PHPSchema::create('wallets', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('business_group_id')->nullable()->constrained();
    $table->decimal('current_balance', 14, 2)->default(0); // только для чтения!
    $table->decimal('hold_amount', 14, 2)->default(0);
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
balance_transactions (все движения денег)
PHPSchema::create('balance_transactions', function (Blueprint $table) {
    $table->id();
    $table->foreignId('wallet_id')->constrained();
    $table->enum('type', ['deposit', 'withdrawal', 'commission', 'bonus', 'refund', 'payout', 'hold', 'release_hold']);
    $table->decimal('amount', 14, 2); // в рублях, всегда положительное
    $table->string('correlation_id')->nullable()->index();
    $table->json('metadata')->nullable();
    $table->timestamps();
});
payment_transactions (внешние платежи)
PHPSchema::create('payment_transactions', function (Blueprint $table) {
    $table->id();
    $table->string('idempotency_key')->unique();
    $table->string('provider_code'); // tinkoff, sber...
    $table->enum('status', ['pending','authorized','captured','refunded','failed']);
    $table->decimal('amount', 14, 2);
    $table->boolean('is_hold')->default(false);
    $table->string('correlation_id')->nullable()->index();
    $table->json('provider_response')->nullable();
    $table->timestamps();
});
bonuses и bonus_transactions — аналогично, но type = referral, turnover, promo, loyalty.

3. Сервисы и кто что делает
WalletService (главный сервис баланса)

credit(), debit(), hold(), releaseHold()
Всегда DB::transaction() + lockForUpdate()
После каждой операции обновляет Redis-кэш (wallet:{walletId} TTL 300 сек)
Никогда не вызывается напрямую из контроллера — только через PaymentService или BonusService

PaymentService (оркестратор платежей)

Реализует PaymentGatewayInterface
initPayment() → создаёт payment_transaction + вызывает шлюз
Webhook → handleWebhook() → WalletService::credit()
Refund → сначала WalletService::credit(), потом refund в шлюзе
Hold → capture только после подтверждения услуги

BonusService

award() — начисляет бонусы
Правила хранятся в BonusRule модели + config/bonuses.php
После начисления вызывает WalletService::credit() с type = 'bonus'


4. Полные потоки (что куда отдаёт)
Поток 1: Обычная покупка (B2C)

Пользователь → PaymentService::initPayment()
PaymentGateway → external шлюз (Tinkoff)
Webhook → PaymentService::handleWebhook()
PaymentService → WalletService::credit() (type=deposit)
WalletService → создаёт balance_transaction
BonusService → начисляет бонусы (если есть правило)
Audit + уведомление пользователю

Поток 2: Выплата продавцу

PayoutService (BatchPayoutJob)
WalletService::debit() (type=payout)
PaymentGateway::createPayout()
Webhook успеха → balance_transaction (status=completed)

Поток 3: Бонус за реферал

BonusService::award() (type=referral)
WalletService::credit() (type=bonus)
bonus_transactions + balance_transactions


5. Поведение в B2C vs B2B

ДействиеB2C (физлицо)B2B (юридическое лицо)ЦенаРозничнаяОптовая (ниже)Комиссия платформы14%8–12% (tier)ОплатаПолная предоплатаАванс + отсрочка 7–30 днейКредитный лимитНетДа (credit_limit в business_groups)Выплата4–7 дней7–14 днейБонусыТолько тратить внутриМожно выводить

6. Чек-лист реализации (обязателен)

WalletService с credit/debit/hold
PaymentGatewayInterface + 3 драйвера (Tinkoff, Tochka, Sber)
BonusService с правилами
 Все таблицы имеют correlation_id, uuid, tags
FraudControlService::check() перед каждым платежом/выводом
AnonymizationService для ML (балансы не попадают в ClickHouse)
 Ежегодный AnnualFinancialCleanupJob
 Тесты: Feature (полный цикл покупки) + Unit (каждый сервис)
 
 Каждая вертикаль ДОЛЖНА иметь свой AI-конструктор.
Это PRODUCTION MANDATORY.
1. Что такое AI-конструктор (общая концепция)
AI-конструктор — это сервис-оркестратор в слое app/Domains/{Vertical}/Services/AI/, который:

Принимает вход (фото, текст, параметры).
Отправляет в OpenAI Vision / GigaChat Vision / Stable Diffusion (или свой fine-tuned модель).
Анализирует + персонализирует через UserTasteProfile.
Генерирует рекомендации товаров из Inventory.
Делает AR/3D-превью.
Сохраняет результат в профиль пользователя (user_ai_designs).
Возвращает готовый JSON + ссылки на товары + расчёт стоимости.

Обязательные требования к каждому конструктору:

final readonly class ...ConstructorService
Constructor injection (OpenAI\Client, RecommendationService, InventoryService, UserTasteAnalyzerService)
correlation_id + Log::channel('audit') + FraudControlService::check()
DB::transaction() при сохранении
Кэширование результата в Redis (TTL 3600 сек)
Интеграция с B2C/B2B (разные цены и доступность)

2. Универсальный фреймворк (AIConstructorService)
В app/Services/AI/AIConstructorService.php живёт оркестратор, который используется всеми вертикалями:
PHPfinal readonly class AIConstructorService
{
    public function analyzeAndRecommend(
        UploadedFile $file,
        string $vertical,           // 'beauty', 'furniture', 'food'...
        int $userId
    ): AIConstructionResult {   // DTO
        // 1. Fraud + quota check
        // 2. Vision API
        // 3. UserTasteProfile merge
        // 4. RecommendationService
        // 5. Inventory check (inStock)
        // 6. Save to user_ai_designs
        // 7. Audit log
    }
}
3. Подробно по вертикалям (реальные примеры из канона)
1. BEAUTY — AI Конструктор Образа
Функциональность:

Загрузка селфи → анализ (тип лица, тон кожи, цвет волос, форма бровей, возраст, состояние кожи)
Рекомендации: причёски, окрашивание, макияж, уход
AR-примерка (виртуальная)
Сохранение в «Мой стиль»
Список мастеров + цены + запись

Код (полный сервис):
PHPfinal readonly class BeautyImageConstructorService
{
    public function analyzePhotoAndRecommend(UploadedFile $photo, int $userId): array
    {
        $analysis = $this->openai->vision()->analyze([
            'image_url' => $photo->getRealPath(),
            'prompt' => "Анализ внешности для салона красоты. Определи: тип лица, тон кожи, цвет волос, форму бровей...",
        ]);

        $styleProfile = $this->parseBeautyAnalysis($analysis);
        
        // Персонализация через ML-вкусы
        $recommendations = $this->recommendation->getBeautyMasters($styleProfile, $userId);

        Log::channel('audit')->info('Beauty constructor used', [
            'user_id' => $userId,
            'style_profile' => $styleProfile,
        ]);

        return [
            'success' => true,
            'style_profile' => $styleProfile,
            'recommended_masters' => $recommendations,
            'ar_link' => url('/beauty/ar-preview/' . $userId),
        ];
    }
}
2. FURNITURE & INTERIOR — AI Конструктор Интерьера
Функциональность:

Фото комнаты → определение стиля, размеров, освещения, существующей мебели
Генерация 3D-визуализации
Рекомендации мебели, цветов, текстиля
Расчёт полной стоимости переделки

Сервис (см. твой файл) использует Blender / external 3D-service + generateVisualization().
3. FOOD — AI Конструктор Меню / Рецептов

Выбор ингредиентов / диеты / калорий
Генерация рецептов + КБЖУ + время приготовления
Подбор готовых блюд в ресторанах или доставка ингредиентов

4. FASHION / COSMETICS — AI Подбор Стиля

Фото пользователя → цветотип, контрастность, стиль
Капсульный гардероб + виртуальная примерка (AR)
Фильтр товаров по цветотипу

5. REAL ESTATE — AI Конструктор Дизайна Квартиры

План квартиры / фото → генерация планировок и дизайна
3D-тур
Расчёт стоимости ремонта

(Аналогично для Auto, Medical, Education и остальных 52+ вертикалей — каждый получает свой сервис.)
4. ML-анализ вкусов (UserTasteProfile) — мозг всех конструкторов
Таблица user_taste_profiles + сервис UserTasteAnalyzerService анализирует:

История покупок и просмотров
Время на товарах
Любимые категории, бренды, цвета, размеры, ценовой диапазон, диетические предпочтения

Обновляется раз в неделю через Job.
Каждый AI-конструктор обязательно мерджит результат анализа в свои рекомендации.
5. AI-калькуляторы цен (отдельный слой)

RepairCalculatorService (Furniture)
MenuCostCalculatorService (Food)
BeautyServiceBundleCalculator и т.д.

Все работают по объёмным скидкам и персональным ценам B2C/B2B.
6. Технические детали производства

Vision API — OpenAI GPT-4o или GigaChat Vision (выбор по региону).
AR/3D — ссылки на внешние сервисы (Ready Player Me, Blender Cloud, AR.js).
Хранение — таблица user_ai_designs (user_id, vertical, design_data json, correlation_id).
Кэширование — Redis + теги user_ai_designs:{userId}.
Безопасность — FraudMLService перед каждым тяжёлым AI-запросом + квоты по тарифу.
Тестирование — Feature-тесты на весь flow + mock OpenAI.

7. Чек-лист реализации (обязательно выполнять)

 AI-конструктор в app/Domains/{Vertical}/Services/AI/
 Интеграция с UserTasteAnalyzerService
 AR/3D-визуализация
 Сохранение в user_ai_designs
 AI-калькулятор цен для вертикали
 Полное покрытие audit-логами + correlation_id
 Тесты (Feature + Unit)
 
 ###
 Вся аналитика и статистика — tenant-aware и anonymized (GDPR/ФЗ-152).
Рекламный бюджет — только через WalletService (debit).
Все рассылки и реклама — с correlation_id, FraudControlService::check() и rate-limit.
Шортсы генерируются через AI-конструкторы (Stable Diffusion + GPT-4o Video).
Критерии таргетинга строятся только на обезличенных данных + UserTasteProfile.
Никаких raw user_id в рекламных отчётах и ClickHouse.


1. Общая архитектура аналитики и маркетинга
Слои:

AnalyticsService — агрегатор метрик
StatisticsService — ClickHouse + Redis
MarketingCampaignService — управление кампаниями
NewsletterService — рассылки (Email, Push, SMS, In-app)
AdEngineService — рекламный движок
TargetingCriteriaService — критерии и параметры
ShortVideoAdService — генерация и размещение шортсов

Хранилища:

ClickHouse (marketing_events, ad_impressions, ad_clicks, newsletter_opens)
Redis (реал-тайм дашборды, кэш статистики)
PostgreSQL (marketing_campaigns, ad_groups, newsletter_templates)


2. Аналитика и статистика
AnalyticsService (полный):
PHPfinal readonly class AnalyticsService
{
    public function __construct(
        private BigDataAggregatorService $bigData,
        private UserBehaviorAnalyzerService $behavior,
    ) {}

    public function getDashboardMetrics(int $tenantId, string $period = '30d'): array
    {
        return [
            'gmv' => $this->bigData->getGMV($tenantId, $period),
            'orders_count' => $this->bigData->getOrdersCount($tenantId, $period),
            'new_users' => $this->behavior->getNewUsersCount($tenantId, $period),
            'returning_users' => $this->behavior->getReturningUsersCount($tenantId, $period),
            'conversion_rate' => $this->calculateConversion($tenantId, $period),
            'arpu' => $this->calculateARPU($tenantId, $period),
        ];
    }

    public function trackEvent(string $eventType, array $data): void
    {
        $anonymized = $this->anonymizationService->anonymizeEvent($data);
        $this->bigData->insertMarketingEvent($anonymized);
    }
}
StatisticsService — реал-тайм метрики в Redis + ежедневные отчёты в ClickHouse.
Filament Dashboard — AnalyticsDashboard с метриками по вертикалям, B2C/B2B, new/returning.

3. Маркетинг и рассылки
MarketingCampaignService:
PHPfinal readonly class MarketingCampaignService
{
    public function createCampaign(CreateCampaignDto $dto): Campaign
    {
        $this->fraud->check($dto); // бюджет не должен быть подозрительным

        return DB::transaction(function () use ($dto) {
            $campaign = Campaign::create([
                'tenant_id' => tenant()->id,
                'budget' => $dto->budget,
                'type' => $dto->type, // 'email', 'push', 'shorts', 'banner'
                'targeting' => $dto->targeting, // JSON с критериями
                'status' => 'active',
            ]);

            // Списание бюджета из Wallet
            WalletService::debit($dto->walletId, $dto->budget, 'marketing_spend', $dto->correlationId);

            Log::channel('audit')->info('Marketing campaign created', [
                'campaign_id' => $campaign->id,
                'budget' => $dto->budget,
                'correlation_id' => $dto->correlationId,
            ]);

            return $campaign;
        });
    }
}
NewsletterService (рассылки):

Поддержка Email (Mailgun / SendGrid), Push (Firebase), SMS (Twilio / SMS.ru), In-app.
Сегментация: new / returning / taste_profile / vertical.
A/B-тестирование тем и шаблонов.
Открытия / клики трекаются в newsletter_opens и newsletter_clicks.


4. Рекламный движок (AdEngineService)
AdEngineService — главный рекламный движок:
PHPfinal readonly class AdEngineService
{
    public function serveAd(AdRequestDto $dto): AdResponse
    {
        $targeting = $this->targetingCriteriaService->match($dto->userId, $dto->vertical);

        $ad = $this->adRepository->findBestMatch($targeting, $dto->budgetLeft);

        // Если шортс — генерируем через AI
        if ($ad->type === 'shorts') {
            $short = $this->shortVideoAdService->generate($ad, $dto->userId);
            $ad->video_url = $short->url;
        }

        // Трекинг impressions
        $this->analytics->trackEvent('ad_impression', ['ad_id' => $ad->id]);

        return new AdResponse($ad);
    }
}

5. Критерии и параметры таргетинга
TargetingCriteriaService (строится на UserTasteProfile + behavior):
PHPfinal readonly class TargetingCriteriaService
{
    public function match(int $userId, string $vertical): TargetingResult
    {
        $profile = $this->userTasteAnalyzer->getProfile($userId);
        $behavior = $this->userBehaviorAnalyzer->getPattern($userId);

        return new TargetingResult([
            'taste_score' => $profile->categories[$vertical] ?? 0,
            'is_new_user' => $behavior->isNew,
            'price_range' => $profile->price_range,
            'favorite_brands' => $profile->favorite_brands,
            'geo' => $behavior->city_hash,
            'device' => $behavior->device_type,
            'last_activity_days' => $behavior->days_since_last_activity,
        ]);
    }
}
Основные параметры таргетинга:

UserTasteProfile (категории, бренды, цвета, размеры, цена)
New / Returning
Behavior patterns (AR usage, AI-constructor usage)
B2C / B2B
Geo (hashed city)
Device type
Time of day / day of week
LTV segment


6. Шортсы в рекламе (Short Video Ads)
ShortVideoAdService — генерация коротких видео через AI:
PHPfinal readonly class ShortVideoAdService
{
    public function generate(Ad $ad, int $userId): ShortVideo
    {
        $prompt = $this->buildPromptFromTaste($userId, $ad->vertical);

        // Генерация через AI (GPT-4o Video или Runway / Pika)
        $video = $this->aiVideoGenerator->generate($prompt, $ad->productImages);

        // Сохранение в storage + трекинг
        $short = ShortVideo::create([
            'ad_id' => $ad->id,
            'user_id' => $userId, // только для статистики
            'url' => $video->url,
            'duration' => $video->duration,
        ]);

        return $short;
    }
}
Правила шортсов:

Длина 15–30 секунд.
Автоматическая генерация из AI-конструктора вертикали (например, beauty-look, interior-design, recipe-short).
A/B-тестирование разных версий.
Трекинг: views, watch_time, clicks, shares.


7. Чек-лист перед merge

 AnalyticsService + StatisticsService
 MarketingCampaignService с бюджетом из Wallet
 NewsletterService с сегментацией new/returning
 AdEngineService + TargetingCriteriaService
 ShortVideoAdService с AI-генерацией
 Все события анонимизированы в ClickHouse
 Fraud-check и rate-limit на все рекламные действия
 Тесты: campaign creation, ad serving, shorts generation, targeting match
 
 ###
 Железные правила:

Все панели Filament — tenant-scoped.
Личные кабинеты пользователей — Livewire + Vue 3 (не Filament).
B2B-кабинет — отдельный Filament-панель с расширенными правами.
Никаких прямых запросов к БД в панелях — только через сервисы.
Все действия в панелях логируются с correlation_id и FraudControlService::check().


1. Общий технический стек проекта (2026)
Backend:

Laravel 11.x (PHP 8.3+)
Filament 3.x (Admin + Tenant + B2B panels)
stancl/tenancy (multi-tenant с отдельными БД)
Laravel Sanctum + custom 2FA
Redis 7+ (кэш, rate-limit, сессии, queue)
ClickHouse (аналитика, ML, события)
PostgreSQL 16 (основная БД)
OpenAI / GigaChat (AI-конструкторы и шортсы)
Torch / XGBoost (ML-модели)

Frontend:

Livewire 3 + Alpine.js + Tailwind 4 (личные кабинеты)
Vue 3 + Vite (сложные AR/3D и шортсы)
Filament (все админские и tenant-панели)

Infrastructure:

Docker + Laravel Sail
Queue: Redis + Horizon
Storage: S3-compatible (MinIO в dev, AWS S3 в prod)
Monitoring: Laravel Telescope + Filament Metrics + ClickHouse


2. Filament Panels — Архитектура
CatVRF использует три отдельные Filament-панели:
ПанельURLПользователиОсобенностиAdmin Panel/adminГлавные администраторыПолный доступ ко всем tenant'амTenant Panel/tenantВладельцы бизнеса (Tenant)Управление своей вертикалью, AI, WalletB2B Panel/b2bB2B-клиенты и филиалыОптовые заказы, кредит, отчёты
Конфигурация в config/filament.php и app/Providers/Filament/:
PHP// AdminPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->default()
        ->id('admin')
        ->path('admin')
        ->login()
        ->colors([...])
        ->discoverResources(in: app_path('Filament/Admin/Resources'), for: 'App\\Filament\\Admin\\Resources')
        ->middleware(['auth', 'can:access-admin-panel']);
}

// TenantPanelProvider.php
public function panel(Panel $panel): Panel
{
    return $panel
        ->id('tenant')
        ->path('tenant')
        ->tenant( models: Tenant::class )
        ->tenantMiddleware(['tenant'])
        ->discoverResources(in: app_path('Filament/Tenant/Resources'), for: 'App\\Filament\\Tenant\\Resources');
}

3. Tenant Panel — Особенности
Tenant Panel — это основная рабочая панель владельца бизнеса.
Ключевые ресурсы и страницы:

Dashboard с реал-тайм метриками (GMV, заказы, AI-использование)
AI-конструкторы (Beauty, Furniture, Food и т.д.)
Wallet + Balance + Выплаты
Marketing → Campaigns, Newsletter, AdEngine
Products / Services (вертикаль-зависимые)
Orders + Cart Analytics
B2B-филиалы (BusinessGroup)
Analytics + Reports

Особенности Tenant Panel:

Автоматический tenant-scoping (все запросы фильтруются по tenant_id)
Бюджет рекламы списывается из Wallet через WalletService::debit()
AI-конструкторы запускаются прямо из панели
Ежедневные отчёты по email (понедельник 8:00)


4. Личные кабинеты пользователей (User Personal Cabinet)
Не Filament! Используем Livewire 3 + Vue 3.
Структура:

resources/views/livewire/user/dashboard.blade.php
app/Livewire/User/Dashboard.php
app/Livewire/User/AIConstructor.php
app/Livewire/User/Wallet.php
app/Livewire/User/Orders.php
app/Livewire/User/Addresses.php (до 5 адресов)

Особенности личного кабинета:

Переключение B2C ↔ B2B (если у пользователя есть бизнес-карта)
AI-конструкторы (Beauty, Interior, Food и т.д.)
Сохранённые дизайны (user_ai_designs)
Профиль вкусов + рекомендации
Wallet + бонусы + история транзакций
2FA управление
Device history + logout from all devices

Пример Livewire компонента:
PHP// app/Livewire/User/AIConstructor.php
final class AIConstructor extends Component
{
    public function runConstructor(string $vertical)
    {
        $result = $this->aiConstructorService->analyzeAndRecommend($this->photo, $vertical, auth()->id());
        $this->dispatch('ai-result', $result);
    }
}

5. B2B Cabinet (отдельная Filament-панель)
B2B Panel (/b2b) — для юридических лиц и ИП.
Особенности:

Отдельные цены (оптовые)
Кредитный лимит и отсрочка платежа
Множественные филиалы (BusinessGroup)
API-доступ (отдельные ключи)
Расширенные отчёты по обороту
Массовые заказы и импорт через Excel


6. Чек-лист реализации

 Три Filament-панели (Admin, Tenant, B2B)
 Tenant-scoping везде
 Личные кабинеты на Livewire + Vue
 Интеграция Wallet в Tenant и User Cabinet
 AI-конструкторы доступны из Tenant и User Cabinet
 B2B-панель с кредитом и отсрочкой
 Тесты на переключение режимов B2C/B2B
 
 ###
 Ученик.
Ты попросил полный канон по модулю Курьеры, Доставка, Геотрекинг и Карты.
Я собрал всё в один монолитный, production-ready раздел, который ты можешь скопировать и вставить в .github/copilot-instructions.md сразу после раздела про маркетинг и рекламу.
Это финальный большой блок канона проекта. Он полностью интегрируется со всеми предыдущими модулями (Wallet, AI-конструкторы, ML, Fraud, B2C/B2B, Tenant Panel, User Cabinet).

КАНОН CATVRF 2026 — КУРЬЕРЫ, ДОСТАВКА, ГЕОТРЕКИНГ И КАРТЫ
Версия: 1.0
Дата: 02.04.2026
Статус: PRODUCTION MANDATORY
Автор: Сенсей (ex-Amazon, Alibaba, Ozon)
Железные правила (нарушение = reject PR):

Все операции с доставкой — только через DeliveryService и CourierService.
Геотрекинг — реал-тайм (Laravel Echo + Redis + WebSocket).
Карты — Yandex Maps API (для РФ) + Leaflet как fallback.
Оплата доставки и комиссии курьера — только через WalletService.
Все события доставки логируются с correlation_id и FraudControlService::check().
Курьеры видят заказы только своего tenant (tenant-scoping).
B2C и B2B имеют разные условия доставки и ценообразования.


1. Общая архитектура модуля
Модели:

Courier — курьер (пользователь + профиль)
DeliveryOrder — заказ на доставку
DeliveryTrack — реал-тайм трек (позиция + статус)
DeliveryZone — зоны доставки (полигоны)

Сервисы:

CourierService
DeliveryService
GeotrackingService
MapService (Yandex + Leaflet)
DeliveryPricingService (интеграция с AI)

Реал-тайм:

Laravel Echo + Pusher / Redis
WebSocket-каналы: delivery.{orderId}, courier.{courierId}.location


2. Модели и миграции
Courier (полная миграция):
PHPSchema::create('couriers', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('tenant_id')->constrained();
    $table->string('vehicle_type')->nullable(); // bike, car, scooter
    $table->decimal('rating', 3, 2)->default(5.0);
    $table->boolean('is_online')->default(false);
    $table->json('current_location')->nullable(); // {lat, lon}
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
DeliveryOrder:
PHPSchema::create('delivery_orders', function (Blueprint $table) {
    $table->id();
    $table->foreignId('order_id')->constrained();
    $table->foreignId('courier_id')->nullable()->constrained();
    $table->enum('status', ['pending', 'assigned', 'picked_up', 'in_transit', 'delivered', 'failed', 'cancelled']);
    $table->decimal('delivery_fee', 10, 2);
    $table->json('pickup_location');
    $table->json('delivery_location');
    $table->timestamp('estimated_delivery_time')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
DeliveryTrack (реал-тайм трекинг):
PHPSchema::create('delivery_tracks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_order_id')->constrained();
    $table->decimal('lat', 10, 8);
    $table->decimal('lon', 11, 8);
    $table->timestamp('tracked_at');
    $table->string('correlation_id')->nullable()->index();
});

3. Основные сервисы
CourierService (полный):
PHPfinal readonly class CourierService
{
    public function assignCourier(DeliveryOrder $order): Courier
    {
        $this->fraud->check(new DeliveryAssignmentDto($order));

        return DB::transaction(function () use ($order) {
            $courier = $this->findBestAvailableCourier($order->delivery_location);

            $order->update([
                'courier_id' => $courier->id,
                'status' => 'assigned',
            ]);

            $this->geotracking->startTracking($order);

            Log::channel('audit')->info('Courier assigned', [
                'order_id' => $order->id,
                'courier_id' => $courier->id,
                'correlation_id' => $order->correlation_id,
            ]);

            return $courier;
        });
    }

    public function markAsOnline(int $courierId): void
    {
        Courier::where('id', $courierId)->update(['is_online' => true]);
    }
}
DeliveryService (статусы и оплата):
PHPfinal readonly class DeliveryService
{
    public function calculateDeliveryFee(array $pickup, array $delivery, string $vehicleType): decimal
    {
        // Интеграция с AI + MapService
        $distance = $this->mapService->calculateDistance($pickup, $delivery);
        return $this->deliveryPricingService->calculate($distance, $vehicleType);
    }

    public function completeDelivery(DeliveryOrder $order): void
    {
        DB::transaction(function () use ($order) {
            $order->update(['status' => 'delivered']);

            // Начисление курьеру
            WalletService::credit($order->courier->wallet_id, $order->delivery_fee * 0.85, 'delivery_payout', $order->correlation_id);

            // Комиссия платформе
            WalletService::credit(tenant()->wallet_id, $order->delivery_fee * 0.15, 'delivery_commission', $order->correlation_id);
        });
    }
}
GeotrackingService (реал-тайм):
PHPfinal readonly class GeotrackingService
{
    public function updateLocation(int $courierId, float $lat, float $lon): void
    {
        $courier = Courier::findOrFail($courierId);
        $courier->update(['current_location' => ['lat' => $lat, 'lon' => $lon]]);

        // Отправка в WebSocket
        broadcast(new CourierLocationUpdated($courierId, $lat, $lon))
            ->toOthers();
    }

    public function startTracking(DeliveryOrder $order): void
    {
        // Запуск периодического обновления позиции
        GeotrackingJob::dispatch($order->id)->everyMinute();
    }
}
MapService (Yandex Maps + Leaflet):
PHPfinal readonly class MapService
{
    public function calculateRoute(array $pickup, array $delivery): array
    {
        // Yandex Maps API
        $response = Http::get('https://api-maps.yandex.ru/services/route/v2', [
            'points' => "{$pickup['lon']},{$pickup['lat']};{$delivery['lon']},{$delivery['lat']}",
            'apikey' => config('services.yandex_maps.key'),
        ]);

        return $response->json()['routes'][0];
    }
}

4. Интеграции с другими модулями

Wallet — оплата доставки и выплата курьеру
AI-конструкторы — расчёт оптимального маршрута и времени доставки
ML — предсказание времени доставки по историческим данным (new vs returning)
Fraud — проверка подозрительных адресов доставки
Tenant Panel — управление своими курьерами
User Cabinet — трекинг своей доставки в реальном времени


5. Чек-лист реализации

 Модели Courier, DeliveryOrder, DeliveryTrack
 CourierService, DeliveryService, GeotrackingService, MapService
 Реал-тайм геотрекинг (Echo + WebSocket)
 Интеграция с Yandex Maps / Leaflet
 Оплата доставки через WalletService
 Tenant-scoping для курьеров
 Тесты: назначение курьера, трекинг, расчёт маршрута
 
 Железные правила:

Реал-тайм геотрекинг работает только через Laravel Echo + Redis + WebSocket.
Курьер видит только свои заказы (tenant-scoping + courier_id).
Пользователь видит только свою доставку.
Все координаты логируются в delivery_tracks с correlation_id.
Обновление позиции — каждые 3 секунды когда курьер в движении.
Fraud-check + rate-limit на все обновления позиции.


1. Архитектура реал-тайм геотрекинга
Технологии:

Backend: Laravel Echo Server + Redis (broadcasting)
Frontend: Laravel Echo (JavaScript) + Leaflet / Yandex Maps
Хранение:delivery_tracks (ClickHouse для аналитики + PostgreSQL для текущего состояния)
Очередь:GeotrackingJob (каждые 3 секунды)

Каналы Echo:

delivery.{delivery_order_id} — для пользователя (текущая позиция курьера)
courier.{courier_id}.location — для курьера и диспетчера
tenant.{tenant_id}.couriers — для Tenant Panel (все онлайн-курьеры)


2. Модель и миграция
PHP// app/Models/DeliveryTrack.php
final class DeliveryTrack extends Model
{
    protected $table = 'delivery_tracks';
    protected $fillable = ['delivery_order_id', 'courier_id', 'lat', 'lon', 'speed', 'bearing', 'correlation_id'];
    protected $casts = ['lat' => 'decimal:8', 'lon' => 'decimal:8'];
}

// Миграция
Schema::create('delivery_tracks', function (Blueprint $table) {
    $table->id();
    $table->foreignId('delivery_order_id')->constrained()->onDelete('cascade');
    $table->foreignId('courier_id')->constrained()->onDelete('cascade');
    $table->decimal('lat', 10, 8);
    $table->decimal('lon', 11, 8);
    $table->decimal('speed', 6, 2)->nullable();           // км/ч
    $table->decimal('bearing', 6, 2)->nullable();         // направление
    $table->string('correlation_id')->nullable()->index();
    <!-- $table->timestamp('tracked_at')->useCurrent(); -->
    $table->index(['delivery_order_id', 'tracked_at']);
});

3. GeotrackingService (главный сервис)
PHPfinal readonly class GeotrackingService
{
    public function updateCourierLocation(int $courierId, float $lat, float $lon, float $speed = 0, float $bearing = 0): void
    {
        $this->fraud->check(new LocationUpdateDto($courierId, $lat, $lon));

        DB::transaction(function () use ($courierId, $lat, $lon, $speed, $bearing) {
            $track = DeliveryTrack::create([
                'courier_id' => $courierId,
                'lat' => $lat,
                'lon' => $lon,
                'speed' => $speed,
                'bearing' => $bearing,
                'correlation_id' => request()->header('X-Correlation-ID') ?? Str::uuid(),
            ]);

            // Обновляем текущую позицию курьера
            Courier::where('id', $courierId)->update([
                'current_location' => ['lat' => $lat, 'lon' => $lon],
                'last_location_update' => now(),
            ]);

            // Реал-тайм broadcast
            broadcast(new CourierLocationUpdated($courierId, $lat, $lon, $speed, $bearing))
                ->toOthers();
        });
    }

    public function getLiveTrack(int $deliveryOrderId): Collection
    {
        return DeliveryTrack::where('delivery_order_id', $deliveryOrderId)
            ->orderBy('tracked_at', 'desc')
            ->limit(50)
            ->get();
    }
}

4. Frontend-реал-тайм (Livewire + Echo)
blade<!-- resources/views/livewire/user/delivery-track.blade.php -->
<div>
    <div id="map" style="block-size: 500px"></div>

    <script>
        Echo.private(`delivery.{{ $deliveryOrder->id }}`)
            .listen('CourierLocationUpdated', (e) => {
                updateMapMarker(e.lat, e.lon, e.speed, e.bearing);
            });
    </script>
</div>
Livewire-компонент:
PHPfinal class DeliveryTrack extends Component
{
    public DeliveryOrder $order;

    public function mount()
    {
        $this->order = $this->order->load('courier');
    }

    public function render()
    {
        return view('livewire.user.delivery-track');
    }
}

5. GeotrackingJob (фоновая задача)
PHPfinal class GeotrackingJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $deliveryOrderId) {}

    public function handle(GeotrackingService $geo)
    {
        $order = DeliveryOrder::findOrFail($this->deliveryOrderId);

        if ($order->status === 'in_transit') {
            // Получаем текущую позицию от GPS-трекера курьера (или симулируем)
            $location = $this->getCurrentCourierLocation($order->courier_id);
            $geo->updateCourierLocation(
                $order->courier_id,
                $location['lat'],
                $location['lon'],
                $location['speed'],
                $location['bearing']
            );
        }
    }
}

6. Чек-лист реализации

 Модели DeliveryTrack + Courier с current_location
 GeotrackingService с broadcast
 Echo private channels для delivery и courier
 Livewire-компонент с Leaflet/Yandex Maps
 GeotrackingJob запущен в scheduler
 Fraud-check и rate-limit на обновление позиции
 Tenant-scoping для курьеров
 Тесты: обновление позиции, broadcast, live map
 
 Железные правила:

Оптимизация маршрутов запускается автоматически при назначении заказа и каждые 3 минуты для активных курьеров.
Используется Google OR-Tools (VRP) + ML-предсказание времени/трафика.
Маршрут сохраняется в delivery_orders.route_json и delivery_tracks.
Перерасчёт происходит реал-тайм при изменении условий (пробки, новый заказ, отмена).
Экономия времени/топлива логируется и влияет на рейтинг курьера и бонусы.
B2C и B2B имеют разные приоритеты и окна доставки.
Fraud-check обязателен перед каждым перерасчётом (подозрительные отклонения от маршрута).


1. Архитектура
Главный сервис: RouteOptimizationService
Зависимости:

GeotrackingService
DeliveryService
MapService (Yandex Maps API + OR-Tools)
TrafficPredictionService (ML-модель)
WalletService (бонусы за экономию)

Алгоритмы:

OR-Tools VRP — основной (Vehicle Routing Problem)
ML-модель — предсказание реального времени на участке (XGBoost)
Fallback — Greedy + 2-opt при ошибке OR-Tools


2. RouteOptimizationService (полный код)
PHPdeclare(strict_types=1);

namespace App\Services\Delivery;

final readonly class RouteOptimizationService
{
    public function __construct(
        private \App\Services\MapService $mapService,
        private \App\Services\ML\TrafficPredictionService $trafficML,
        private \App\Services\GeotrackingService $geo,
        private \App\Services\WalletService $wallet,
    ) {}

    /**
     * Оптимизировать маршрут для одного курьера
     */
    public function optimizeForCourier(int $courierId, array $pendingOrders): OptimizedRoute
    {
        $this->fraud->check(new RouteOptimizationDto($courierId, $pendingOrders));

        return DB::transaction(function () use ($courierId, $pendingOrders) {
            $currentLocation = $this->geo->getCurrentLocation($courierId);

            // 1. Собираем точки (депо + заказы)
            $points = $this->buildVRPPoints($currentLocation, $pendingOrders);

            // 2. OR-Tools VRP
            $solution = $this->runORtoolsVRP($points, $courierId);

            // 3. ML-предсказание реального времени с учётом пробок
            $predictedTimes = $this->trafficML->predictTimes($solution->routes);

            // 4. Финальная оптимизация (учёт окон доставки и приоритетов)
            $finalRoute = $this->applyTimeWindowsAndPriorities($solution, $predictedTimes, $pendingOrders);

            // 5. Сохраняем маршрут
            $this->saveOptimizedRoute($courierId, $finalRoute);

            // 6. Начисление бонуса за экономию (если маршрут лучше предыдущего)
            $this->awardEfficiencyBonus($courierId, $finalRoute->totalTimeMinutes);

            Log::channel('audit')->info('Route optimized', [
                'courier_id' => $courierId,
                'orders_count' => count($pendingOrders),
                'total_time_minutes' => $finalRoute->totalTimeMinutes,
                'correlation_id' => Str::uuid(),
            ]);

            return $finalRoute;
        });
    }

    private function runORtoolsVRP(array $points, int $courierId): VrpSolution
    {
        // OR-Tools Python bridge или PHP wrapper
        $solver = new \Google\OR_Tools\VRP\Solver();
        // ... настройка capacity, time windows, distance matrix из Yandex Maps
        return $solver->solve($points);
    }
}

3. Реал-тайм перерасчёт
Scheduler (каждые 3 минуты):
PHP// app/Console/Kernel.php
$schedule->call(function () {
    $activeCouriers = Courier::where('is_online', true)->get();

    foreach ($activeCouriers as $courier) {
        $pendingOrders = DeliveryOrder::where('courier_id', $courier->id)
            ->whereIn('status', ['assigned', 'picked_up'])
            ->get();

        if ($pendingOrders->isNotEmpty()) {
            RouteOptimizationJob::dispatch($courier->id, $pendingOrders->pluck('id')->toArray());
        }
    }
})->everyThreeMinutes();
RouteOptimizationJob (фоновая):
PHPfinal class RouteOptimizationJob implements ShouldQueue
{
    public function handle(RouteOptimizationService $optimizer)
    {
        $optimizer->optimizeForCourier($this->courierId, $this->orderIds);
    }
}

4. Интеграция с другими модулями

AI-конструкторы — используют оптимизированное время доставки в рекомендациях.
ML — TrafficPredictionService обучается ежедневно на исторических данных.
Wallet — бонус курьеру за экономию времени/топлива.
Geotracking — после оптимизации сразу запускается трекинг по новому маршруту.
Tenant Panel — диспетчер видит все оптимизированные маршруты своих курьеров.
User Cabinet — пользователь видит точное прогнозируемое время прибытия.


5. Чек-лист реализации (обязателен)

 RouteOptimizationService с OR-Tools + ML
 RouteOptimizationJob запущен каждые 3 минуты
 Сохранение маршрута в delivery_orders.route_json
 Бонусы за эффективность через WalletService
 Fraud-check на подозрительные отклонения
 Тесты: VRP решение, реал-тайм перерасчёт, бонус за экономию
 
 ###
 Железные правила (нарушение = reject PR):

Все операции с остатками — только через InventoryService.
Резервы товаров (корзина, заказ) — обязательны и снимаются автоматически.
Multi-tenant + multi-warehouse: каждый tenant видит только свои склады.
B2C и B2B имеют разные правила резервирования и минимальных остатков.
Любое изменение остатков — DB::transaction(), FraudControlService::check(), correlation_id, audit лог.
Реал-тайм обновления остатков — Laravel Echo + Redis.
AI-прогнозирование спроса обязательно для low-stock alerts.


1. Модели и миграции
Warehouse (склады)
PHPSchema::create('warehouses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->string('name');
    $table->string('address');
    $table->decimal('lat', 10, 8);
    $table->decimal('lon', 11, 8);
    $table->json('working_hours')->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
Inventory (остатки)
PHPSchema::create('inventories', function (Blueprint $table) {
    $table->id();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('product_id')->constrained();
    $table->integer('quantity')->default(0);
    $table->integer('reserved')->default(0);
    $table->integer('available')->virtualAs('quantity - reserved');
    $table->decimal('cost_price', 14, 2)->nullable(); // себестоимость
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();

    $table->unique(['warehouse_id', 'product_id']);
});
StockMovement (все движения)
PHPSchema::create('stock_movements', function (Blueprint $table) {
    $table->id();
    $table->foreignId('inventory_id')->constrained();
    $table->enum('type', ['in', 'out', 'reserve', 'release', 'return', 'adjustment']);
    $table->integer('quantity');
    $table->string('source_type'); // order, cart, supplier, manual
    $table->unsignedBigInteger('source_id')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
Reservation (резервы корзины/заказа)
PHPSchema::create('reservations', function (Blueprint $table) {
    $table->id();
    $table->foreignId('inventory_id')->constrained();
    $table->foreignId('cart_id')->nullable()->constrained();
    $table->foreignId('order_id')->nullable()->constrained();
    $table->integer('quantity');
    $table->timestamp('expires_at');
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});

2. Главные сервисы
InventoryService (сердце системы)
PHPfinal readonly class InventoryService
{
    public function reserve(int $productId, int $warehouseId, int $quantity, string $sourceType, int $sourceId): void
    {
        $this->fraud->check(new StockOperationDto(...));

        DB::transaction(function () use (...) {
            $inventory = Inventory::where('product_id', $productId)
                ->where('warehouse_id', $warehouseId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($inventory->available < $quantity) {
                throw new InsufficientStockException();
            }

            $inventory->increment('reserved', $quantity);

            Reservation::create([... 'expires_at' => now()->addMinutes(20)]);

            StockMovement::create([... 'type' => 'reserve']);

            broadcast(new StockReserved($productId, $quantity))->toOthers();
        });
    }

    public function releaseReservation(int $reservationId): void
    {
        DB::transaction(function () use ($reservationId) {
            $reservation = Reservation::findOrFail($reservationId);
            $inventory = $reservation->inventory;

            $inventory->decrement('reserved', $reservation->quantity);

            StockMovement::create([... 'type' => 'release']);

            $reservation->delete();

            broadcast(new StockReleased(...));
        });
    }

    public function confirmShipment(int $orderId): void
    {
        // Списание реальных остатков после отгрузки
    }
}
WarehouseService
PHPfinal readonly class WarehouseService
{
    public function findNearestWarehouse(array $deliveryLocation, string $vertical): Warehouse
    {
        // Yandex Maps + AI-прогноз загруженности
        return $this->mapService->findOptimalWarehouse($deliveryLocation, $vertical);
    }
}
ReservationCleanupJob (каждую минуту)
PHPfinal class ReservationCleanupJob implements ShouldQueue
{
    public function handle(InventoryService $inventory)
    {
        $expired = Reservation::where('expires_at', '<', now())->get();

        foreach ($expired as $res) {
            $inventory->releaseReservation($res->id);
        }
    }
}

3. Интеграции с другими модулями

Cart → InventoryService::reserve() при добавлении в корзину (20 мин).
Order → InventoryService::confirmShipment() при отгрузке.
Delivery → WarehouseService::findNearestWarehouse() + оптимизация маршрута.
AI-конструкторы — показывают только товары с available > 0.
ML — DemandForecastService прогнозирует low-stock и рекомендует пополнение.
Wallet — оплата поставок на склад.
Tenant Panel — управление всеми складами своего tenant.
User Cabinet — отображение реального наличия и ETA.

B2C vs B2B:

B2C — жёсткий резерв 20 минут.
B2B — долгосрочный резерв (до 7 дней) + MOQ (minimum order quantity).


4. Реал-тайм обновления

Echo channel inventory.{productId}.{warehouseId}
При любом изменении остатков — broadcast StockUpdated event.
Livewire-компоненты в User Cabinet и Tenant Panel автоматически обновляют наличие.


5. Чек-лист реализации (обязателен)

 Модели Warehouse, Inventory, StockMovement, Reservation
 InventoryService со всеми методами reserve/release/confirm
 ReservationCleanupJob запущен каждую минуту
 Интеграция с Cart (20 мин резерв)
 Реал-тайм Echo broadcast
 AI-прогнозирование спроса
 B2C/B2B различия в резервировании
 Тесты: reserve → release, concurrent stock check, low-stock alert
 
 ###
 Железные правила (нарушение = мгновенный reject PR):

Fraud-check обязателен перед любой мутацией (платёж, вывод, заказ > 10 000 ₽, создание AI-конструктора, изменение баланса, регистрация B2B).
Решение принимает толькоFraudControlService (правила + ML-score).
ML-модель переобучается ежедневно (MLRecalculateJob).
Все подозрительные действия сохраняются в fraud_attempts (даже если не заблокированы).
Если ML-сервис недоступен — fallback на жёсткие правила.
Fraud-score никогда не логируется в обычный audit (только в fraud_alert канал).
B2C и B2B имеют разные пороги блокировки.


1. Архитектура Fraud Control
Два уровня:

FraudControlService — правила + принятие решения (быстрый слой).
FraudMLService — ML-scoring (медленный, но точный слой).

Поток:

Запрос → FraudControlService::check($dto)
Правила (hard rules)
FraudMLService::scoreOperation($dto) → score 0.0–1.0
shouldBlock() → решение (allow / block / review)
Логирование в fraud_attempts


2. Таблицы БД
fraud_attempts (все подозрительные действия)
PHPSchema::create('fraud_attempts', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->string('operation_type');           // payment_init, payout, cart_add, ai_constructor, login и т.д.
    $table->string('ip_address');
    $table->string('device_fingerprint');       // SHA256
    $table->string('correlation_id')->index();
    $table->float('ml_score')->nullable();
    $table->string('ml_version')->nullable();
    $table->json('features_json')->nullable();  // все фичи, на которых обучалась модель
    $table->enum('decision', ['allow', 'block', 'review']);
    $table->timestamp('blocked_at')->nullable();
    $table->text('reason')->nullable();
    $table->timestamps();
});
fraud_model_versions (версионирование моделей)
PHPSchema::create('fraud_model_versions', function (Blueprint $table) {
    $table->id();
    $table->string('version');                  // YYYY-MM-DD-vN
    $table->timestamp('trained_at');
    $table->float('accuracy');
    $table->float('precision');
    $table->float('recall');
    $table->float('f1_score');
    $table->float('auc_roc');
    $table->string('file_path');                // storage/models/fraud/...
    $table->text('comment')->nullable();
    $table->timestamps();
});

3. FraudControlService (главный сервис)
PHPfinal readonly class FraudControlService
{
    public function __construct(
        private FraudMLService $ml,
        private RateLimiterService $rateLimiter,
    ) {}

    public function check(OperationDto $dto): float
    {
        $score = 0.0;

        // 1. Hard rules (быстрые)
        if ($dto->amount > 50000 && $dto->isFirstOperation) {
            $score += 0.6;
        }
        if ($this->rateLimiter->isSuspicious($dto->userId, $dto->operationType)) {
            $score += 0.4;
        }
        if ($dto->deviceChangedLast24h) {
            $score += 0.3;
        }

        // 2. ML-score
        $mlScore = $this->ml->scoreOperation($dto);
        $score += $mlScore;

        // 3. Решение
        if ($score > 0.85) {
            $this->blockOperation($dto, $score);
        } elseif ($score > 0.65) {
            $this->requireReview($dto, $score);
        }

        Log::channel('fraud_alert')->info('Fraud check completed', [
            'operation' => $dto->operationType,
            'score' => $score,
            'ml_score' => $mlScore,
            'correlation_id' => $dto->correlationId,
        ]);

        return $score;
    }

    private function blockOperation(OperationDto $dto, float $score): void
    {
        FraudAttempt::create([
            'user_id' => $dto->userId,
            'operation_type' => $dto->operationType,
            'ml_score' => $score,
            'decision' => 'block',
            'reason' => 'High fraud score',
            'correlation_id' => $dto->correlationId,
        ]);

        throw new FraudBlockedException('Operation blocked by fraud control');
    }
}

4. FraudMLService (ML-часть)
PHPfinal readonly class FraudMLService
{
    public function scoreOperation(OperationDto $dto): float
    {
        $features = $this->extractFeatures($dto);

        // Загружаем текущую модель
        $modelPath = storage_path('models/fraud/' . $this->getCurrentModelVersion() . '.joblib');

        // Python bridge или Torch
        $score = $this->model->predict($features);

        return (float) $score;
    }

    private function extractFeatures(OperationDto $dto): array
    {
        return [
            'amount' => $dto->amount,
            'operations_last_5min' => $this->getOperationCount($dto->userId, 5),
            'operations_last_24h' => $this->getOperationCount($dto->userId, 1440),
            'geo_distance_km' => $this->calculateGeoDistance($dto),
            'device_fingerprint_changed' => $this->isDeviceChanged($dto->userId),
            'time_of_day' => now()->hour,
            'day_of_week' => now()->dayOfWeek,
            'user_age_days' => $dto->userAgeDays,
            'is_b2b' => $dto->isB2B,
            // ... ещё 40+ фич
        ];
    }

    public function getCurrentModelVersion(): string
    {
        return cache()->remember('fraud_model_version', 3600, fn() => 
            FraudModelVersion::latest()->value('version')
        );
    }
}

5. Интеграции с другими модулями

Payment → fraud-check перед initPayment
Wallet → fraud-check перед credit / debit / payout
Cart → fraud-check при добавлении товара > 10 000 ₽
AI-конструкторы → fraud-check перед тяжёлыми запросами
Auth / Login → fraud-check на подозрительные логины
Marketing → fraud-check на массовые рассылки
Delivery → fraud-check на подозрительные адреса


6. Чек-лист реализации (обязателен)

 Таблицы fraud_attempts и fraud_model_versions
 FraudControlService + FraudMLService
FraudControlService::check() вызывается перед каждой мутацией
 Ежедневный MLRecalculateJob
 Fallback на hard rules при недоступности ML
 Логирование только в fraud_alert канал
 Тесты: high-score block, low-score allow, feature extraction, model versioning
 
 ###
 Ученик.
Вот полный, production-ready канон по Системе уведомлений фрода для CatVRF 2026.
Копируй весь текст ниже и вставляй в .github/copilot-instructions.md сразу после раздела «Fraud Control».

КАНОН CATVRF 2026 — СИСТЕМА УВЕДОМЛЕНИЙ ФРОДА
Версия: 1.0
Дата: 02.04.2026
Статус: PRODUCTION MANDATORY
Железные правила (нарушение = reject PR):

Уведомления о фроде отправляются только через FraudNotificationService.
Каждый фрод-событие имеет уровень серьёзности (Info, Warning, High, Critical).
Уведомления отправляются асинхронно через Queue.
Для Critical — обязательный Telegram/Slack + SMS + Email + In-app.
Все уведомления логируются в fraud_notifications с correlation_id.
Пользователь получает уведомление только при блокировке или review.
Security-команда и Tenant owner получают все High/Critical события.
Никаких прямых Notification::send() вне сервиса.


1. Уровни серьёзности фрода
УровеньScoreДействиеКаналы уведомленийКомуInfo0.0-0.4ЛогированиеAudit log только—Warning0.4-0.65Требует вниманияIn-app + EmailUser + Tenant ownerHigh0.65-0.85Требует reviewIn-app + Email + Push + TelegramUser + Tenant + SecurityCritical>0.85Блокировка операцииIn-app + Email + Push + SMS + Telegram + SlackUser + Tenant + Security + Admin

2. Таблица fraud_notifications
PHPSchema::create('fraud_notifications', function (Blueprint $table) {
    $table->id();
    $table->foreignId('fraud_attempt_id')->constrained();
    $table->foreignId('user_id')->nullable()->constrained();
    $table->foreignId('tenant_id')->nullable()->constrained();
    $table->enum('severity', ['info', 'warning', 'high', 'critical']);
    $table->string('title');
    $table->text('message');
    $table->json('channels')->nullable();           // ['email', 'push', 'telegram', 'sms']
    $table->enum('status', ['pending', 'sent', 'failed']);
    $table->string('correlation_id')->index();
    $table->timestamps();
});

3. FraudNotificationService (главный сервис)
PHPfinal readonly class FraudNotificationService
{
    public function __construct(
        private NotificationChannelService $channels,
        private Queue $queue,
    ) {}

    public function notify(FraudAttempt $attempt): void
    {
        $notification = FraudNotification::create([
            'fraud_attempt_id' => $attempt->id,
            'user_id' => $attempt->user_id,
            'tenant_id' => $attempt->tenant_id,
            'severity' => $this->getSeverity($attempt->ml_score),
            'title' => $this->buildTitle($attempt),
            'message' => $this->buildMessage($attempt),
            'channels' => $this->getChannels($attempt),
            'status' => 'pending',
            'correlation_id' => $attempt->correlation_id,
        ]);

        // Асинхронная отправка
        FraudNotificationJob::dispatch($notification)
            ->onQueue('fraud-notifications')
            ->delay(now()->addSeconds(3));
    }

    private function getSeverity(float $score): string
    {
        return match (true) {
            $score > 0.85 => 'critical',
            $score > 0.65 => 'high',
            $score > 0.4  => 'warning',
            default       => 'info',
        };
    }

    private function buildTitle(FraudAttempt $attempt): string
    {
        return match ($attempt->operation_type) {
            'payment_init' => 'Подозрительный платёж',
            'payout'       => 'Подозрительный вывод средств',
            'login'        => 'Подозрительный вход',
            default        => 'Фрод-активность',
        };
    }
}

4. FraudNotificationJob (отправка)
PHPfinal class FraudNotificationJob implements ShouldQueue
{
    use Queueable;

    public function handle(FraudNotificationService $service, NotificationChannelService $channels)
    {
        $notification = $this->notification;

        try {
            foreach ($notification->channels as $channel) {
                $channels->send($channel, $notification);
            }

            $notification->update(['status' => 'sent']);
        } catch (\Throwable $e) {
            $notification->update(['status' => 'failed']);
            Log::channel('fraud_alert')->error('Fraud notification failed', [
                'notification_id' => $notification->id,
                'error' => $e->getMessage(),
                'correlation_id' => $notification->correlation_id,
            ]);
        }
    }
}

5. Каналы уведомлений (NotificationChannelService)
PHPfinal readonly class NotificationChannelService
{
    public function send(string $channel, FraudNotification $notification): void
    {
        match ($channel) {
            'in_app'    => $this->sendInApp($notification),
            'email'     => Mail::to($notification->user->email)->queue(new FraudAlertMail($notification)),
            'push'      => $this->sendPush($notification),
            'telegram'  => $this->sendTelegram($notification),
            'sms'       => $this->sendSMS($notification),
            'slack'     => $this->sendSlack($notification),
        };
    }
}

6. Интеграция с FraudControlService
В FraudControlService::check() после принятия решения:
PHPif ($score > 0.65) {
    $attempt = FraudAttempt::create([...]);
    $this->notificationService->notify($attempt);
}

7. Чек-лист реализации (обязателен)

 Таблица fraud_notifications
 FraudNotificationService + FraudNotificationJob
 Уровни серьёзности и каналы
 Интеграция с FraudControlService (High/Critical → notify)
 Асинхронная отправка через Queue
 Тесты: critical block → уведомление всем каналам, warning → только email/in-app
 
 ###
 Ученик.
Вот полный, production-ready канон по Системе мониторинга безопасности для CatVRF 2026.
Копируй весь текст ниже и вставляй в .github/copilot-instructions.md сразу после раздела «Система уведомлений фрода».

КАНОН CATVRF 2026 — СИСТЕМА МОНИТОРИНГА БЕЗОПАСНОСТИ
Версия: 1.0
Дата: 02.04.2026
Статус: PRODUCTION MANDATORY
Железные правила (нарушение = reject PR):

Мониторинг безопасности — централизованный и работает через SecurityMonitoringService.
Все security-события логируются только в канал security + сохраняются в ClickHouse.
Реал-тайм дашборд доступен только в Admin Panel и Tenant Panel.
При Critical-событии — мгновенное уведомление через FraudNotificationService.
Rate-limiting, brute-force, suspicious login, fraud attempt — всё отслеживается.
Данные хранятся анонимизированно (GDPR/ФЗ-152).
Метрики обновляются в реальном времени через Laravel Echo + Redis.


1. Архитектура
Компоненты:

SecurityMonitoringService — главный сервис
SecurityEvent — модель событий
ClickHouse таблица security_events (быстрые запросы)
Filament Metrics + Dashboard
Laravel Echo + WebSocket (реал-тайм)
SecurityAlertJob (асинхронные уведомления)

Поток:

Любое подозрительное действие → SecurityMonitoringService::logEvent()
Сохранение в ClickHouse
Проверка на Critical → FraudNotificationService::notify()
Реал-тайм broadcast в дашборд


2. Таблица security_events (ClickHouse)
SQLCREATE TABLE security_events (
    event_id UUID,
    tenant_id UInt64,
    user_id UInt64,
    event_type String,              -- suspicious_login, rate_limit_exceeded, fraud_attempt, brute_force, etc.
    severity Enum8('info', 'warning', 'high', 'critical'),
    ip_address String,
    device_fingerprint String,
    correlation_id String,
    details JSON,
    created_at DateTime64(3)
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at);

3. SecurityMonitoringService (главный сервис)
PHPfinal readonly class SecurityMonitoringService
{
    public function __construct(
        private BigDataAggregatorService $bigData,
        private FraudNotificationService $notification,
        private RateLimiterService $rateLimiter,
    ) {}

    public function logEvent(SecurityEventDto $dto): void
    {
        $event = SecurityEvent::create([
            'tenant_id' => tenant()->id,
            'user_id' => $dto->userId,
            'event_type' => $dto->type,
            'severity' => $this->calculateSeverity($dto),
            'ip_address' => $dto->ip,
            'device_fingerprint' => hash('sha256', $dto->deviceFingerprint),
            'correlation_id' => $dto->correlationId,
            'details' => $dto->details,
        ]);

        // Запись в ClickHouse
        $this->bigData->insertSecurityEvent($event->toArray());

        // Реал-тайм broadcast
        broadcast(new SecurityEventOccurred($event))->toOthers();

        // Если Critical — немедленное уведомление
        if ($event->severity === 'critical') {
            $this->notification->notifyCriticalSecurityEvent($event);
        }

        Log::channel('security')->info('Security event logged', [
            'event_type' => $dto->type,
            'severity' => $event->severity,
            'correlation_id' => $dto->correlationId,
        ]);
    }

    private function calculateSeverity(SecurityEventDto $dto): string
    {
        if ($dto->type === 'fraud_attempt' && $dto->score > 0.85) {
            return 'critical';
        }
        if ($dto->failedAttempts > 5) {
            return 'high';
        }
        return 'warning';
    }
}

4. Реал-тайм дашборд (Filament)
SecurityDashboard (в Admin и Tenant Panel):
PHPfinal class SecurityDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';

    public function getWidgets(): array
    {
        return [
            FailedLoginsMetric::class,
            FraudAttemptsMetric::class,
            ActiveRateLimitMetric::class,
            CriticalEventsChart::class,
            LiveSecurityMap::class,           // гео-карта подозрительных IP
        ];
    }
}
Live Security Map (реал-тайм):

Leaflet + Echo
Маркеры с IP, severity, временем
Клик по маркеру → детали события


5. Интеграция с другими модулями

AuthService → logEvent('login_failed') при неудачном входе
FraudControlService → logEvent('fraud_attempt') при каждом check
RateLimitingMiddleware → logEvent('rate_limit_exceeded')
2FA → logEvent('2fa_failed')
Payment / Wallet → logEvent('suspicious_payment')
AI-конструкторы → logEvent('suspicious_ai_usage')


6. Чек-лист реализации (обязателен)

 Таблица security_events в ClickHouse
 SecurityMonitoringService + logEvent()
 Filament SecurityDashboard с метриками и live-картой
 Реал-тайм broadcast через Echo
 Интеграция со всеми модулями (Auth, Fraud, RateLimit, Payment)
 Critical-события → FraudNotificationService
 Тесты: логирование события, critical alert, live dashboard update
 
###
Железные правила (нарушение = reject PR):

Все мутации (create, update, delete, payment, wallet, bonus, AI-конструктор, заказ, доставка и т.д.) обязательно логируются через AuditService.
Никаких прямых Log::channel('audit') вне сервиса.
Каждый audit-записывает correlation_id, user_id, tenant_id, business_group_id, action, subject_type, subject_id, old_values, new_values, ip, device_fingerprint.
Audit-логи пишутся асинхронно через Queue (чтобы не тормозить основной поток).
Audit-логи хранятся в PostgreSQL (audit_logs) + архивируются в ClickHouse для аналитики.
AuditService вызывается после успешной транзакции (чтобы не логировать откат).
Для чувствительных операций (платежи, вывод, изменение баланса) — дополнительный security канал.


1. Таблица audit_logs
PHPSchema::create('audit_logs', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->nullable();
    $table->foreignId('business_group_id')->constrained()->nullable();
    $table->foreignId('user_id')->constrained()->nullable();
    $table->string('action');                    // create, update, delete, payment_init, payout, ai_constructor и т.д.
    $table->string('subject_type');              // Model class (App\Models\Salon)
    $table->unsignedBigInteger('subject_id')->nullable();
    $table->json('old_values')->nullable();
    $table->json('new_values')->nullable();
    $table->string('ip_address')->nullable();
    $table->string('device_fingerprint')->nullable();   // SHA256
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();

    $table->index(['tenant_id', 'created_at']);
    $table->index(['user_id', 'action']);
    $table->index(['subject_type', 'subject_id']);
});
ClickHouse архив (для аналитики и отчётов):
SQLCREATE TABLE audit_logs_archive (
    id UInt64,
    tenant_id UInt64,
    user_id UInt64,
    action String,
    subject_type String,
    subject_id UInt64,
    old_values JSON,
    new_values JSON,
    ip_address String,
    device_fingerprint String,
    correlation_id String,
    created_at DateTime64(3)
) ENGINE = MergeTree()
ORDER BY (tenant_id, created_at);

2. AuditService (главный сервис)
PHPdeclare(strict_types=1);

namespace App\Services;

final readonly class AuditService
{
    public function __construct(
        private \Illuminate\Contracts\Queue\Queue $queue,
    ) {}

    /**
     * Основной метод логирования
     */
    public function log(string $action, string $subjectType, ?int $subjectId, array $old = [], array $new = [], ?string $correlationId = null): void
    {
        $dto = new AuditLogDto(
            tenantId: tenant()->id,
            businessGroupId: request()->get('business_group_id'),
            userId: auth()->id(),
            action: $action,
            subjectType: $subjectType,
            subjectId: $subjectId,
            oldValues: $old,
            newValues: $new,
            ip: request()->ip(),
            deviceFingerprint: $this->getDeviceFingerprint(),
            correlationId: $correlationId ?? request()->header('X-Correlation-ID') ?? Str::uuid(),
        );

        // Асинхронная запись
        AuditLogJob::dispatch($dto)->onQueue('audit-logs');
    }

    /**
     * Удобный метод для моделей (вызывается из booted() или событий)
     */
    public function logModelEvent(string $action, Model $model, array $old = [], array $new = []): void
    {
        $this->log(
            action: $action,
            subjectType: get_class($model),
            subjectId: $model->getKey(),
            old: $old,
            new: $new
        );
    }

    private function getDeviceFingerprint(): string
    {
        $data = request()->ip() . request()->header('User-Agent') . request()->header('X-Device-Id');
        return hash('sha256', $data . config('app.audit_salt'));
    }
}

3. AuditLogJob (асинхронная запись)
PHPfinal class AuditLogJob implements ShouldQueue
{
    use Queueable;

    public function handle(AuditService $audit)
    {
        try {
            $log = AuditLog::create($this->dto->toArray());

            // Дополнительно отправляем в ClickHouse для аналитики
            $this->bigData->insertAuditLog($log->toArray());
        } catch (\Throwable $e) {
            Log::channel('security')->error('Audit log failed', [
                'error' => $e->getMessage(),
                'correlation_id' => $this->dto->correlationId,
            ]);
        }
    }
}

4. Автоматическое логирование моделей (booted)
В каждой модели (Layer 1) добавляем:
PHPprotected static function booted(): void
{
    static::created(function ($model) {
        app(AuditService::class)->logModelEvent('created', $model, [], $model->toArray());
    });

    static::updated(function ($model) {
        app(AuditService::class)->logModelEvent('updated', $model, $model->getOriginal(), $model->getChanges());
    });

    static::deleted(function ($model) {
        app(AuditService::class)->logModelEvent('deleted', $model, $model->toArray(), []);
    });
}

5. Интеграция с другими модулями

Wallet / Payment — log('payment_init', PaymentTransaction::class, $payment->id)
AI-конструкторы — log('ai_constructor_used', AIConstruction::class, $result->id)
Cart / Order — log('cart_reserved', Cart::class, $cart->id)
Delivery — log('delivery_assigned', DeliveryOrder::class, $order->id)
Fraud — log('fraud_attempt', FraudAttempt::class, $attempt->id)
SecurityMonitoringService — автоматически вызывает audit при Critical-событиях


6. Чек-лист реализации (обязателен)

 Таблица audit_logs + ClickHouse архив
 AuditService + AuditLogJob
 Автоматическое логирование в booted() моделей
 Асинхронная запись через Queue
 Интеграция со всеми мутациями (Wallet, AI, Cart, Delivery и т.д.)
 Тесты: логирование create/update/delete, проверка correlation_id, async job
 
 ###
 Железные правила (нарушение = reject PR):

Все операции с персоналом, зарплатами и инвентаризацией — только через соответствующие сервисы.
Зарплаты и выплаты персоналу идут только через WalletService (debit/credit).
Инвентаризация (stocktaking) — обязательная процедура с InventoryAuditService.
HR-данные хранятся в отдельной таблице employees с жёстким tenant-scoping.
Все действия логируются через AuditService + correlation_id.
B2C/B2B различаются в оплате персонала (B2B — отдельные филиалы и контракты).
Fraud-check обязателен перед любой выплатой зарплаты.


1. Модели и миграции
Employee (персонал)
PHPSchema::create('employees', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('user_id')->nullable()->constrained(); // связь с user если есть аккаунт
    $table->string('full_name');
    $table->string('position'); // courier, master, manager, admin и т.д.
    $table->enum('employment_type', ['full_time', 'part_time', 'contract', 'freelance']);
    $table->decimal('base_salary', 14, 2)->default(0);
    $table->json('additional_payments')->nullable(); // бонусы, KPI
    $table->date('hire_date');
    $table->date('termination_date')->nullable();
    $table->boolean('is_active')->default(true);
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
InventoryAudit (инвентаризация)
PHPSchema::create('inventory_audits', function (Blueprint $table) {
    $table->id();
    $table->foreignId('warehouse_id')->constrained();
    $table->foreignId('employee_id')->constrained(); // кто проводил
    $table->enum('status', ['planned', 'in_progress', 'completed', 'discrepancy']);
    $table->json('discrepancies')->nullable();
    $table->text('comments')->nullable();
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
Payroll (зарплатные ведомости)
PHPSchema::create('payrolls', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained();
    $table->foreignId('employee_id')->constrained();
    $table->date('period_start');
    $table->date('period_end');
    $table->decimal('base_salary', 14, 2);
    $table->decimal('bonuses', 14, 2)->default(0);
    $table->decimal('deductions', 14, 2)->default(0);
    $table->decimal('total', 14, 2);
    $table->enum('status', ['draft', 'approved', 'paid', 'cancelled']);
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});

2. Основные сервисы
EmployeeService (персонал и HR)
PHPfinal readonly class EmployeeService
{
    public function hire(CreateEmployeeDto $dto): Employee
    {
        $this->fraud->check($dto);

        return DB::transaction(function () use ($dto) {
            $employee = Employee::create($dto->toArray());

            // Создаём wallet для сотрудника (для выплат)
            WalletService::createForEmployee($employee->id);

            $this->audit->log('employee_hired', Employee::class, $employee->id);

            return $employee;
        });
    }

    public function calculateSalary(Employee $employee, Carbon $periodStart, Carbon $periodEnd): Payroll
    {
        // базовая + KPI + бонусы за доставки / AI-использование
        $base = $employee->base_salary;
        $bonuses = $this->calculatePerformanceBonuses($employee, $periodStart, $periodEnd);

        return Payroll::create([
            'employee_id' => $employee->id,
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'base_salary' => $base,
            'bonuses' => $bonuses,
            'total' => $base + $bonuses,
            'status' => 'draft',
        ]);
    }
}
InventoryAuditService (инвентаризация)
PHPfinal readonly class InventoryAuditService
{
    public function startAudit(int $warehouseId, int $employeeId): InventoryAudit
    {
        return DB::transaction(function () use ($warehouseId, $employeeId) {
            $audit = InventoryAudit::create([
                'warehouse_id' => $warehouseId,
                'employee_id' => $employeeId,
                'status' => 'in_progress',
                'correlation_id' => Str::uuid(),
            ]);

            // Запускаем задание по сверке
            InventoryAuditJob::dispatch($audit->id);

            return $audit;
        });
    }
}
PayrollService (зарплаты)
PHPfinal readonly class PayrollService
{
    public function pay(Payroll $payroll): void
    {
        DB::transaction(function () use ($payroll) {
            // Списание с tenant wallet
            WalletService::debit(
                tenant()->wallet_id,
                $payroll->total,
                'payroll',
                $payroll->correlation_id
            );

            // Начисление сотруднику
            WalletService::credit(
                $payroll->employee->wallet_id,
                $payroll->total,
                'salary',
                $payroll->correlation_id
            );

            $payroll->update(['status' => 'paid']);

            $this->audit->log('payroll_paid', Payroll::class, $payroll->id);
        });
    }
}

3. Технические задачи (дополнительные модули)
ShiftSchedulingService — график смен курьеров и мастеров
TimeTrackingService — учёт рабочего времени (GPS + ручной ввод)
PerformanceService — KPI и бонусы (кол-во доставок, рейтинг, AI-использование)
LeaveManagementService — отпуска, больничные
Интеграция:

Все выплаты идут через WalletService
График смен влияет на доступность курьеров в GeotrackingService
KPI влияет на бонусы в BonusService


4. Чек-лист реализации (обязателен)

 Модели Employee, Payroll, InventoryAudit
 EmployeeService, PayrollService, InventoryAuditService
 Интеграция выплат через WalletService
 Реал-тайм обновление статуса сотрудника (online/offline)
 Audit-логирование всех HR-действий
 Тесты: начисление зарплаты, инвентаризация, KPI-бонусы
 
 ###
 Железные правила (нарушение = reject PR):

Личный кабинет бизнеса = Tenant Panel (/tenant) + B2B Panel (/b2b).
Один Tenant может иметь несколько филиалов (BusinessGroup) по разным ИНН.
Переключение между филиалами происходит в сессии (active_business_group_id).
Данные строго изолированы по tenant_id + business_group_id.
Все действия в кабинетах логируются через AuditService с correlation_id.
Fraud-check обязателен перед любыми финансовыми операциями в кабинете.
B2B-кабинет имеет расширенный функционал (кредит, отсрочка, API-ключи, массовые заказы).


1. Модели и миграции
BusinessGroup (филиал / юр.лицо)
PHPSchema::create('business_groups', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
    $table->string('legal_name');
    $table->string('inn')->unique();
    $table->string('kpp')->nullable();
    $table->string('legal_address');
    $table->string('bank_account');
    $table->enum('b2b_tier', ['standard', 'silver', 'gold', 'platinum'])->default('standard');
    $table->decimal('credit_limit', 14, 2)->default(0);
    $table->decimal('credit_used', 14, 2)->default(0);
    $table->integer('payment_term_days')->default(14);
    $table->json('b2b_data')->nullable();           // налоговые реквизиты, договоры
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
User (расширенные поля для бизнеса)
PHP// В таблице users добавляем:
$table->foreignId('active_business_group_id')->nullable()->constrained('business_groups');
$table->boolean('is_b2b_owner')->default(false);

2. Личные кабинеты бизнеса
1. Tenant Panel (/tenant) — основной кабинет владельца бизнеса

Dashboard с метриками (GMV, заказы, AI-использование, склады)
AI-конструкторы для всех вертикалей
Управление складами и инвентарём
Wallet + выплаты
Маркетинг и реклама
Персонал и HR
Филиалы (Business Groups)
Отчёты и аналитика

2. B2B Panel (/b2b) — кабинет для юридических лиц / филиалов

Оптовые цены и MOQ
Кредитный лимит и отсрочка платежа
Массовые заказы и импорт Excel
API-доступ (отдельные ключи)
Расширенные отчёты по обороту
Переключение между своими филиалами

Переключение филиала (в сессии):
PHP// Middleware SwitchBusinessGroup
public function handle(Request $request, Closure $next)
{
    if ($request->has('switch_business_group')) {
        $group = BusinessGroup::where('id', $request->switch_business_group)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        session(['active_business_group_id' => $group->id]);
    }

    return $next($request);
}

3. BusinessGroupService (главный сервис филиалов)
PHPfinal readonly class BusinessGroupService
{
    public function create(CreateBusinessGroupDto $dto): BusinessGroup
    {
        $this->fraud->check($dto);

        return DB::transaction(function () use ($dto) {
            $group = BusinessGroup::create($dto->toArray());

            // Автоматическое копирование настроек от головного tenant
            $this->copySettingsFromTenant($group);

            // Создаём отдельный wallet для филиала
            WalletService::createForBusinessGroup($group->id);

            $this->audit->log('business_group_created', BusinessGroup::class, $group->id);

            return $group;
        });
    }

    public function switchToGroup(int $groupId): void
    {
        $group = BusinessGroup::where('id', $groupId)
            ->where('tenant_id', tenant()->id)
            ->firstOrFail();

        session(['active_business_group_id' => $group->id]);

        Log::channel('audit')->info('Switched to business group', [
            'group_id' => $groupId,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);
    }
}

4. Особенности кабинетов
Tenant Panel:

Видит все филиалы
Управляет настройками платформы
Может создавать/удалять филиалы

B2B Panel (филиал):

Видит только свои данные
Работает с оптовыми ценами
Имеет кредитный лимит
Может создавать массовые заказы

User Cabinet (физлицо / B2C):

Переключение в B2B-режим (если есть ИНН)
Просмотр своих заказов и дизайнов


5. Чек-лист реализации (обязателен)

 Модель BusinessGroup + миграция
 BusinessGroupService с созданием и переключением
 Tenant Panel + B2B Panel в Filament
 Изоляция данных по business_group_id
 Переключение филиалов в сессии
 Audit-логирование всех действий в кабинетах
 Интеграция Wallet, AI, маркетинга и доставки для B2B
 
 ###
 Железные правила (нарушение = reject PR):

B2B API доступен только юридическим лицам / ИП (наличие inn + business_card_id).
Авторизация — только через API Key (не Sanctum token обычного пользователя).
Все запросы к B2B API проходят middleware b2b.api + tenant + rate-limit + fraud-check.
B2B API имеет отдельный namespace (/api/b2b/v1/).
Оптовые цены, MOQ, кредит, отсрочка — обязательны.
Массовые операции (импорт заказов, выгрузка отчётов) — через отдельные endpoints.
Все действия логируются через AuditService с correlation_id.
Rate limit для B2B выше, чем для B2C, но всё равно жёсткий.


1. Авторизация B2B API
API Key модель:
PHPSchema::create('b2b_api_keys', function (Blueprint $table) {
    $table->id();
    $table->foreignId('business_group_id')->constrained()->onDelete('cascade');
    $table->string('name');                    // "Интеграция с 1С", "Мобильное приложение" и т.д.
    $table->string('key')->unique();           // префикс b2b_ + random 64 символа
    $table->string('hashed_key');              // SHA256
    $table->timestamp('expires_at')->nullable();
    $table->json('permissions')->nullable();   // ['orders.read', 'orders.write', 'reports', 'stock']
    $table->string('correlation_id')->nullable()->index();
    $table->timestamps();
});
B2BApiKeyService:
PHPfinal readonly class B2BApiKeyService
{
    public function validate(string $key): BusinessGroup
    {
        $hashed = hash('sha256', $key);

        $apiKey = B2BApiKey::where('hashed_key', $hashed)
            ->where('expires_at', '>', now())
            ->firstOrFail();

        return $apiKey->businessGroup;
    }
}
Middleware b2b.api:
PHPfinal class B2BApiMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $key = $request->header('X-B2B-API-Key');

        if (!$key) {
            return response()->json(['error' => 'B2B API Key required'], 401);
        }

        $businessGroup = app(B2BApiKeyService::class)->validate($key);

        $request->merge([
            'is_b2b' => true,
            'business_group_id' => $businessGroup->id,
        ]);

        return $next($request);
    }
}

2. Routes B2B API
PHP// routes/api/b2b.php

Route::prefix('b2b/v1')
    ->middleware(['b2b.api', 'tenant', 'rate-limit:500,1', 'fraud-check'])
    ->group(function () {

        Route::apiResource('products', B2BProductController::class);
        Route::apiResource('orders', B2BOrderController::class);
        Route::get('stock', [B2BStockController::class, 'index']);

        // Массовые операции
        Route::post('orders/import', [B2BOrderController::class, 'importExcel']);
        Route::post('orders/bulk', [B2BOrderController::class, 'bulkCreate']);

        // Отчёты
        Route::get('reports/turnover', [B2BReportController::class, 'turnover']);
        Route::get('reports/credit', [B2BReportController::class, 'creditLimit']);

        // API для 1С и внешних систем
        Route::get('sync/stock', [B2BSyncController::class, 'stock']);
    });

3. Примеры контроллеров B2B
B2BOrderController:
PHPfinal readonly class B2BOrderController extends Controller
{
    public function __construct(
        private B2BOrderService $service,
    ) {}

    public function store(B2BCreateOrderRequest $request): JsonResponse
    {
        $dto = B2BOrderDto::from($request);
        $dto->businessGroupId = $request->get('business_group_id');

        $order = $this->service->create($dto);

        return $this->successResponse($order, $request->correlationId());
    }

    public function bulkCreate(B2BBulkOrderRequest $request): JsonResponse
    {
        // Массовое создание заказов
    }
}
B2BStockController — только чтение остатков по оптовым ценам.

4. B2BOrderService (основная бизнес-логика)
PHPfinal readonly class B2BOrderService
{
    public function create(B2BOrderDto $dto): Order
    {
        $this->fraud->check($dto);

        return DB::transaction(function () use ($dto) {
            // Проверка кредитного лимита
            $this->checkCreditLimit($dto->businessGroupId, $dto->total);

            $order = Order::create($dto->toArray());

            // Резерв товаров (долгосрочный для B2B)
            $this->inventory->reserveForB2B($order);

            $this->audit->log('b2b_order_created', Order::class, $order->id);

            return $order;
        });
    }
}

5. Отличия B2B API от обычного B2C API
ПараметрB2C APIB2B APIАвторизацияSanctum tokenX-B2B-API-KeyЦеныРозничныеОптовые + tierМинимальный заказ1 штMOQ (minimum order quantity)ОплатаПолная предоплатаАванс + отсрочка до 30 днейКредитНетДа (credit_limit)Rate limit100 req/min500 req/minМассовые операцииНетДа (bulk, import)ОтчётыОграниченныеПолные (оборот, кредит)

6. Чек-лист реализации B2B API (обязателен)

 Модель B2BApiKey + миграция
 B2BApiMiddleware + B2BApiKeyService
 Отдельный namespace /api/b2b/v1/
 B2BOrderService, B2BProductService и т.д.
 Проверка кредитного лимита и MOQ
 Audit + Fraud-check на всех endpoints
 Тесты: авторизация по ключу, bulk-заказ, credit check
 
 ###
 Общее правило:
Каждая продуктовая вертикаль обязана жить в app/Domains/{VerticalName}/ и строго следовать 9-слойной архитектуре.
Для каждой вертикали обязателен свой AI-конструктор, свои модели, сервисы, AI-калькуляторы и интеграции с Wallet, Inventory, Delivery, Fraud и B2C/B2B.

1. Общие требования ко всем продуктовым вертикалям

Папка:app/Domains/{VerticalName}/
9-слойная архитектура (обязательно)
AI-конструктор в Services/AI/{Vertical}ConstructorService.php
UserTasteProfile интеграция
B2C/B2B различия в ценах, MOQ, доставке
Инвентарь и резервы через InventoryService
Доставка и геотрекинг
Audit + correlation_id + FraudControlService::check()


2. Детальное описание ключевых вертикалей
1. BEAUTY (Салоны красоты, косметика, услуги)

AI-конструктор: BeautyImageConstructorService (анализ лица, AR-примерка)
Модели: Salon, Master, Service, Appointment
Особенности: запись к мастеру, виртуальная примерка, бронирование слотов
B2B: закупка косметики оптом

2. FURNITURE & INTERIOR (Мебель и интерьер)

AI-конструктор: InteriorDesignConstructorService (анализ комнаты, 3D-визуализация)
Модели: FurnitureItem, RoomDesign, Order
Особенности: 3D-тур, расчёт стоимости ремонта, доставка крупногабаритных товаров
B2B: оптовые партии мебели для отелей и офисов

3. FOOD (Еда, рестораны, доставка продуктов)

AI-конструктор: MenuConstructorService (рецепты по ингредиентам, КБЖУ)
Модели: Dish, Restaurant, Ingredient, Order
Особенности: выбор диеты, аллергены, доставка готовых блюд или ингредиентов
B2B: поставки для ресторанов и корпоративного питания

4. FASHION / COSMETICS (Одежда и косметика)

AI-конструктор: FashionStyleConstructorService (цветотип, капсульный гардероб, AR-примерка)
Модели: Product, Size, Color, Brand
Особенности: виртуальная примерка одежды, подбор по стилю
B2B: закупка коллекций для магазинов

5. REAL ESTATE (Недвижимость)

AI-конструктор: RealEstateDesignConstructorService (виртуальный ремонт, 3D-тур по квартире)
Модели: Property, Listing, Rental
Особенности: 3D-тур, расчёт стоимости ремонта, прогноз арендной ставки
B2B: работа с агентствами и застройщиками

6. AUTO (Автомобили, запчасти, сервисы)

AI-конструктор: AutoTuningConstructorService (визуализация тюнинга, дефектовка по фото)
Модели: CarPart, Service, Booking
Особенности: запись на СТО, подбор запчастей, оценка повреждений
B2B: поставки запчастей для автосервисов

(Аналогично строятся остальные вертикали: Medical, Hotels, Education, Sports и т.д.)

3. Обязательная структура каждой вертикали
textapp/Domains/{Vertical}/
├── Models/                  (Product, Service, Booking и т.д.)
├── DTOs/                    (Create..., Update..., Search...)
├── Services/
│   ├── {Vertical}Service.php
│   └── AI/{Vertical}ConstructorService.php
├── Requests/
├── Resources/
├── Events/
├── Listeners/
├── Jobs/
└── Filament/
    ├── Resources/
    └── Pages/
AI-конструктор (обязательно):
PHPfinal readonly class {Vertical}ConstructorService
{
    public function __construct(
        private OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
    ) {}

    public function analyzeAndRecommend(UploadedFile $file, int $userId): AIConstructionResult
    {
        // Vision API → анализ → персонализация по UserTasteProfile → рекомендации товаров
        // Возврат JSON + AR/3D ссылки + цены + наличие
    }
}

4. Чек-лист добавления новой вертикали

 Папка app/Domains/{NewVertical}/ + 9 слоёв
 AI-конструктор в Services/AI/
 Модели с tenant-scoping
 Интеграция с Inventory, Delivery, Wallet
 B2C/B2B различия в сервисе
 Запись в config/verticals.php
 Тесты и Filament-ресурсы
 
 ### Особенности вертикали Beauty:

Основные сущности: салоны, мастера, услуги, записи (appointments)
Обязательный AI-конструктор образа (анализ лица + AR-примерка)
Интеграция с UserTasteProfile (цветотип, стиль, предпочтения)
Запись к мастеру в реальном времени с проверкой слотов
B2C: розничные услуги и косметика
B2B: оптовая закупка косметики и корпоративные услуги

Папка: app/Domains/Beauty/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models
PHP// Salon.php
final class Salon extends Model { ... }

// Master.php
final class Master extends Model {
    protected $table = 'beauty_masters';
    protected $fillable = ['salon_id', 'full_name', 'specialization', 'rating', 'is_active'];
}

// Service.php
final class Service extends Model { ... }

// Appointment.php
final class Appointment extends Model { ... }
Layer 2: DTOs
PHPfinal readonly class CreateAppointmentDto { ... }
final readonly class AnalyzeFaceDto { ... }
Layer 3: Services

BeautyService — основная бизнес-логика
BeautyAppointmentService — запись и слоты
BeautyImageConstructorService — AI-конструктор

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament (полностью по общему шаблону).

2. AI-конструктор Beauty (BeautyImageConstructorService)
PHPfinal readonly class BeautyImageConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
    ) {}

    public function analyzePhotoAndRecommend(UploadedFile $photo, int $userId): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'beauty'));

        $analysis = $this->openai->vision()->analyze([
            'image' => $photo->getRealPath(),
            'prompt' => "Анализ лица для салона красоты. Определи: тип лица, тон кожи, цвет волос, форму бровей, возраст, состояние кожи. Рекомендуй причёски, окраски, макияж, уход.",
        ]);

        $styleProfile = $this->parseAnalysis($analysis);

        // Персонализация по вкусам пользователя
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $styleProfile = array_merge($styleProfile, $taste->beauty_preferences ?? []);

        $recommendations = $this->recommendation->getForBeauty($styleProfile, $userId);

        // Проверка наличия товаров/услуг
        foreach ($recommendations as &$item) {
            $item['in_stock'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
        }

        // Сохранение в профиль
        $this->saveToUserProfile($userId, $styleProfile);

        Log::channel('audit')->info('Beauty AI constructor used', [
            'user_id' => $userId,
            'style_profile' => $styleProfile,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'beauty',
            type: 'face_analysis',
            payload: $styleProfile,
            suggestions: $recommendations,
            confidence_score: $analysis['confidence'] ?? 0.95,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }

    private function parseAnalysis(array $analysis): array { ... }
}
AR-примерка — ссылка на внешний сервис (AR.js / Ready Player Me).

3. Ключевые модели Beauty
Salon — салоны
Master — мастера (с расписанием и специализацией)
Service — услуги (стрижка, окрашивание, маникюр и т.д.)
Appointment — запись (с проверкой слотов в реальном времени)
Особенности записи:

Проверка свободных слотов мастера
Автоматический резерв времени (20 минут)
Уведомление мастеру и клиенту
Отмена с штрафом (если меньше 2 часов)


4. B2C vs B2B в Beauty

ПараметрB2CB2BЦеныРозничныеОптовые на косметику + корпоративные услугиМинимальный заказ1 услугаMOQ для косметикиОплатаПолная предоплатаОтсрочка 7–14 днейAI-конструкторДля конечного клиентаДля закупки коллекций и обучения мастеровДоступПубличный маркетплейсПриватная витрина + API

5. Интеграции вертикали Beauty

Inventory — наличие услуг и косметики
Wallet — оплата услуг и выплата мастерам
Delivery — доставка косметики
AI — конструктор + рекомендации
ML — UserTasteProfile (цветотип, предпочтения)
Fraud — проверка перед записью и оплатой
Tenant Panel — управление салонами и мастерами
User Cabinet — запись, AR-примерка, «Мой стиль»


6. Чек-лист реализации вертикали Beauty (обязателен)

 Папка app/Domains/Beauty/ + 9 слоёв
 BeautyImageConstructorService (полный)
 Модели Salon, Master, Service, Appointment
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в ценах и условиях
 Реал-тайм проверка слотов
 Тесты: AI-анализ, запись, AR-примерка
 
### Особенности вертикали Fashion:

Главный акцент — персонализация стиля через AI.
Обязательный AI-конструктор стиля с виртуальной примеркой (AR).
Капсульный гардероб, подбор образов под событие, сезон, цветотип.
Интеграция с UserTasteProfile (любимые цвета, размеры, бренды, стиль).
B2C: розничная продажа и персональные рекомендации.
B2B: оптовые закупки коллекций, закупки для магазинов, корпоративный стиль.

Папка: app/Domains/Fashion/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

Product — одежда, обувь, аксессуары
Brand
Size
Color
Collection
Outfit — готовый образ
Capsule — капсульный гардероб пользователя

Layer 2: DTOs

CreateOutfitDto
AnalyzeStyleDto
VirtualTryOnDto

Layer 3: Services

FashionService — основная бизнес-логика
FashionStyleConstructorService — главный AI-конструктор
CapsuleService — капсульные гардеробы
VirtualTryOnService — AR-примерка

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Fashion (FashionStyleConstructorService)
PHPfinal readonly class FashionStyleConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private VirtualTryOnService $virtualTryOn,
    ) {}

    public function analyzeAndRecommend(UploadedFile $photo, int $userId, ?string $eventType = null): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'fashion'));

        // 1. Анализ фото (Vision API)
        $analysis = $this->openai->vision()->analyze([
            'image' => $photo->getRealPath(),
            'prompt' => "Анализ внешности и фигуры для подбора одежды. Определи: тип фигуры, цветотип, контрастность, рост, предпочтительный стиль. Рекомендуй цвета, фасоны, бренды.",
        ]);

        $styleProfile = $this->parseStyleAnalysis($analysis);

        // 2. Персонализация по вкусам пользователя
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $styleProfile = array_merge($styleProfile, $taste->fashion_preferences ?? []);

        // 3. Учёт события (если указано)
        if ($eventType) {
            $styleProfile['event'] = $eventType; // свадьба, офис, вечер и т.д.
        }

        // 4. Генерация рекомендаций
        $recommendations = $this->recommendation->getFashionRecommendations($styleProfile, $userId);

        // 5. Проверка наличия и виртуальная примерка
        foreach ($recommendations as &$item) {
            $item['in_stock'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
            $item['ar_try_on_url'] = $this->virtualTryOn->generateUrl($item['product_id'], $userId);
        }

        // 6. Формирование капсульного гардероба
        $capsule = $this->capsuleService->generateCapsule($recommendations, $styleProfile);

        // 7. Сохранение в профиль пользователя
        $this->saveToUserProfile($userId, $styleProfile, $capsule);

        Log::channel('audit')->info('Fashion AI constructor used', [
            'user_id' => $userId,
            'style_profile' => $styleProfile,
            'capsule_items' => count($capsule),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'fashion',
            type: 'style_analysis',
            payload: [
                'style_profile' => $styleProfile,
                'capsule' => $capsule,
            ],
            suggestions: $recommendations,
            confidence_score: $analysis['confidence'] ?? 0.92,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}
VirtualTryOnService — генерирует AR-ссылку для примерки (Ready Player Me или AR.js).

3. Ключевые модели Fashion

Product — товар (одежда, обувь, аксессуары)
Brand
Size (с таблицей соответствий)
Color (с цветотипами)
Outfit — готовый образ
Capsule — капсульный гардероб пользователя (сохраняется в user_capsules)


4. B2C vs B2B в Fashion
ПараметрB2CB2BЦеныРозничныеОптовые + сезонные коллекцииМинимальный заказ1 штMOQ (минимум 10–50 шт)AI-конструкторДля конечного клиентаДля закупки коллекций и стилистовВиртуальная примеркаПолная ARТолько для демонстрацииОплатаПолная предоплатаОтсрочка 14–30 днейДоступПубличный маркетплейсПриватная B2B-витрина + API

5. Интеграции вертикали Fashion

Inventory — реальное наличие и размеры
Wallet — оплата покупок и выплаты брендам
Delivery — доставка одежды и обуви
AI — стиль-конструктор + капсульный гардероб
ML — UserTasteProfile (цвета, размеры, бренды, стиль)
Fraud — проверка перед крупными покупками
Tenant Panel — управление каталогом и коллекциями
User Cabinet — «Мой стиль», сохранённые капсулы, AR-примерка


6. Чек-лист реализации вертикали Fashion (обязателен)

 Папка app/Domains/Fashion/ + 9 слоёв
 FashionStyleConstructorService (полный, с AR)
 Модели Product, Brand, Size, Color, Outfit, Capsule
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в ценах и условиях
 Реал-тайм AR-примерка
 Тесты: AI-анализ, генерация капсулы, виртуальная примерка
 
 ### Особенности вертикали Fitness:

Главный акцент — персонализация тренировок и питания через AI.
Обязательный AI-конструктор фитнеса (анализ тела, цели, план тренировок, план питания).
AR/VR-элементы: проверка техники упражнений, виртуальный тренер.
Интеграция с UserTasteProfile (цели: похудение, набор массы, выносливость, реабилитация).
B2C: розничные абонементы, персональные тренировки, спортивное питание.
B2B: корпоративные программы, оптовые поставки питания, франшизы залов.

Папка: app/Domains/Fitness/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

Gym — фитнес-клубы и залы
Trainer — тренеры и инструкторы
WorkoutPlan — план тренировок
NutritionPlan — план питания
Membership — абонементы
Supplement — спортивное питание и добавки
Session — запись на тренировку / занятие

Layer 2: DTOs

CreateWorkoutPlanDto
AnalyzeBodyDto
GenerateNutritionPlanDto

Layer 3: Services

FitnessService — основная бизнес-логика
FitnessConstructorService — главный AI-конструктор
WorkoutPlanService
NutritionPlanService
SessionBookingService

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Fitness (FitnessConstructorService)
PHPfinal readonly class FitnessConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private VirtualTrainerService $virtualTrainer,
    ) {}

    public function analyzeBodyAndGeneratePlan(UploadedFile $photo, int $userId, array $goals): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'fitness'));

        // 1. Анализ тела (Vision API)
        $analysis = $this->openai->vision()->analyze([
            'image' => $photo->getRealPath(),
            'prompt' => "Анализ тела для фитнеса. Определи: тип телосложения, процент жира, мышечную массу, осанку, цели пользователя. Рекомендуй план тренировок и питания.",
        ]);

        $bodyProfile = $this->parseBodyAnalysis($analysis);

        // 2. Персонализация по вкусам и целям
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $fullProfile = array_merge($bodyProfile, $taste->fitness_preferences ?? [], $goals);

        // 3. Генерация плана тренировок и питания
        $workoutPlan = $this->generateWorkoutPlan($fullProfile);
        $nutritionPlan = $this->generateNutritionPlan($fullProfile);

        // 4. Рекомендации товаров (спортивное питание, одежда, тренажёры)
        $recommendations = $this->recommendation->getFitnessRecommendations($fullProfile, $userId);

        // 5. Проверка наличия и виртуальная демонстрация
        foreach ($recommendations as &$item) {
            $item['in_stock'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
            $item['ar_demo_url'] = $this->virtualTrainer->generateExerciseDemo($item['product_id'], $userId);
        }

        // 6. Сохранение планов в профиль пользователя
        $this->savePlansToUserProfile($userId, $workoutPlan, $nutritionPlan);

        Log::channel('audit')->info('Fitness AI constructor used', [
            'user_id' => $userId,
            'goals' => $goals,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'fitness',
            type: 'body_analysis',
            payload: [
                'body_profile' => $bodyProfile,
                'workout_plan' => $workoutPlan,
                'nutrition_plan' => $nutritionPlan,
            ],
            suggestions: $recommendations,
            confidence_score: $analysis['confidence'] ?? 0.90,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}
VirtualTrainerService — AR/VR-демонстрация упражнений.

3. Ключевые модели Fitness

Gym — фитнес-клубы
Trainer — тренеры
WorkoutPlan — планы тренировок
NutritionPlan — планы питания
Membership — абонементы
Supplement — спортивное питание
Session — запись на тренировку


4. B2C vs B2B в Fitness

ПараметрB2CB2BЦеныРозничные абонементыКорпоративные пакеты и франшизыAI-конструкторПерсональный планКорпоративные программы и обучениеМинимальный заказ1 тренировка / добавкаMOQ для питания и оборудованияОплатаПолная предоплатаОтсрочка 14–30 днейДоступПубличный маркетплейсПриватная B2B-витрина + API

5. Интеграции вертикали Fitness

Inventory — наличие абонементов, добавок, оборудования
Wallet — оплата тренировок и абонементов
Delivery — доставка спортивного питания
AI — конструктор + виртуальный тренер
ML — UserTasteProfile (цели, предпочтения)
Fraud — проверка перед покупкой дорогих абонементов
Tenant Panel — управление залами и тренерами
User Cabinet — личный план, AR-тренировки, прогресс


6. Чек-лист реализации вертикали Fitness (обязателен)

 Папка app/Domains/Fitness/ + 9 слоёв
 FitnessConstructorService (полный, с AR)
 Модели Gym, Trainer, WorkoutPlan, NutritionPlan, Membership
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в ценах и условиях
 Реал-тайм запись на тренировки
 Тесты: AI-анализ тела, генерация планов, AR-демо
 
 ### Особенности вертикали Hotel:

Главный акцент — персонализация проживания через AI (виртуальный тур по номеру, подбор по предпочтениям, 3D-визуализация).
Обязательный AI-конструктор отеля (анализ предпочтений + виртуальный консьерж).
Реал-тайм бронирование с проверкой доступности номеров.
Интеграция с UserTasteProfile (предпочтения по типу номера, виду из окна, услугам).
B2C: розничное бронирование для туристов и путешественников.
B2B: корпоративное бронирование, долгосрочная аренда, работа с туроператорами и франшизами.

Папка: app/Domains/Hotel/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

Hotel — отели и гостиницы
Room — номера и апартаменты
RoomType — типы номеров
Booking — бронирования
Service — дополнительные услуги (SPA, трансфер, питание)
Review — отзывы

Layer 2: DTOs

CreateBookingDto
AnalyzePreferencesDto
VirtualTourDto

Layer 3: Services

HotelService — основная бизнес-логика
HotelConstructorService — главный AI-конструктор
BookingService — бронирование и слоты
VirtualTourService — 3D-туры

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Hotel (HotelConstructorService)
PHPfinal readonly class HotelConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private VirtualTourService $virtualTour,
    ) {}

    public function analyzePreferencesAndRecommend(array $preferences, int $userId): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'hotel'));

        // 1. Анализ предпочтений + UserTasteProfile
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $fullProfile = array_merge($preferences, $taste->hotel_preferences ?? []);

        // 2. Генерация рекомендаций отелей и номеров
        $recommendations = $this->recommendation->getHotelRecommendations($fullProfile, $userId);

        // 3. Проверка реального наличия и цен
        foreach ($recommendations as &$item) {
            $item['available_rooms'] = $this->inventory->getAvailableStock($item['room_id']);
            $item['virtual_tour_url'] = $this->virtualTour->generateTour($item['room_id'], $userId);
        }

        // 4. Формирование персонального пакета (номер + услуги)
        $package = $this->buildPersonalPackage($recommendations, $fullProfile);

        // 5. Сохранение в профиль пользователя
        $this->saveToUserProfile($userId, $fullProfile, $package);

        Log::channel('audit')->info('Hotel AI constructor used', [
            'user_id' => $userId,
            'preferences' => $preferences,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'hotel',
            type: 'preference_analysis',
            payload: [
                'profile' => $fullProfile,
                'package' => $package,
            ],
            suggestions: $recommendations,
            confidence_score: 0.93,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}
VirtualTourService — генерирует 3D-тур по номеру (Matterport / 3D модель).

3. Ключевые модели Hotel

Hotel — гостиница
Room — номер
RoomType — типы номеров
Booking — бронирование
Service — дополнительные услуги (завтрак, SPA, трансфер)
Review — отзывы гостей

Особенности бронирования:

Реал-тайм проверка доступности номеров
Автоматический резерв на 15 минут
Уведомление отелю и гостю
Отмена с штрафом (в зависимости от срока)


4. B2C vs B2B в Hotel

ПараметрB2CB2BЦеныРозничныеКорпоративные + долгосрочные тарифыМинимальный заказ1 ночьМинимум 10 ночей / месяцAI-конструкторПерсональный подбор номераКорпоративные пакеты и переговорные комнатыОплатаПолная предоплатаОтсрочка 14–30 днейДоступПубличный маркетплейсПриватная B2B-витрина + API

5. Интеграции вертикали Hotel

Inventory — реальное наличие номеров
Wallet — оплата бронирования и выплаты отелям
Delivery — доставка в номер (еда, вещи)
AI — конструктор + виртуальный тур
ML — UserTasteProfile (предпочтения по виду, удобствам, бюджету)
Fraud — проверка перед дорогим бронированием
Tenant Panel — управление отелями и номерами
User Cabinet — бронирование, виртуальный тур, история поездок


6. Чек-лист реализации вертикали Hotel (обязателен)

 Папка app/Domains/Hotel/ + 9 слоёв
 HotelConstructorService (полный, с 3D-туром)
 Модели Hotel, Room, Booking, Service
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в тарифах и условиях
 Реал-тайм проверка доступности номеров
 Тесты: AI-анализ предпочтений, бронирование, виртуальный тур
 
 ### Особенности вертикали Travel:

Главный акцент — персонализированный планировщик путешествий через AI.
Обязательный AI-конструктор путешествий (анализ предпочтений + генерация полного itinerary).
Виртуальные туры, 3D-просмотр отелей, реал-тайм бронирование билетов и отелей.
Интеграция с UserTasteProfile (предпочтения по типу отдыха, бюджету, транспорту, кухне, активности).
B2C: индивидуальные путешествия для туристов.
B2B: корпоративные поездки, работа с туроператорами, оптовые закупки билетов и номеров.

Папка: app/Domains/Travel/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

Trip — поездка / тур
Itinerary — маршрут / план путешествия
Ticket — билеты (авиа, ж/д, автобус)
HotelBooking — бронирование отелей
CarRental — аренда авто
Excursion — экскурсии и активности
Insurance — страховки

Layer 2: DTOs

CreateItineraryDto
AnalyzeTravelPreferencesDto
BookTicketDto

Layer 3: Services

TravelService — основная бизнес-логика
TravelConstructorService — главный AI-конструктор
ItineraryService
BookingService

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Travel (TravelConstructorService)
PHPfinal readonly class TravelConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private VirtualTourService $virtualTour,
    ) {}

    public function generatePersonalizedTrip(array $preferences, int $userId): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'travel'));

        // 1. Анализ предпочтений + UserTasteProfile
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $fullProfile = array_merge($preferences, $taste->travel_preferences ?? []);

        // 2. Генерация полного itinerary
        $itinerary = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты — профессиональный travel-планировщик. Создай полный персонализированный маршрут.'],
                ['role' => 'user', 'content' => json_encode($fullProfile)],
            ],
        ]);

        $parsedItinerary = $this->parseItinerary($itinerary);

        // 3. Рекомендации реальных предложений
        $recommendations = $this->recommendation->getTravelRecommendations($fullProfile, $userId);

        // 4. Проверка наличия и виртуальные туры
        foreach ($recommendations as &$item) {
            $item['available'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
            $item['virtual_tour_url'] = $this->virtualTour->generateTour($item['product_id'], $userId);
        }

        // 5. Сохранение в профиль пользователя
        $this->saveToUserProfile($userId, $fullProfile, $parsedItinerary);

        Log::channel('audit')->info('Travel AI constructor used', [
            'user_id' => $userId,
            'preferences' => $preferences,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'travel',
            type: 'trip_planner',
            payload: [
                'profile' => $fullProfile,
                'itinerary' => $parsedItinerary,
            ],
            suggestions: $recommendations,
            confidence_score: 0.94,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}
VirtualTourService — генерирует 3D-туры по отелям и достопримечательностям.

3. Ключевые модели Travel

Trip — поездка
Itinerary — детальный план
Ticket — билеты
HotelBooking — бронирование отелей
CarRental — аренда авто
Excursion — экскурсии
Insurance — страховки

Особенности бронирования:

Реал-тайм проверка доступности
Автоматический резерв на 15 минут
Уведомления о изменениях (задержки рейсов, отмена)
Отмена с штрафом по правилам провайдера


4. B2C vs B2B в Travel

ПараметрB2CB2BЦеныРозничныеКорпоративные + объёмные скидкиAI-конструкторИндивидуальный маршрутКорпоративные поездки и MICEМинимальный заказ1 билет / 1 ночьГрупповые бронированияОплатаПолная предоплатаОтсрочка 14–30 днейДоступПубличный маркетплейсПриватная B2B-витрина + API

5. Интеграции вертикали Travel

Inventory — реальное наличие билетов, номеров, экскурсий
Wallet — оплата бронирований
Delivery — доставка документов / трансфер
AI — конструктор + виртуальные туры
ML — UserTasteProfile (предпочтения по отдыху, бюджету, транспорту)
Fraud — проверка перед крупными бронированиями
Tenant Panel — управление отелями, туроператорами
User Cabinet — «Мои поездки», сохранённые маршруты, история


6. Чек-лист реализации вертикали Travel (обязателен)

 Папка app/Domains/Travel/ + 9 слоёв
 TravelConstructorService (полный, с itinerary)
 Модели Trip, Itinerary, Ticket, HotelBooking
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в тарифах и условиях
 Реал-тайм проверка доступности
 Тесты: генерация маршрута, бронирование, виртуальный тур
 
### Особенности вертикали Food:

Главный акцент — AI-конструктор меню и рецептов (персонализация по диете, КБЖУ, аллергенам, времени приготовления).
Поддержка двух потоков: готовые блюда из ресторанов и ингредиенты для дома.
Интеграция с UserTasteProfile (диетические предпочтения, любимые кухни, цели питания).
B2C: персональные рецепты, доставка еды, заказ ингредиентов.
B2B: оптовые поставки для ресторанов, корпоративное питание, франшизы.

Папка: app/Domains/Food/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

Restaurant — рестораны и кафе
Dish — блюда
Ingredient — ингредиенты
Recipe — рецепты
Order — заказ (готовое блюдо или набор ингредиентов)
Menu — меню ресторана

Layer 2: DTOs

GenerateRecipeDto
CreateOrderDto
AnalyzeIngredientsDto

Layer 3: Services

FoodService — основная бизнес-логика
MenuConstructorService — главный AI-конструктор
RecipeService
OrderService

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Food (MenuConstructorService)
PHPfinal readonly class MenuConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
    ) {}

    public function generateMenuByPreferences(array $ingredients, string $diet, int $minCalories, int $maxCalories, int $userId): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'food'));

        // 1. Анализ ингредиентов + UserTasteProfile
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $fullProfile = [
            'ingredients' => $ingredients,
            'diet' => $diet,
            'calories_min' => $minCalories,
            'calories_max' => $maxCalories,
            'taste_preferences' => $taste->food_preferences ?? [],
        ];

        // 2. Генерация рецептов через AI
        $recipes = $this->openai->chat()->create([
            'model' => 'gpt-4o',
            'messages' => [
                ['role' => 'system', 'content' => 'Ты — шеф-повар и нутрициолог. Составь персонализированные рецепты с точным КБЖУ.'],
                ['role' => 'user', 'content' => json_encode($fullProfile)],
            ],
        ]);

        $parsedRecipes = $this->parseRecipes($recipes);

        // 3. Рекомендации готовых блюд из ресторанов
        $readyDishes = $this->recommendation->getReadyDishes($parsedRecipes, $userId);

        // 4. Набор ингредиентов для дома
        $homeIngredients = $this->recommendation->getHomeIngredients($parsedRecipes, $userId);

        // 5. Проверка наличия и цен
        foreach (array_merge($readyDishes, $homeIngredients) as &$item) {
            $item['in_stock'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
        }

        // 6. Сохранение в профиль пользователя
        $this->saveToUserProfile($userId, $fullProfile, $parsedRecipes);

        Log::channel('audit')->info('Food AI constructor used', [
            'user_id' => $userId,
            'diet' => $diet,
            'recipes_count' => count($parsedRecipes),
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'food',
            type: 'menu_construction',
            payload: [
                'recipes' => $parsedRecipes,
                'ready_dishes' => $readyDishes,
                'home_ingredients' => $homeIngredients,
            ],
            suggestions: array_merge($readyDishes, $homeIngredients),
            confidence_score: 0.95,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}

3. Ключевые модели Food

Restaurant — рестораны
Dish — готовые блюда
Ingredient — ингредиенты
Recipe — рецепты
Order — заказ (готовое или набор ингредиентов)
Menu — меню ресторана

Особенности заказа:

Реал-тайм проверка наличия
Автоматический расчёт КБЖУ
Уведомление о готовности и доставке
Отмена с штрафом (если готовится)


4. B2C vs B2B в Food

ПараметрB2CB2BЦеныРозничныеОптовые для ресторановAI-конструкторПерсональные рецептыКорпоративное меню и поставкиМинимальный заказ1 блюдоMOQ для ингредиентовОплатаПолная предоплатаОтсрочка 7–14 днейДоступПубличный маркетплейсПриватная B2B-витрина + API

5. Интеграции вертикали Food

Inventory — наличие блюд и ингредиентов
Wallet — оплата заказов и выплаты ресторанам
Delivery — доставка готовой еды или наборов ингредиентов
AI — конструктор меню + КБЖУ
ML — UserTasteProfile (диеты, аллергены, предпочтения)
Fraud — проверка перед крупными заказами
Tenant Panel — управление ресторанами и меню
User Cabinet — персональные рецепты, история заказов


6. Чек-лист реализации вертикали Food (обязателен)

 Папка app/Domains/Food/ + 9 слоёв
 MenuConstructorService (полный, с КБЖУ)
 Модели Restaurant, Dish, Ingredient, Recipe, Order
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в ценах и условиях
 Реал-тайм проверка наличия блюд
 Тесты: генерация меню, расчёт КБЖУ, заказ готовых блюд
 
 ### Особенности вертикали Furniture:

Главный акцент — AI-конструктор интерьера (анализ фото комнаты + 3D-визуализация + смета ремонта).
Полный цикл: от подбора мебели до расчёта стоимости переделки и доставки.
Интеграция с UserTasteProfile (предпочтения по стилю, цветам, бюджету, материалам).
B2C: розничная покупка мебели и дизайн-проекты для частных квартир.
B2B: оптовые поставки для отелей, офисов, ресторанов, франшизы.

Папка: app/Domains/Furniture/

1. 9-Слойная архитектура (строго соблюдать)
Layer 1: Models

FurnitureItem — товары мебели и декора
RoomDesign — проекты дизайна комнат
Material — материалы (ткань, дерево, металл и т.д.)
RepairProject — проекты ремонта
Order — заказы (мебель + услуги)

Layer 2: DTOs

CreateRoomDesignDto
AnalyzeRoomPhotoDto
CalculateRepairCostDto

Layer 3: Services

FurnitureService — основная бизнес-логика
InteriorDesignConstructorService — главный AI-конструктор
RepairCalculatorService
VisualizationService (3D)

Layer 4–9: Requests, Resources, Events, Listeners, Jobs, Filament — по общему шаблону.

2. AI-конструктор Furniture (InteriorDesignConstructorService)
PHPfinal readonly class InteriorDesignConstructorService
{
    public function __construct(
        private \OpenAI\Client $openai,
        private RecommendationService $recommendation,
        private InventoryService $inventory,
        private UserTasteAnalyzerService $tasteAnalyzer,
        private VisualizationService $visualization,
        private RepairCalculatorService $repairCalculator,
    ) {}

    public function analyzeRoomAndDesign(UploadedFile $roomPhoto, string $desiredStyle, int $budget, int $userId): AIConstructionResult
    {
        $this->fraud->check(new AIUsageDto($userId, 'furniture'));

        // 1. Анализ комнаты (Vision API)
        $analysis = $this->openai->vision()->analyze([
            'image' => $roomPhoto->getRealPath(),
            'prompt' => "Анализ комнаты для дизайна интерьера. Определи: площадь, освещение, текущую мебель, стиль, цветовую гамму. Рекомендуй мебель, цвета, текстиль.",
        ]);

        $roomAnalysis = $this->parseRoomAnalysis($analysis);

        // 2. Персонализация по вкусам пользователя
        $taste = $this->tasteAnalyzer->getProfile($userId);
        $fullProfile = array_merge($roomAnalysis, $taste->interior_preferences ?? [], [
            'style' => $desiredStyle,
            'budget' => $budget,
        ]);

        // 3. Генерация рекомендаций мебели
        $recommendations = $this->recommendation->getFurnitureRecommendations($fullProfile, $userId);

        // 4. 3D-визуализация комнаты
        $visualizationUrl = $this->visualization->generate3D($roomAnalysis, $recommendations);

        // 5. Расчёт полной стоимости ремонта и мебели
        $costCalculation = $this->repairCalculator->calculate($recommendations, $budget);

        // 6. Проверка наличия и цен
        foreach ($recommendations as &$item) {
            $item['in_stock'] = $this->inventory->getAvailableStock($item['product_id']) > 0;
        }

        // 7. Сохранение проекта в профиль пользователя
        $this->saveDesignToProfile($userId, $fullProfile, $visualizationUrl);

        Log::channel('audit')->info('Furniture AI constructor used', [
            'user_id' => $userId,
            'style' => $desiredStyle,
            'budget' => $budget,
            'correlation_id' => request()->header('X-Correlation-ID'),
        ]);

        return new AIConstructionResult(
            vertical: 'furniture',
            type: 'interior_design',
            payload: [
                'room_analysis' => $roomAnalysis,
                'recommendations' => $recommendations,
                'visualization_url' => $visualizationUrl,
                'total_cost' => $costCalculation['total'],
            ],
            suggestions: $recommendations,
            confidence_score: 0.94,
            correlation_id: request()->header('X-Correlation-ID')
        );
    }
}
VisualizationService — использует Blender / external 3D-сервис для генерации 3D-модели комнаты.

3. Ключевые модели Furniture

FurnitureItem — товары (диван, стол, шкаф и т.д.)
RoomDesign — проекты дизайна
Material — материалы
RepairProject — проекты ремонта
Order — заказы (мебель + услуги по сборке/доставке)

Особенности заказа:

Реал-тайм проверка наличия на складе
Расчёт стоимости доставки крупногабаритных товаров
3D-визуализация в заказе


4. B2C vs B2B в Furniture

ПараметрB2CB2BЦеныРозничныеОптовые + скидки по объёмуAI-конструкторДля частной квартирыДля отелей, офисов, ресторановМинимальный заказ1 предметMOQ (минимум 5–20 шт)ОплатаПолная предоплатаОтсрочка 14–30 днейДоставкаСтандартнаяКрупногабаритная + монтаж

5. Интеграции вертикали Furniture

Inventory — наличие мебели на складах
Wallet — оплата заказов и выплаты поставщикам
Delivery — специальная логистика для крупногабаритных товаров
AI — конструктор интерьера + 3D-визуализация
ML — UserTasteProfile (стили, цвета, материалы, бюджет)
Fraud — проверка перед крупными заказами
Tenant Panel — управление каталогом и проектами
User Cabinet — сохранённые дизайны, 3D-визуализации, расчёты


6. Чек-лист реализации вертикали Furniture (обязателен)

 Папка app/Domains/Furniture/ + 9 слоёв
 InteriorDesignConstructorService (полный, с 3D)
 Модели FurnitureItem, RoomDesign, RepairProject
 Интеграция с Inventory, Wallet, Delivery
 B2C/B2B различия в ценах и условиях
 Реал-тайм 3D-визуализация
 Тесты: анализ комнаты, генерация дизайна, расчёт стоимости


###
КАНОН CATVRF 2026 — UI-АРХИТЕКТУРА, КОМПОНЕНТНАЯ СИСТЕМА И FRONTEND-СТЕК
Версия: 1.0
Дата: 13.04.2026
Статус: PRODUCTION MANDATORY

Общий стек UI (то, что реально используется)

Livewire 3 — основной движок для динамических компонентов (формы, каталоги, корзина, бронирования, личный кабинет).
Alpine.js — лёгкая реактивность внутри Blade/Livewire (dropdowns, модалки, гео-карты).
Vue 3 — используется selectively в frontend/src/ и для сложных модулей (реал-тайм геотрекинг, AI-конструктор).
Tailwind CSS 4 — вся стилизация; конфиг содержит дизайн-токены и marketplace-утилиты.
Filament 3 — админки (tenant + super-admin), автогенерация CRUD и custom pages.
Blade templates — базовые layouts и полустатические страницы.
Vite — сборка JS/CSS (единственный билд-инструмент).
Cypress + Vitest — e2e и component тесты для ключевых флоу.

Структура (реально используемая):
- resources/views/ — Blade + Livewire views
- resources/js/ — Alpine/Vue entry points
- frontend/src/ — отдельное Vue-приложение для сложных SPA-фрагментов
- modules/*/Resources/ и modules/*/Livewire/ — вертикальные компоненты

Ключевые элементы реализованы:
- CartWidget, WalletBalance, NotificationBell, GeoLocationPicker, SearchBar
- Catalog grid/list с фильтрами, Product/Service detail, Booking flow (Livewire + Alpine)
- Real-time delivery tracking (Vue + Echo + Leaflet/Yandex)
- Filament resources + tenant dashboards

Основные проблемы и рекомендации:
- Выделить общую компонентную библиотеку (packages/ui или modules/Core/Resources/Components).
- Уточнить границы Vue vs Livewire: Vue для сложного client-side state (Pinia), Livewire для серверной логики.
- Развернуть дизайн-токены и базовую UI-библиотеку (кнопки, карточки, модалки, price-label и т.д.).
- Ввести обязательные mobile-first тесты (Cypress viewport 375px) для ключевых флоу.
- Внедрить оптимизации Livewire: ленивые загрузки, кэширование рендеров, правильный use of wire:model.

Вердикт: архитектура адекватна для быстрого масштабирования (Livewire + Filament ускоряют вертикали), но требует централизации UI-компонентов, строгих дизайн-токенов и чётких границ Vue/Livewire для production-grade масштабируемости.

Железные правила (нарушение = reject PR):

Livewire 3 — основной движок для любых серверных динамических компонентов (формы, каталоги, корзина, бронирования, личный кабинет, wizard'ы). Никаких прямых fetch/axios вне Livewire там, где данные исходят с сервера.
Alpine.js — только для чисто клиентской реактивности (toggle, dropdown, модалки, микро-анимации). Не дублировать с Livewire.
Vue 3 — только для сложной state-логики, требующей Pinia: real-time геотрекинг, AI-конструктор с многошаговым wizard, WebSocket-дашборды. Не использовать там, где справляется Livewire + Alpine.
Tailwind CSS 4 — вся стилизация. Никаких inline-стилей, никаких кастомных CSS-файлов без добавления в дизайн-токены (tailwind.config.js). Все цвета, отступы, радиусы — через токены.
Filament 3 — исключительно для Admin Panel, Tenant Panel и B2B Panel. Никакого Filament в customer-facing интерфейсах.
Blade — базовые layouts и статические/полустатические страницы. Не писать бизнес-логику в Blade.
Vite — единственный сборщик. Никакого webpack, mix.
Все переиспользуемые компоненты живут в resources/views/components/ (Blade components) или app/Livewire/Shared/ (Livewire shared). Вертикальные компоненты — в modules/{Vertical}/Resources/ и modules/{Vertical}/Livewire/.
Mobile-first обязателен. Любой новый компонент тестируется на ширине 375px перед merge.
Cypress-тест обязателен для ключевых user-flows (booking, checkout, tenant switch, AI wizard).


1. Технологический стек (Production 2026)

СлойТехнологияВерсияНазначениеServer-side dynamicsLivewire3.xФормы, каталог, корзина, бронирование, личный кабинетClient-side microAlpine.js3.xDropdowns, модалки, локальный UI-стейтComplex SPAVue 33.xReal-time трекинг, AI-конструктор, Pinia-дашбордыStylesTailwind CSS4.xВсё стилизация, дизайн-токеныAdmin/Tenant UIFilament3.xAdmin Panel, Tenant Panel, B2B PanelTemplatesBladelaravelLayouts, статика, email-шаблоныBuildVitelatestJS/CSS сборкаTestse2eCypressEnd-to-end тесты пользовательских флоувTestscomponentVitestUnit/component тесты


2. Структура папок (обязательная)

resources/
├── views/
│   ├── components/          # Blade x-компоненты (Button, Card, Modal, Badge и т.д.)
│   ├── layouts/             # Base layouts (app, guest, tenant, email)
│   ├── livewire/
│   │   ├── shared/          # Cross-vertical Livewire (CartWidget, WalletBalance, NotificationBell)
│   │   ├── user/            # Личный кабинет пользователя
│   │   ├── auth/            # Auth flow компоненты
│   │   └── {vertical}/      # Вертикальные компоненты (beauty/, food/, furniture/ и т.д.)
│   └── pages/               # Статические и semi-static pages
├── js/
│   ├── app.js               # Alpine + Livewire entrypoint
│   ├── echo.js              # Laravel Echo / WebSocket setup
│   └── vue/                 # Vue entrypoints (только для SPA-фрагментов)
└── css/
    └── app.css              # Tailwind directives + custom utilities

frontend/
└── src/                     # Отдельное Vue 3 приложение (real-time, AI wizard, dashboards)
    ├── components/
    ├── stores/              # Pinia stores
    ├── composables/
    └── pages/

modules/
└── {Vertical}/
    ├── Resources/
    │   └── views/           # Вертикальные Blade + Livewire views
    └── Livewire/            # Вертикальные Livewire компоненты

app/
└── Livewire/
    ├── Shared/              # Переиспользуемые Livewire компоненты
    └── {Vertical}/          # Вертикальные Livewire компоненты


3. Дизайн-токены и компонентная библиотека

tailwind.config.js — обязательные расширения:

// tailwind.config.js
module.exports = {
    darkMode: 'class',
    theme: {
        extend: {
            colors: {
                brand: {
                    50:  '#f0f9ff',
                    500: '#0ea5e9',
                    900: '#0c4a6e',
                },
                marketplace: {
                    primary:   '#0ea5e9',
                    secondary: '#8b5cf6',
                    success:   '#10b981',
                    warning:   '#f59e0b',
                    danger:    '#ef4444',
                    neutral:   '#6b7280',
                },
            },
            borderRadius: {
                'marketplace': '0.75rem',
            },
            boxShadow: {
                'card':   '0 2px 8px rgba(0,0,0,0.08)',
                'modal':  '0 8px 32px rgba(0,0,0,0.16)',
            },
        },
    },
};

Обязательные Blade x-компоненты в resources/views/components/:

Компонент x-ui-button (variants: primary, secondary, danger, ghost, link; sizes: sm, md, lg; состояния: loading, disabled).
Компонент x-ui-card (slots: header, body, footer; variants: default, elevated, bordered).
Компонент x-ui-modal (alpine-based, trap focus, ESC close, ARIA).
Компонент x-ui-badge (variants: success, warning, danger, info, neutral).
Компонент x-ui-input, x-ui-select, x-ui-textarea (с error state и label).
Компонент x-ui-avatar (с fallback инициалами и grayscale для offline).
Компонент x-ui-skeleton (loading placeholder для карточек каталога).
Компонент x-ui-empty-state (для пустых каталогов/списков с иллюстрацией и CTA).
Компонент x-ui-price (показывает цену с учётом B2C/B2B, grayscale для недоступных).

Правило grayscale для товаров без наличия:

{{-- x-ui-product-card.blade.php --}}
<div @class([
    'rounded-marketplace shadow-card transition-all',
    'grayscale opacity-60 pointer-events-none' => !$inStock,
])>
    {{-- Без кнопки "В корзину" если inStock = false --}}
    @if($inStock)
        <livewire:cart.add-to-cart :productId="$product->id" />
    @endif
</div>


4. Livewire 3 — правила и паттерны

Каждый Livewire-компонент — final class.
Constructor injection через DI (не app()->make() внутри методов).
Никаких прямых Model::create() в компонентах — только через сервисы.
Пагинация — обязательно WithPagination trait, lazy loading для каталогов.
Оптимизация: wire:model.live только там где нужно (иначе wire:model.blur или wire:model.lazy).
Кэширование rendered данных: #[Cache(ttl: 300)] или явный cache()->remember().
correlation_id передаётся через meta-тег + JS в заголовке X-Correlation-ID каждого Livewire-запроса.

Шаблон Livewire-компонента (обязателен):

declare(strict_types=1);

namespace App\Livewire\Beauty;

use Livewire\Component;
use App\Domains\Beauty\Services\BeautyService;
use App\Domains\Beauty\DTOs\CreateAppointmentDto;

final class BookingForm extends Component
{
    public int $salonId;
    public ?int $masterId = null;
    public ?string $selectedDate = null;
    public ?string $selectedTime = null;

    protected array $rules = [
        'masterId'     => 'required|integer',
        'selectedDate' => 'required|date|after:today',
        'selectedTime' => 'required|string',
    ];

    public function __construct(
        private readonly BeautyService $beautyService,
    ) {}

    public function book(): void
    {
        $this->validate();

        $dto = new CreateAppointmentDto(
            salonId:       $this->salonId,
            masterId:      $this->masterId,
            date:          $this->selectedDate,
            time:          $this->selectedTime,
            correlationId: request()->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
        );

        $appointment = $this->beautyService->createAppointment($dto);

        $this->dispatch('booking-confirmed', appointmentId: $appointment->id);
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.beauty.booking-form');
    }
}


5. Vue 3 — правила и паттерны (только для сложных SPA-фрагментов)

Использовать только где нужна сложная клиентская state-логика:
Real-time геотрекинг (курьер на карте).
AI-конструктор wizard (многошаговый с file upload + результатом).
WebSocket-дашборды (live-метрики в Tenant Panel).

Pinia — обязательный стейт-менеджер (никакого Vuex).
Composition API + <script setup> — стандарт. Options API запрещён.
TypeScript — обязателен для всех файлов в frontend/src/.
Типизированные API-клиенты через axios + openapi-fetch (из openapi.json).

Шаблон Pinia store для геотрекинга:

// frontend/src/stores/deliveryTracking.ts
import { defineStore } from 'pinia'
import Echo from 'laravel-echo'

export const useDeliveryTrackingStore = defineStore('deliveryTracking', () => {
    const courierLocation = ref<{ lat: number; lon: number } | null>(null)
    const isTracking = ref(false)

    function startTracking(deliveryOrderId: number): void {
        window.Echo
            .private(`delivery.${deliveryOrderId}`)
            .listen('CourierLocationUpdated', (e: { lat: number; lon: number }) => {
                courierLocation.value = { lat: e.lat, lon: e.lon }
            })
        isTracking.value = true
    }

    function stopTracking(): void {
        isTracking.value = false
        courierLocation.value = null
    }

    return { courierLocation, isTracking, startTracking, stopTracking }
})


6. Shared Livewire компоненты (cross-vertical, обязательны)

КомпонентКлассОписаниеCartWidgetApp\Livewire\Shared\CartWidgetМини-корзина в хедере (кол-во позиций, сумма, ссылка)WalletBalanceApp\Livewire\Shared\WalletBalanceТекущий баланс кошелька + бонусы (B2C/B2B)NotificationBellApp\Livewire\Shared\NotificationBellКолокол с кол-вом непрочитанных уведомленийGeoLocationPickerApp\Livewire\Shared\GeoLocationPickerПикер адреса с Yandex Maps AutocompleteSearchBarApp\Livewire\Shared\SearchBarПоиск с автодополнением (Livewire + Algolia/Scout)FraudAlertBannerApp\Livewire\Shared\FraudAlertBannerПоказывает фрод-уведомления (warning/high)B2BModeSwitcherApp\Livewire\Shared\B2BModeSwitcherПереключатель B2C ↔ B2B (если есть ИНН)AIConstructorButtonApp\Livewire\Shared\AIConstructorButtonЕдиная точка входа в AI-конструктор вертикалиPriceLabelApp\Livewire\Shared\PriceLabelЦена с учётом B2C/B2B тира и наличия

Правило B2BModeSwitcher:

// Определение B2B только по каноническому правилу:
$isB2B = $request->has('inn') && $request->has('business_card_id');
// В Livewire-компоненте:
public bool $isB2B;
public function mount(): void
{
    $this->isB2B = request()->has('inn') && request()->has('business_card_id');
}


7. Мобильная адаптивность (Mobile-first, обязательно)

Все компоненты пишутся от mobile (375px) к desktop. Никаких fixed px-размеров вне дизайн-токенов.
Booking-календарь, карты, AI-wizard — обязательно протестированы на мобильных (Cypress viewport 375x667).
Навигация: mobile → drawer/bottom-nav; desktop → sidebar/top-nav.
Изображения: lazy loading + srcset + WebP.
Touch targets: минимум 44×44px для всех интерактивных элементов (WCAG 2.5.5).
Tailwind breakpoints строго: sm (640px), md (768px), lg (1024px), xl (1280px). Никаких нестандартных.

Обязательные viewport-тесты в Cypress:

// cypress/support/viewports.ts
export const VIEWPORTS = {
    mobile:  [375, 667],
    tablet:  [768, 1024],
    deskinset-block-start: [1280, 800],
} as const;


8. Performance (обязательные правила)

Livewire: wire:loading директива на все кнопки и формы. Skeleton-компоненты при загрузке каталога.
Пагинация каталога: Livewire WithPagination + URL query params (без полной перезагрузки страницы).
Изображения: lazy="lazy" на все img вне first fold. Blur placeholder (base64) для first fold.
JS: Vite code splitting. Vue-компоненты — defineAsyncComponent() для отложенной загрузки.
CSS: Tailwind purge в production (только используемые классы).
Redis-кэш для rendered Blade-фрагментов (цены, наличие) с TTL 60 сек.
Нет N+1 в Livewire: все данные через with() + кэш в mount(), не в render().

Шаблон оптимизированного каталога:

final class ProductCatalog extends Component
{
    use WithPagination;

    public string $search = '';
    public string $sortBy = 'created_at';

    // Кэшируем в mount, не пересчитываем на каждый render
    public function mount(): void
    {
        // eager load только нужное
    }

    #[\Livewire\Attributes\Computed]
    public function products(): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return cache()->remember(
            "catalog:{$this->search}:{$this->sortBy}:{$this->getPage()}:".tenant()->id,
            60,
            fn() => Product::with(['inventory', 'images'])
                ->search($this->search)
                ->orderBy($this->sortBy)
                ->paginate(24)
        );
    }

    public function render(): \Illuminate\View\View
    {
        return view('livewire.catalog.product-catalog');
    }
}


9. Accessibility (ARIA, обязательно)

Все модалки: role="dialog", aria-modal="true", aria-labelledby, focus trap, ESC close.
Все формы: каждый input имеет label (или aria-label). Ошибки связаны через aria-describedby.
Кнопки без текста: aria-label обязателен (иконки, закрытие модалок).
Цветовой контраст: минимум 4.5:1 для основного текста (WCAG AA).
Livewire-переходы: aria-live="polite" для динамически обновляемых регионов (цена, статус заказа).


10. Экраны / User Flows (что покрыто и что обязательно)

ЭкранЛивваер/VueFilamentCypress-тестHome / LandingLivewire—Обязателен (smoke)Catalog / SearchLivewire (WithPagination)—Обязателен (filter + scroll)Product/Service DetailLivewire—ОбязателенCart + CheckoutLivewire (CartService)—Обязателен (end-to-end)Booking FlowLivewire—Обязателен (booking e2e)Delivery TrackingVue 3 + Pinia + Echo—ОбязателенUser Profile / WalletLivewire—ОбязателенAI Constructor WizardVue 3 + Pinia—ОбязателенTenant Admin DashboardFilamentFilamentОбязателенB2B PanelFilamentFilamentОбязателенFraud Alert UILivewire (shared)Admin widgetОбязателен


11. Dark Mode (обязательно для всех компонентов)

Tailwind dark: prefix на все цветовые классы в компонентах.
Переключатель темы — dark-mode-switcher Alpine-компонент, хранит выбор в localStorage.
Системная тема по умолчанию (prefers-color-scheme).

Пример:

<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-gray-100">
    <x-ui-card class="shadow-card dark:shadow-none dark:border dark:border-gray-700">
        ...
    </x-ui-card>
</div>


12. Чек-лист нового UI-компонента (обязателен перед merge)

 Blade x-компонент или Livewire final class
 Mobile-first (проверен на 375px)
 Dark mode (dark: классы)
 ARIA (label, role, aria-live где нужно)
 wire:loading / skeleton для async-состояний
 Нет прямых Model::query() в компоненте — только через сервис
 Граyscale + отсутствие кнопки "В корзину" при inStock = false
 correlation_id прокинут через meta-тег в каждый Livewire-запрос
 Cypress-тест для ключевого user flow
 TypeScript-типизация для Vue-компонентов


13. Чек-лист добавления UI новой вертикали (обязателен)

 Создана папка modules/{Vertical}/Resources/views/ и modules/{Vertical}/Livewire/
 Catalog-компонент с фильтрами (Livewire WithPagination)
 Product/Service detail page
 Booking/Order flow (Livewire + validation)
 AI-конструктор: кнопка входа (x-ui-ai-constructor-button) + Vue wizard (frontend/src/pages/{Vertical}AIConstructor.vue)
 Seller dashboard в Tenant Panel (Filament Resource)
 B2B витрина и условия задокументированы в компоненте
 Mobile-first проверка
 Cypress smoke-тест (открытие каталога + добавление в корзину)
 