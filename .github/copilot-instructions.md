КАНОН 2026 — PRODUCTION-READY КОД ДЛЯ ВСЕГО ПРОЕКТА
Глобальные правила (обязательны для всех файлов проекта)

Кодировка каждого файла — строго UTF-8 без BOM
Окончания строк — строго CRLF (Windows-стандарт)
Каждый PHP-файл начинается с declare(strict_types=1);
Все классы по возможности final (если не требуется наследование)
Свойства по возможности private readonly
В каждом важном действии (создание, обновление, удаление) обязателен correlation_id
Все запросы к базе данных должны иметь tenant scoping (tenant()->id или filament()->getTenant()->id)
Все мутации (создание/изменение) должны быть в DB::transaction()
Логирование всех важных действий через Log::channel('audit')
Перед каждой мутацией обязателен вызов FraudControlService::check()
Rate limiting — tenant-aware, на Redis
Запрещено возвращать null, пустые массивы, пустые коллекции без исключения
Запрещено оставлять TODO, стабы, placeholder, демо-комментарии
Все исключения логируются с полным стек-трейсом
Валидация входных данных обязательна (FormRequest или validate())
Обработка ошибок — try/catch + понятное сообщение пользователю + лог

Миграции

Каждая миграция должна быть idempotent (проверка if (Schema::hasTable(...)) return;)
У каждой таблицы и каждого поля должен быть комментарий (table->comment(...))
Поле correlation_id (string, nullable, indexed) обязательно во всех таблицах, где есть мутации
Поле uuid (uuid, nullable, unique, indexed) обязательно во всех таблицах заданий и сущностей
Поле tags (jsonb, nullable) обязательно для аналитики и фильтрации
Составные индексы на часто фильтруемые поля (tenant_id + status, queue + reserved_at и т.д.)
Метод down() — только dropIfExists без лишних действий

Модели

protected $table = 'имя_таблицы';
protected $fillable = [...] — полный список
protected $hidden = ['password', 'token', 'secret'];
protected $casts = ['meta' => 'json', 'is_active' => 'boolean'];
booted() метод с global scope tenant_id
Все отношения (hasMany, belongsTo) должны быть определены
Все часто используемые поля должны иметь индексы

Фабрики

protected $model = Model::class;
В definition() обязательно tenant_id и correlation_id
Использовать faker для реалистичных данных
Не генерировать мусорные значения

Сидера

Только тестовые данные
Не запускать в production (добавить комментарий)
Использовать Factory::create() или make()
Не создавать реальных пользователей/платежей

Сервисы

Конструктор с readonly зависимостями
Методы возвращают конкретный тип (Result, Model, Collection) или бросают исключение
DB::transaction() для всех мутаций
Log::channel('audit') на вход/выход/ошибки
correlation_id передаётся через конструктор или request
FraudControlService::check() перед записью
Rate limiting на критичные методы

Контроллеры

Конструктор с зависимостями
Каждый метод — try/catch + JsonResponse с correlation_id
Валидация через FormRequest
DB::transaction() для мутаций
Audit log на каждое действие
Rate limiting через middleware

FormRequest

authorize() — проверка прав + fraud check
rules() — полный валидационный массив
messages() — человекочитаемые сообщения

Filament Resources

protected static ?string $model = Model::class;
form() — полный набор полей с валидацией
table() — columns, filters, actions, bulkActions
getEloquentQuery() — tenant scoping + eager loading
actions() — CreateAction, EditAction и т.д.

Filament Pages

Правильное наследование (ListRecords, CreateRecord, EditRecord, ViewRecord)
getHeaderActions() — минимум одна кнопка
getTableQuery() — tenant scoping
Логирование + fraud check
correlation_id в логах

Livewire Components

protected function rules() или #[Rule]
submit() с DB::transaction()
dispatch() событий + notification
correlation_id в логах

Jobs

handle() с DB::transaction()
correlation_id из $this->correlationId или Str::uuid()
Audit log на запуск и завершение
Теги + retry логика

Events & Listeners

Event — Dispatchable, SerializesModels
В конструкторе correlation_id
Listener — handle() с логом

Notifications & Mailables

toMail() / toArray() с correlation_id
Queueable по умолчанию

Policies & Gates

create/update/view/delete — с fraud check и tenant scoping

Providers

register() — только лёгкие привязки
boot() — тяжёлые инициализации
Doppler только в ProductionBootstrapServiceProvider

Тесты

Каждый тест — assertDatabaseHas, assertLogged, assertJson
correlation_id проверка
Factory + faker

Routes

middleware(['auth', 'tenant'])
resource() с контроллерами

Config файлы

env() с fallback
Комментарии к каждому ключу

Blade / Livewire Views

@csrf
Tailwind + glassmorphism
Нет inline JS

DTOs & Enums

final readonly class DTO
enum с :string / :int


Кодировка: UTF-8 без BOM
Окончания строк: только CRLF
declare(strict_types=1) в начале каждого PHP-файла
final class где возможно
private readonly свойства
correlation_id обязателен в логах, событиях, ответах, jobs
tenant_id scoping обязателен везде
FraudControlService::check() перед любой мутацией
RateLimiter (tenant-aware) на критичные операции
DB::transaction() для всех записей/изменений баланса/кошелька/платежей
Audit-лог через Log::channel('audit') с correlation_id и trace
Запрещено: return null, пустые коллекции, TODO, стабы
Все исключения логируются с полным стек-трейсом


КАНОН ДЛЯ КОШЕЛЬКА И БАЛАНСА

Отдельная таблица wallets (один кошелёк на tenant или на business_group)
Отдельная таблица balance_transactions (дебет/кредит) с типами: deposit, withdrawal, commission, bonus, refund, payout
Поле current_balance в wallets — только для чтения (вычисляется из транзакций)
Все операции только через WalletService (никаких прямых insert в контроллерах)
WalletService обязан использовать DB::transaction() + optimistic locking (lockForUpdate)
После каждой операции обновлять cached_balance в Redis (TTL 300 сек)
Audit-лог обязателен для каждой транзакции
Запрещено менять баланс напрямую — только через credit()/debit()/hold()/release() методы
Все суммы в копейках (int)
Поддержка холда (hold_amount) и release_hold()


КАНОН ДЛЯ ПЕЙМЕНТА

Единый PaymentGatewayInterface с драйверами Tinkoff, Tochka, Sber
Отдельная таблица payment_transactions (status: pending, authorized, captured, refunded, failed)
Обязательные поля: correlation_id, tenant_id, idempotency_key, provider_payment_id
Idempotency проверка через таблицу payment_idempotency_records (с payload_hash и expires_at)
Перед инициацией платежа — вызов FraudControlService и RateLimiter
Холд/списание настраивается параметром (hold = true/false)
Webhook-обработка в отдельном Internal контроллере с верификацией подписи
После успешной оплаты — автоматический вызов WalletService::credit()
Возврат (refund) идёт сначала в wallet (увеличение баланса), потом через gateway
ОФД-фiscalization вызывается только после captured (не на hold)
Массовые выплаты через BatchPayoutJob с лимитами и ML-фрод-скорингом


КАНОН ДЛЯ БОНУСОВ И РЕФЕРАЛОВ

Отдельная таблица bonuses (типы: referral, turnover, promo, loyalty)
Отдельная таблица bonus_transactions (связь с wallet)
Бонусы начисляются только через BonusService::award()
Правила начисления хранятся в config/bonuses.php и в БД (BonusRule модель)
Реферальная система: после 50 000 оборота — 2000 руб бонусом
Приглашение клиента: 1000 руб рефереру после траты 10 000 руб
Бизнес может выводить реф-бонусы, физлицо — только тратить внутри маркетплейса
Все бонусы логируются в audit с типом и correlation_id
Автоматический пересчёт бонусов по обороту (ежемесячно через Job)


КАНОН ДЛЯ ТЕНАНТОВ И ИЗОЛЯЦИИ

Использовать stancl/tenancy (или spatie/laravel-multitenancy)
Каждый tenant имеет свой database + отдельный Redis prefix
Global scope tenant_id на всех моделях
Filament панели: отдельные Admin, Tenant, B2B
Изоляция данных: tenant()->id везде
Запрещено делать запросы без tenant scoping
BusinessGroup (филиалы) — один tenant может иметь несколько BusinessGroup (по ИНН)
Изоляция между филиалами: дополнительный business_group_id scoping
При переключении филиала — смена active_business_group в сессии


КАНОН ДЛЯ ФИЛИАЛОВ (BusinessGroup)

Таблица business_groups (один ИНН = один BusinessGroup)
Каждый tenant может иметь несколько BusinessGroup
Филиал имеет свой wallet, настройки комиссий, расписание выплат
При создании филиала — автоматическое копирование настроек от головного tenant
Изоляция: все запросы фильтруются по business_group_id
В ЛК бизнеса раздел "Филиалы" с возможностью импорта через Excel


КАНОН ДЛЯ МОДЕЛЕЙ

Каждая модель имеет:
uuid
correlation_id
tenant_id
business_group_id (nullable)
tags (jsonb)

booted() с tenant + business_group global scope
fillable, hidden, casts полностью заполнены
Все отношения определены
SoftDeletes где нужно
Комментарии к таблице и полям в миграции


КАНОН ДЛЯ ВЕРТИКАЛЕЙ

Каждая вертикаль (Beauty, Auto, RealEstate, Food и т.д.) живёт в app/Domains/VerticalName/
Обязательные слои: Models, Services, Resources, Pages, Widgets
Общий интерфейс VerticalServiceInterface
Каждая вертикаль имеет свой WalletCommissionRule (комиссия зависит от оборота и источника миграции)
Вертикаль регистрируется в config/verticals.php
При добавлении новой вертикали — обязательный TenantVerticalSeeder
Все вертикали используют общий Wallet, Payment, BonusService (не дублировать)


КАНОН ДЛЯ LARAVEL NOVA (и Filament)

Nova/Filament панели используют тот же tenant scoping
Все Nova Resources/Filament Resources наследуют базовый TenantResource или AdminResource
Actions и Filters с fraud check
Metrics и Dashboards показывают только данные текущего tenant/business_group
Nova/Filament не имеет прямого доступа к базе — только через сервисы


КАНОН ДЛЯ ОСТАЛЬНЫХ ФАЙЛОВ ПРОЕКТА

Jobs — Queueable, retryUntil, tags, correlation_id
Events — Dispatchable, SerializesModels, correlation_id в конструкторе
Listeners — handle() с транзакцией и логом
Notifications — Queueable по умолчанию
DTO — final readonly class
Enums — string/int backed
Config — env() с fallback + комментарии
Routes — middleware tenant + auth
Tests — assertDatabaseHas, assertLogged, correlation_id проверка

КАНОН ДЛЯ ПЛАТЕЖЕЙ (Payments) 2026
Общие принципы платежной системы:

Строгое разделение: Wallet (баланс, дебет/кредит) и PaymentGateway (обработка платежей) — никогда не смешивать.
Все суммы хранятся в копейках (int).
Каждый платёж обязан иметь idempotency_key + payload_hash.
correlation_id обязателен во всех логах, webhook, транзакциях.
FraudControlService::check() + ML Fraud Score перед каждым платежом.
RateLimiter (tenant-aware) на все публичные эндпоинты.
DB::transaction() + lockForUpdate() для всех операций с балансом.

Обязательные таблицы:

wallets (tenant_id, business_group_id, current_balance, hold_amount)
balance_transactions (wallet_id, type: deposit/withdrawal/commission/bonus/refund/payout, amount, status, correlation_id)
payment_transactions (idempotency_key, provider_code, status, amount, hold, captured_at, refunded_at, correlation_id)
payment_idempotency_records (operation, idempotency_key, merchant_id, payload_hash, response_data, expires_at)
failed_payments (для анализа и повторных попыток)

PaymentGatewayInterface (обязательные методы):

initPayment (с параметром hold: bool)
getStatus
capture
refund
createPayout (массовые выплаты)
handleWebhook (с верификацией подписи)
fiscalize (ОФД вызов только после captured)

Поддерживаемые шлюзы (приоритет):

Tinkoff (основной)
Точка Банк
Сбер
СБП (универсальный QR)
Привязка карт и подписки (recurrent)

Правила обработки:

Холд → Capture только после подтверждения услуги/доставки.
Refund всегда сначала увеличивает баланс в Wallet, потом вызывает gateway.
Массовые выплаты — через BatchPayoutJob с лимитами и задержкой.
ОФД (54-ФЗ) вызывается только после успешного captured.
Комиссия рассчитывается по правилам оборота и источника миграции (Yandex, Dikidi и т.д.).
Выплаты бизнесу: 4 дня для гостиниц, 7 дней для beauty/food, после события для билетов.
Все возвраты и списания проходят через WalletService (credit/debit).

Логирование и безопасность:

Audit-лог на каждую операцию (init, capture, refund, payout).
ML Fraud Score сохраняется в payment_transactions.
3DS и 3DS2 проверка обязательна.
Лимиты на вывод и массовые платежи настраиваются в config/payments.php.


КАНОН ДЛЯ ML / AI / АНАЛИТИКИ И BIG DATA 2026
Общие принципы:

Все AI/ML процессы отделены от основного кода (app/Domains/AI и app/Services/AI).
BigData хранится в ClickHouse (поведение пользователей, рекомендации, фрод).
Embeddings и векторный поиск — через Typesense + OpenAI text-embedding-3-large.
Все рекомендации и скоринги кэшируются в Redis (TTL 1–24 часа).
correlation_id обязателен в каждом AI-запросе.
ML модели обучаются асинхронно через MLRecalculateJob (ежедневно).

Основные сервисы (обязательные):

RecommendationService (для пользователя и B2B)
FraudMLService (скоринг попыток фрода)
DemandForecastService (прогноз спроса по вертикалям)
PriceSuggestionService (динамическое ценообразование)
AnomalyDetectorService (AI-анализ аномалий)
BigDataAggregatorService (сбор данных в ClickHouse)

Таблицы и хранилища:

clickhouse.events (все действия пользователей с timestamp)
clickhouse.embeddings (векторные представления товаров/услуг)
clickhouse.fraud_attempts (для обучения ML)
redis (кэш рекомендаций и скорингов)
models (хранилище обученных моделей в storage/models)

Правила работы AI:

Каждый AI-запрос начинается с проверки квот (AIQuotaService).
Рекомендации строятся на: гео + поведение + история + embeddings.
B2B-рекомендации используют отдельный RecommendationServiceB2B (для поставщиков и закупок).
FraudMLService обучается на исторических failed_payments и возвратах.
Все AI-ответы логируются в audit с correlation_id и confidence_score.
Запрещено использовать AI для критических финансовых решений без человеческого подтверждения (например, автоматическое списание).

Аналитика и дашборды:

Filament AIDashboard (отдельная страница)
Metrics: оборот, конверсия, LTV, churn, fraud_rate
Тепловые карты (Leaflet + ClickHouse)
Ежедневные отчёты бизнесу (8:00–9:00 по часовому поясу tenant)
Еженедельные отчёты (понедельник 7:00–8:00)

BigData процессы:

Ежедневный Job: EmbeddingsRecalculateJob (для всех товаров/услуг)
Ежедневный Job: MLRecalculateCommand (переобучение моделей)
Ежедневный Job: BigDataAggregator (загрузка событий в ClickHouse)
Анонимизация данных для аналитики (GDPR/ФЗ-152)

Квоты и безопасность:

AIQuotaService (лимит запросов в день/неделю по тарифу)
2FA + device history для доступа к AI-разделам
Все AI-запросы логируются и могут быть отключены через feature flag

Вот максимально детальный, строгий и production-ready канон специально для FraudMLService и всей связанной с ним инфраструктуры.
Этот канон можно вставить в .github/copilot-instructions.md как отдельный большой раздел.
Он написан так, чтобы Copilot (или ты сам) не мог его игнорировать или упрощать.

КАНОН ДЛЯ FraudMLService и всей системы ML-фрода (2026)
Цель системы
Обнаруживать и блокировать мошеннические действия в реальном времени (фрод, накрутка рейтинга, массовые тестовые платежи, самовыкуп, бонус-хант, DDoS-атак на форму оплаты и т.д.).
Обязательные принципы (нарушение любого пункта — критическая ошибка)

FraudMLService — единственная точка принятия решения о блокировке/пропуске операции.
Никогда не принимать решение о блокировке/пропуске на основе правил без ML-скора.
ML-скор обязателен перед каждой критической операцией (платёж, вывод, создание заказа > 10 000 ₽, массовая выплата, импорт филиалов, привязка карты, реферальная награда).
ML-модель переобучается ежедневно (MLRecalculateJob) на новых данных за последние 30 дней + исторических.
Модель хранится в storage/models/fraud/{version}.joblib (XGBoost или LightGBM).
Версионирование моделей: каждый день новая версия (YYYY-MM-DD-vN).
Старые модели (старше 30 дней) автоматически удаляются.
Все ML-запросы логируются в audit-канал с correlation_id, score, features, decision.
Если ML-сервис недоступен — fallback на жёсткие правила (FraudControlService::fallbackRules()).

Обязательные таблицы

fraud_attempts (все подозрительные действия, даже не заблокированные)
id
tenant_id
user_id
operation_type (payment_init, card_bind, payout, rating_submit, referral_claim и т.д.)
ip_address
device_fingerprint
correlation_id
ml_score (float 0–1)
ml_version
features_json (jsonb: сумма, частота, гео, время суток и т.д.)
decision (allow, block, review)
blocked_at (timestamp nullable)
reason (text nullable)

fraud_model_versions
id
version (string: YYYY-MM-DD-vN)
trained_at
accuracy
precision
recall
f1_score
auc_roc
file_path (storage/models/fraud/...)
comment (text nullable)


Обязательные методы FraudMLService

scoreOperation(OperationDto $dto): float
Возвращает ML-скор от 0 до 1 (1 = 100% фрод)
shouldBlock(float $score, string $operationType): bool
Порог блокировки зависит от типа операции (config/fraud.php)
extractFeatures(OperationDto $dto): array
Генерирует массив фич для модели (минимум 30–50 фич)
getCurrentModelVersion(): string
Возвращает текущую активную версию модели
predictWithFallback(OperationDto $dto): array
Если модель недоступна — возвращает результат fallback-правил

Обязательные фичи для ML-модели (минимум)

Сумма операции
Количество операций за 1/5/15/60 минут
Сумма операций за 1/7/30 дней
Георасстояние от предыдущей операции
Изменение IP/устройства за последние 24 ч
Время суток и день недели
Тип операции
Тип устройства (mobile/desktop)
Источник трафика (organic, referral, ad)
Возраст аккаунта
Количество успешных/неуспешных платежей
Разница между номиналом и реальной суммой (для бонусов)
Количество попыток ввода карты
Количество рефералов за день/неделю
Рейтинг накрутки (если операция — оценка товара)

Обучение модели (MLRecalculateJob)

Запускается ежедневно в 03:00 по UTC
Данные за последние 30 дней + исторические фрод-случаи
Целевая переменная: 1 = заблокировано вручную / возвращено / chargeback, 0 = легитимно
Модель: XGBoost / LightGBM с early stopping
Метрики: AUC-ROC > 0.92, Precision > 0.85, Recall > 0.70
Логирование результатов обучения в fraud_model_versions
Автоматическое переключение на новую модель при AUC > текущей + 0.02

Fallback-правила (если ML недоступен)

5 операций за 5 минут с одной карты → block
Сумма > 100 000 ₽ с нового устройства → review
3 попытки привязки карты за 10 минут → block
Оценка товара без покупки → block + минус рейтинг

Интеграция в проект

FraudMLService::scoreOperation() вызывается в PaymentService, WalletService, RatingService, ReferralService перед любой мутацией.
Если score > порога → abort(403, 'Подозрение на мошенничество')
Все действия с score > 0.7 логируются в отдельный канал fraud_alert

Мониторинг и алерты

Ежедневный отчёт: количество заблокированных операций, средний score, топ-10 фич
Sentry-алерты при AUC < 0.85 или при > 100 блокировок за час

КАНОН ДЛЯ RecommendationService и всей системы рекомендаций (2026)
Цель системы рекомендаций
Предоставлять персонализированные, кросс-вертикальные и B2B-рекомендации товаров, услуг, поставщиков, мастеров, курсов, недвижимости и т.д. с учётом гео, поведения, истории, embeddings и бизнес-целей платформы.
Обязательные принципы (нарушение любого пункта — критическая ошибка)

RecommendationService — единственная точка генерации рекомендаций.
Никогда не генерировать рекомендации напрямую в контроллерах, страницах или Livewire-компонентах.
Все рекомендации проходят через RecommendationService с обязательным кэшированием (Redis, TTL 300–3600 сек).
correlation_id обязателен в каждом вызове и логе.
tenant_id scoping обязателен (рекомендации только внутри текущего tenant).
FraudControlService::checkRecommendation() перед выдачей списка (защита от накрутки рекомендаций).
RateLimiter на публичные эндпоинты рекомендаций (например 100 запросов/мин на пользователя).
DB::transaction() не используется (рекомендации — read-only), но кэш инвалидируется после мутаций.
Логирование: Log::channel('recommend') на каждый запрос с score, features, correlation_id.
Запрещено: return null, пустые коллекции, статические рекомендации без персонализации.

Обязательные таблицы и хранилища

user_embeddings (user_id, tenant_id, embedding vector, updated_at)
product_embeddings (product_id, tenant_id, embedding vector, updated_at)
recommendation_logs (user_id, tenant_id, correlation_id, recommended_items jsonb, score, source: behavior/geo/embedding/cross, clicked_at nullable)
recommendation_cache (key: user_id + vertical + geo_hash, value: jsonb списка рекомендаций, expires_at)
recommendation_rules (tenant_id, vertical, rule_type: boost/demote, conditions jsonb, weight float)

Обязательные методы RecommendationService

getForUser(int $userId, string $vertical = null, array $context = []): Collection
Возвращает персонализированные рекомендации с учётом всех источников.
getCrossVertical(int $userId, string $currentVertical): Collection
Кросс-рекомендации (например, после бронирования гостиницы — ресторан рядом).
getB2BForTenant(int $tenantId, string $vertical): Collection
Рекомендации поставщиков, товаров B2B, партнёров.
scoreItem(int $userId, int $itemId, array $context): float
Возвращает персональный score 0–1 для конкретного товара/услуги.
invalidateUserCache(int $userId): void
Вызывается после покупки, оценки, изменения профиля.
recalculateEmbeddings(): void
Ежедневный job для обновления всех embeddings (OpenAI или SentenceTransformers).

Источники рекомендаций (обязательный порядок приоритета)

Прямое поведение (просмотры, покупки, добавления в корзину) — вес 0.45
Географическая близость (GeoService + радиус) — вес 0.25
Embeddings similarity (cosine similarity user ↔ item) — вес 0.20
Правила бизнеса (boost/demote из recommendation_rules) — вес 0.10
Популярность в tenant (hot items) — fallback, вес 0.05

Генерация embeddings

Использовать OpenAI text-embedding-3-large или sentence-transformers/all-MiniLM-L6-v2
Текст для товара/услуги: название + описание + категория + теги + цена + рейтинг
Обновление embeddings: после создания/обновления товара + ежедневный RecalculateJob
Хранить в PostgreSQL (pgvector) или Typesense (векторный поиск)

Кэширование

Ключ: recommendation:user:{userId}:vertical:{vertical}:geo:{geoHash}:v1
TTL: 300 сек (5 мин) для динамических рекомендаций
TTL: 3600 сек (1 час) для стабильных
Инвалидация кэша после: покупка, оценка, изменение профиля, импорт товаров

Мониторинг и качество

Ежедневный Job: RecommendationQualityJob
Считает CTR (click-through rate), conversion rate, revenue lift
Логирование: Log::channel('recommend_quality') с метриками
Sentry-алерты при CTR < 5% или revenue lift < 10% за день
A/B-тестирование новых моделей (feature flag recommend_model_v2)

Безопасность и юридические аспекты

Анонимизация данных для обучения (GDPR/ФЗ-152)
Пользователь может отключить персонализацию в настройках
Нет хранения сырых персональных данных в embeddings
Все рекомендации логируются с correlation_id (можно проверить при жалобах)

Интеграция в проект

RecommendationService вызывается в:
PublicMarketplaceFacade
ProductCard.vue / BeautyShopShowcase.vue
AI-рекомендационные виджеты Filament
Email-рассылки и push-уведомления

Рекомендации отображаются в виде: "Рекомендуем именно вам", "Похожие услуги рядом", "Поставщики для вашего бизнеса"

Порог качества модели

Cosine similarity > 0.75 — высокая релевантность
CTR > 8% — хорошая модель
Revenue lift > 15% — отличная модель
Если метрики падают — откат на предыдущую версию модели

КАНОН ДЛЯ RecommendationService и всей системы рекомендаций (2026)
Цель системы рекомендаций
Предоставлять персонализированные, кросс-вертикальные и B2B-рекомендации товаров, услуг, поставщиков, мастеров, курсов, недвижимости и т.д. с учётом гео, поведения, истории, embeddings и бизнес-целей платформы.
Обязательные принципы (нарушение любого пункта — критическая ошибка)

RecommendationService — единственная точка генерации рекомендаций.
Никогда не генерировать рекомендации напрямую в контроллерах, страницах или Livewire-компонентах.
Все рекомендации проходят через RecommendationService с обязательным кэшированием (Redis, TTL 300–3600 сек).
correlation_id обязателен в каждом вызове и логе.
tenant_id scoping обязателен (рекомендации только внутри текущего tenant).
FraudControlService::checkRecommendation() перед выдачей списка (защита от накрутки рекомендаций).
RateLimiter на публичные эндпоинты рекомендаций (например 100 запросов/мин на пользователя).
DB::transaction() не используется (рекомендации — read-only), но кэш инвалидируется после мутаций.
Логирование: Log::channel('recommend') на каждый запрос с score, features, correlation_id.
Запрещено: return null, пустые коллекции, статические рекомендации без персонализации.

Обязательные таблицы и хранилища

user_embeddings (user_id, tenant_id, embedding vector, updated_at)
product_embeddings (product_id, tenant_id, embedding vector, updated_at)
recommendation_logs (user_id, tenant_id, correlation_id, recommended_items jsonb, score, source: behavior/geo/embedding/cross, clicked_at nullable)
recommendation_cache (key: user_id + vertical + geo_hash, value: jsonb списка рекомендаций, expires_at)
recommendation_rules (tenant_id, vertical, rule_type: boost/demote, conditions jsonb, weight float)

Обязательные методы RecommendationService

getForUser(int $userId, string $vertical = null, array $context = []): Collection
Возвращает персонализированные рекомендации с учётом всех источников.
getCrossVertical(int $userId, string $currentVertical): Collection
Кросс-рекомендации (например, после бронирования гостиницы — ресторан рядом).
getB2BForTenant(int $tenantId, string $vertical): Collection
Рекомендации поставщиков, товаров B2B, партнёров.
scoreItem(int $userId, int $itemId, array $context): float
Возвращает персональный score 0–1 для конкретного товара/услуги.
invalidateUserCache(int $userId): void
Вызывается после покупки, оценки, изменения профиля.
recalculateEmbeddings(): void
Ежедневный job для обновления всех embeddings (OpenAI или SentenceTransformers).

Источники рекомендаций (обязательный порядок приоритета)

Прямое поведение (просмотры, покупки, добавления в корзину) — вес 0.45
Географическая близость (GeoService + радиус) — вес 0.25
Embeddings similarity (cosine similarity user ↔ item) — вес 0.20
Правила бизнеса (boost/demote из recommendation_rules) — вес 0.10
Популярность в tenant (hot items) — fallback, вес 0.05

Генерация embeddings

Использовать OpenAI text-embedding-3-large или sentence-transformers/all-MiniLM-L6-v2
Текст для товара/услуги: название + описание + категория + теги + цена + рейтинг
Обновление embeddings: после создания/обновления товара + ежедневный RecalculateJob
Хранить в PostgreSQL (pgvector) или Typesense (векторный поиск)

Кэширование

Ключ: recommendation:user:{userId}:vertical:{vertical}:geo:{geoHash}:v1
TTL: 300 сек (5 мин) для динамических рекомендаций
TTL: 3600 сек (1 час) для стабильных
Инвалидация кэша после: покупка, оценка, изменение профиля, импорт товаров

Мониторинг и качество

Ежедневный Job: RecommendationQualityJob
Считает CTR (click-through rate), conversion rate, revenue lift
Логирование: Log::channel('recommend_quality') с метриками
Sentry-алерты при CTR < 5% или revenue lift < 10% за день
A/B-тестирование новых моделей (feature flag recommend_model_v2)

Безопасность и юридические аспекты

Анонимизация данных для обучения (GDPR/ФЗ-152)
Пользователь может отключить персонализацию в настройках
Нет хранения сырых персональных данных в embeddings
Все рекомендации логируются с correlation_id (можно проверить при жалобах)

Интеграция в проект

RecommendationService вызывается в:
PublicMarketplaceFacade
ProductCard.vue / BeautyShopShowcase.vue
AI-рекомендационные виджеты Filament
Email-рассылки и push-уведомления

Рекомендации отображаются в виде: "Рекомендуем именно вам", "Похожие услуги рядом", "Поставщики для вашего бизнеса"

Порог качества модели

Cosine similarity > 0.75 — высокая релевантность
CTR > 8% — хорошая модель
Revenue lift > 15% — отличная модель
Если метрики падают — откат на предыдущую версию модели

КАНОН ДЛЯ InventoryManagementService и всей системы управления запасами (2026)
Цель системы
Обеспечивать точный учёт, прогнозирование, автоматическое списание, пополнение и аналитику запасов товаров, материалов, расходников, медикаментов и других сущностей во всех вертикалях (Beauty, Food, Auto, Construction, Hotels и т.д.).
Обязательные принципы (нарушение любого пункта — критическая ошибка)

InventoryManagementService — единственная точка изменения остатков.
Никогда не менять остатки напрямую в контроллерах, Livewire, Jobs или моделях.
Все операции проходят через InventoryManagementService с обязательным кэшированием (Redis, TTL 60–300 сек).
correlation_id обязателен в каждом вызове, логе, событии и транзакции.
tenant_id scoping обязателен (запасы только внутри текущего tenant).
FraudControlService::checkInventoryManipulation() перед любым изменением остатков.
RateLimiter (tenant-aware) на массовые операции (импорт, списание, проверка).
DB::transaction() + lockForUpdate() для всех операций изменения остатков.
Логирование: Log::channel('inventory') на каждое движение с correlation_id, before/after, reason.
Запрещено: return null, отрицательные остатки без hold, TODO.
Все операции атомарны и идемпотентны (idempotency_key для массовых списаний/пополнений).

Обязательные таблицы

inventory_items
id
tenant_id
business_group_id (nullable)
product_id / service_id / consumable_id
current_stock (int)
hold_stock (int, default 0)
min_stock_threshold (int)
max_stock_threshold (int)
last_checked_at
correlation_id
tags (jsonb)

stock_movements
id
inventory_item_id
type (in/out/adjust/reserve/release/correction)
quantity (int, signed)
reason (text)
source_type (order, appointment, manual, import, refund и т.д.)
source_id
correlation_id
created_at
created_by (user_id или 'system')

inventory_checks
id
tenant_id
inventory_item_id
checked_stock (int)
system_stock (int)
difference (int)
status (match/discrepancy/corrected)
checked_at
checked_by
correlation_id


Обязательные методы InventoryManagementService

getCurrentStock(int $itemId): int
Возвращает current_stock с учётом hold и кэша
reserveStock(int $itemId, int $quantity, string $sourceType, int $sourceId): bool
Создаёт hold, проверяет наличие, логирует
releaseStock(int $itemId, int $quantity, string $sourceType, int $sourceId): bool
Снимает hold
deductStock(int $itemId, int $quantity, string $reason, string $sourceType, int $sourceId): bool
Списание после выполнения услуги/заказа
addStock(int $itemId, int $quantity, string $reason, string $sourceType = 'manual'): bool
Пополнение
adjustStock(int $itemId, int $newStock, string $reason, int $userId): bool
Ручная корректировка (с аудитом)
checkLowStock(): Collection
Возвращает список позиций ниже min_stock_threshold
predictRestockNeeds(int $itemId, int $daysAhead = 30): array
Прогноз потребности на основе ML (вызывает DemandForecastService)
importFromExcel(UploadedFile $file, int $tenantId): ImportResult
Массовый импорт с валидацией и логированием

Правила списания и учёта

Автоматическое списание расходников при завершении услуги/заказа (DeductAppointmentConsumables listener)
Hold при бронировании/заказе → release при отмене → deduct при выполнении
Отрицательный остаток запрещён (бросить InsufficientStockException)
При расхождении в inventory_checks — автоматическое уведомление владельцу бизнеса
Ежедневный Job: LowStockNotificationJob (отправляет уведомления, если current_stock < min_threshold)

Интеграция с другими системами

При завершении заказа/бронирования — вызов deductStock()
При поступлении оплаты — вызов releaseStock()
При импорте товаров — вызов addStock() для каждой строки
При создании рекомендации — вызов predictRestockNeeds() для товаров
При массовом платеже/выводе — проверка остатков перед списанием комиссии

Мониторинг и аналитика

Ежедневный отчёт: позиции с низким остатком, расхождения в проверках, прогноз потребности
Sentry-алерты при: отрицательный остаток, >10% расхождений в проверках, >50 уведомлений о низком остатке за день
Дашборд: тепловая карта остатков по вертикалям, график движения запасов

Безопасность и юридические аспекты

Все операции с остатками логируются 3 года (ФЗ-152)
Запрет на изменение остатков без correlation_id и user_id
Аудит всех корректировок (adjustStock) с причиной и ответственным
Инвентаризационные проверки (inventory_checks) обязательны раз в квартал

Кэширование

Ключ: inventory:stock:tenant:{tenantId}:item:{itemId}
TTL: 60 сек (динамические остатки) / 300 сек (стабильные)
Инвалидация после любого движения запасов

Порог качества системы

Точность учёта > 99.5% (расхождения < 0.5%)
Время ответа на getCurrentStock < 50 мс
Количество ложных срабатываний уведомлений о низком остатке < 5%

КАНОН ДЛЯ DemandForecastService и всей системы прогнозирования спроса (2026)
Цель системы
Предоставлять точный прогноз спроса на товары, услуги, бронирования, записи, поездки, материалы и другие сущности во всех вертикалях на горизонте 1–90 дней с учётом сезонности, гео, событий, погоды, поведения, маркетинга и внешних факторов.
Обязательные принципы (нарушение любого пункта — критическая ошибка)

DemandForecastService — единственная точка генерации прогнозов спроса.
Никогда не генерировать прогнозы напрямую в контроллерах, страницах, виджетах или уведомлениях.
Все прогнозы проходят через DemandForecastService с обязательным кэшированием (Redis, TTL 3600–86400 сек).
correlation_id обязателен в каждом вызове, логе, событии и ответе.
tenant_id scoping обязателен (прогноз только внутри текущего tenant).
FraudControlService::checkForecastManipulation() перед использованием прогноза для критичных решений (например, закупки).
RateLimiter (tenant-aware) на эндпоинты прогноза и массовые запросы.
Логирование: Log::channel('forecast') на каждый запрос с predicted_value, confidence, features, correlation_id.
Запрещено: return null, фиксированные прогнозы без модели, TODO.
Все прогнозы логируются в audit с причиной использования и точностью (post-factum).

Обязательные таблицы и хранилища

demand_forecasts
id
tenant_id
item_id / service_id / vertical
forecast_date (date)
predicted_demand (int)
confidence_interval_lower (int)
confidence_interval_upper (int)
confidence_score (float 0–1)
model_version
features_json (jsonb)
correlation_id
generated_at
used_at (nullable)

demand_actuals
id
tenant_id
item_id / service_id
date
actual_demand (int)
source (order, appointment, ride и т.д.)
correlation_id

demand_model_versions
id
version (YYYY-MM-DD-vN)
vertical
trained_at
mae
rmse
mape
file_path (storage/models/demand/...)
comment


Обязательные методы DemandForecastService

forecastForItem(int $itemId, Carbon $dateFrom, Carbon $dateTo, array $context = []): ForecastResult
Возвращает объект с predicted_demand, confidence_interval, score, features
forecastBulk(array $itemIds, Carbon $dateFrom, Carbon $dateTo): array
Массовый прогноз (для каталога/меню/склада)
getHistoricalAccuracy(string $vertical, int $days = 30): array
Возвращает MAE, RMSE, MAPE за период
trainModel(string $vertical): string
Запускает обучение и возвращает новую версию
invalidateCache(int $tenantId, int $itemId): void

Источники данных для прогноза (обязательный порядок приоритета)

Исторический спрос (demand_actuals за последние 365 дней) — вес 0.40
Сезонность и календарные факторы (день недели, месяц, праздники) — вес 0.20
Географический фактор (радиус, спрос в районе) — вес 0.15
Маркетинг и акции (PromoCampaign влияние) — вес 0.10
Погода и внешние события (температура, осадки, спортивные события) — вес 0.10
Поведение пользователей (поисковые запросы, просмотры) — вес 0.05

Генерация признаков (features)

Лаговые значения спроса (lag1, lag7, lag30)
День недели, месяц, квартал
Праздничный коэффициент (1–3)
Температура и осадки (через внешний API)
Количество активных промо в день
Количество просмотров/поисков товара
Средняя конверсия в районе
Количество аналогичных товаров у конкурентов
Курс валют (если импорт)

Обучение модели

Запускается ежедневно в 04:30 по UTC (DemandModelTrainJob)
Данные: demand_actuals + внешние источники + маркетинг
Целевая переменная: actual_demand за день
Модель: XGBoost / Prophet / LSTM (с учётом сезонности)
Метрики: MAPE < 15%, MAE < 10% от среднего спроса
Логирование результатов в demand_model_versions
Автоматическое переключение на новую модель при MAPE улучшении > 5%

Кэширование

Ключ: demand_forecast:tenant:{tenantId}:item:{itemId}:date:{date}:v1
TTL: 3600 сек (1 час) для ближайших 7 дней
TTL: 86400 сек (1 день) для горизонта > 7 дней
Инвалидация: после создания заказа, изменения цены, новой акции

Безопасность и юридические аспекты

Данные для обучения анонимизируются (GDPR/ФЗ-152)
Прогноз не используется для автоматического изменения цен без подтверждения
Все прогнозы логируются с correlation_id (можно проверить при спорах)
Бизнес может отключить прогноз в настройках

Мониторинг и алерты

Ежедневный отчёт: средний MAPE, количество прогнозов, топ-10 самых неточных позиций
Sentry-алерты при MAPE > 25% или при > 100 прогнозов с confidence < 0.6 за день
A/B-тестирование новых моделей (feature flag demand_model_v2)

Интеграция в проект

DemandForecastService вызывается в:
InventoryManagementService (прогноз потребности)
PriceSuggestionService (учёт спроса в цене)
Дашборд бизнеса (виджет "Прогноз спроса")
Уведомления о низком остатке (если прогноз показывает рост спроса)
Рекомендации товаров (приоритет позициям с высоким прогнозом)


Порог качества модели

MAPE < 15% (средняя абсолютная процентная ошибка)
MAE < 10% от среднего спроса
Coverage > 95% (прогноз генерируется для >95% позиций)
Если метрики падают — откат на предыдущую версию

КАНОН ДЛЯ PromoCampaignService и системы промо-кампаний (2026)
Цель системы
Управлять всеми видами акций, скидок, промокодов, бандлов, flash-sale, реферальных бонусов, подарочных сертификатов и маркетинговых кампаний с автоматическим применением, контролем бюджета, аналитикой эффективности, защитой от злоупотреблений и соответствием законодательству (ФЗ-38 «О рекламе», 54-ФЗ, ФЗ-152).
Обязательные принципы (нарушение любого пункта — критическая ошибка)

PromoCampaignService — единственная точка создания, управления и применения промо-кампаний.
Никогда не применять скидки/промокоды напрямую в контроллерах, корзине или оплате.
Все операции проходят через PromoCampaignService с обязательным кэшированием (Redis, TTL 300–86400 сек).
correlation_id обязателен в каждом вызове, логе, событии и транзакции.
tenant_id scoping обязателен (промо только внутри текущего tenant).
FraudControlService::checkPromoAbuse() перед применением любой акции.
RateLimiter (tenant-aware) на применение промокодов (например 50 попыток/мин).
DB::transaction() для всех операций применения/отмены/списания бюджета.
Логирование: Log::channel('promo') на создание, применение, отмену, исчерпание бюджета.
Запрещено: return null, автоматическое применение без проверки, TODO.
Все промо-кампании логируются 3 года (ФЗ-38, ФЗ-152).

Обязательные таблицы

promo_campaigns
id
tenant_id
business_group_id (nullable)
type (discount_percent, fixed_amount, bundle, buy_x_get_y, gift_card, referral_bonus)
code (string, unique)
name
description
start_at
end_at
budget (int, копейки)
spent_budget (int)
max_uses_per_user
max_uses_total
min_order_amount
applicable_verticals (jsonb)
applicable_categories (jsonb)
status (active, paused, exhausted, expired)
correlation_id
created_by
created_at

promo_uses
id
promo_campaign_id
tenant_id
user_id
order_id / appointment_id / source_id
discount_amount (int)
correlation_id
used_at

promo_audit_logs
id
promo_campaign_id
action (created, applied, cancelled, budget_exhausted)
user_id
details (jsonb)
correlation_id
created_at


Обязательные методы PromoCampaignService

createCampaign(array $data, int $tenantId, int $userId): PromoCampaign
Создаёт кампанию с валидацией и бюджетом
applyPromo(string $code, Cart|Order $cart): DiscountResult
Проверяет, применяет скидку, списывает бюджет, логирует
validatePromo(string $code, Cart|Order $cart): ValidationResult
Проверяет применимость без применения
cancelPromoUse(int $useId, int $userId): bool
Отмена применения (возврат бюджета)
checkBudgetExhausted(int $campaignId): bool
getActiveCampaigns(int $tenantId, string $vertical = null): Collection
recalculateBudgetUsage(int $campaignId): void
Пересчитывает spent_budget из promo_uses

Типы промо-кампаний (обязательные)

discount_percent (5–50%)
fixed_amount (фиксированная сумма)
buy_x_get_y (2+1, 1+1 и т.д.)
bundle (набор товаров по сниженной цене)
gift_card (подарочные сертификаты +3% к номиналу)
referral_bonus (1000 руб за приглашённого клиента после 10 000 руб траты)
turnover_bonus (после 50 000 оборота — 2000 руб бонусом)

Правила применения

Автоматическое применение промокода при достижении min_order_amount
Проверка max_uses_per_user и max_uses_total
Списание бюджета в реальном времени (DB::transaction)
Возврат бюджета при отмене заказа/бронирования
Интеграция с Wallet: бонусы зачисляются на wallet_balance
Ограничение по вертикалям и категориям (jsonb)

Интеграция в проект

PromoCampaignService вызывается в:
Корзина / checkout (applyPromo)
Создание заказа/бронирования (validatePromo)
Wishlist-оплата (применение подарочных сертификатов)
Реферальная система (referral_bonus)
Дашборд бизнеса (виджет "Активные акции")


Мониторинг и аналитика

Ежедневный отчёт: бюджет израсходован, количество применений, ROI (возврат инвестиций)
Sentry-алерты при: >90% бюджета за 24 ч, >50 попыток применения несуществующего кода
Дашборд: конверсия по промо, топ-10 самых эффективных акций

Безопасность и юридические аспекты

Все промо-кампании подлежат маркировке (ФЗ-38 «О рекламе») при публичном отображении
Ограничение на количество применений одного промокода одним пользователем
Аудит всех применений и отмен (3 года хранения)
Запрет на применение промо к уже оплаченным заказам

Кэширование

Ключ: promo:active:tenant:{tenantId}:vertical:{vertical}
TTL: 300 сек (динамические акции) / 86400 сек (долгосрочные)
Инвалидация: после создания/обновления/исчерпания кампании

Порог качества системы

Конверсия по промо > 18%
ROI > 150% (возврат инвестиций)
Количество злоупотреблений < 0.5% от применений
Время проверки промокода < 100 мс

КАНОН ДЛЯ PromoCampaignService и системы промо-кампаний (2026)
Цель системы
Управлять всеми видами акций, скидок, промокодов, бандлов, flash-sale, реферальных бонусов, подарочных сертификатов и маркетинговых кампаний с автоматическим применением, контролем бюджета, аналитикой эффективности, защитой от злоупотреблений и соответствием законодательству (ФЗ-38 «О рекламе», 54-ФЗ, ФЗ-152).
Обязательные принципы (нарушение любого пункта — критическая ошибка)

PromoCampaignService — единственная точка создания, управления и применения промо-кампаний.
Никогда не применять скидки/промокоды напрямую в контроллерах, корзине или оплате.
Все операции проходят через PromoCampaignService с обязательным кэшированием (Redis, TTL 300–86400 сек).
correlation_id обязателен в каждом вызове, логе, событии и транзакции.
tenant_id scoping обязателен (промо только внутри текущего tenant).
FraudControlService::checkPromoAbuse() перед применением любой акции.
RateLimiter (tenant-aware) на применение промокодов (например 50 попыток/мин).
DB::transaction() для всех операций применения/отмены/списания бюджета.
Логирование: Log::channel('promo') на создание, применение, отмену, исчерпание бюджета.
Запрещено: return null, автоматическое применение без проверки, TODO.
Все промо-кампании логируются 3 года (ФЗ-38, ФЗ-152).

Обязательные таблицы

promo_campaigns
id
tenant_id
business_group_id (nullable)
type (discount_percent, fixed_amount, bundle, buy_x_get_y, gift_card, referral_bonus)
code (string, unique)
name
description
start_at
end_at
budget (int, копейки)
spent_budget (int)
max_uses_per_user
max_uses_total
min_order_amount
applicable_verticals (jsonb)
applicable_categories (jsonb)
status (active, paused, exhausted, expired)
correlation_id
created_by
created_at

promo_uses
id
promo_campaign_id
tenant_id
user_id
order_id / appointment_id / source_id
discount_amount (int)
correlation_id
used_at

promo_audit_logs
id
promo_campaign_id
action (created, applied, cancelled, budget_exhausted)
user_id
details (jsonb)
correlation_id
created_at


Обязательные методы PromoCampaignService

createCampaign(array $data, int $tenantId, int $userId): PromoCampaign
Создаёт кампанию с валидацией и бюджетом
applyPromo(string $code, Cart|Order $cart): DiscountResult
Проверяет, применяет скидку, списывает бюджет, логирует
validatePromo(string $code, Cart|Order $cart): ValidationResult
Проверяет применимость без применения
cancelPromoUse(int $useId, int $userId): bool
Отмена применения (возврат бюджета)
checkBudgetExhausted(int $campaignId): bool
getActiveCampaigns(int $tenantId, string $vertical = null): Collection
recalculateBudgetUsage(int $campaignId): void
Пересчитывает spent_budget из promo_uses

Типы промо-кампаний (обязательные)

discount_percent (5–50%)
fixed_amount (фиксированная сумма)
buy_x_get_y (2+1, 1+1 и т.д.)
bundle (набор товаров по сниженной цене)
gift_card (подарочные сертификаты +3% к номиналу)
referral_bonus (1000 руб за приглашённого клиента после 10 000 руб траты)
turnover_bonus (после 50 000 оборота — 2000 руб бонусом)

Правила применения

Автоматическое применение промокода при достижении min_order_amount
Проверка max_uses_per_user и max_uses_total
Списание бюджета в реальном времени (DB::transaction)
Возврат бюджета при отмене заказа/бронирования
Интеграция с Wallet: бонусы зачисляются на wallet_balance
Ограничение по вертикалям и категориям (jsonb)

Интеграция в проект

PromoCampaignService вызывается в:
Корзина / checkout (applyPromo)
Создание заказа/бронирования (validatePromo)
Wishlist-оплата (применение подарочных сертификатов)
Реферальная система (referral_bonus)
Дашборд бизнеса (виджет "Активные акции")


Мониторинг и аналитика

Ежедневный отчёт: бюджет израсходован, количество применений, ROI (возврат инвестиций)
Sentry-алерты при: >90% бюджета за 24 ч, >50 попыток применения несуществующего кода
Дашборд: конверсия по промо, топ-10 самых эффективных акций

Безопасность и юридические аспекты

Все промо-кампании подлежат маркировке (ФЗ-38 «О рекламе») при публичном отображении
Ограничение на количество применений одного промокода одним пользователем
Аудит всех применений и отмен (3 года хранения)
Запрет на применение промо к уже оплаченным заказам

Кэширование

Ключ: promo:active:tenant:{tenantId}:vertical:{vertical}
TTL: 300 сек (динамические акции) / 86400 сек (долгосрочные)
Инвалидация: после создания/обновления/исчерпания кампании

Порог качества системы

Конверсия по промо > 18%
ROI > 150% (возврат инвестиций)
Количество злоупотреблений < 0.5% от применений
Время проверки промокода < 100 мс

КАНОН ДЛЯ ReferralService и всей реферальной системы (2026)
Цель системы
Создавать, отслеживать и начислять реферальные бонусы и вознаграждения за приглашение новых пользователей/бизнесов с учётом оборота, траты, миграции и правил платформы. Система должна быть прозрачной, защищённой от злоупотреблений и юридически безопасной (ФЗ-152, ФЗ-38, антимонопольное законодательство).
Обязательные принципы (нарушение любого пункта — критическая ошибка)

ReferralService — единственная точка создания, проверки и начисления реферальных бонусов.
Никогда не начислять бонусы напрямую в контроллерах, событиях или jobs.
Все операции проходят через ReferralService с обязательным кэшированием (Redis, TTL 300–86400 сек).
correlation_id обязателен в каждом вызове, логе, событии и транзакции.
tenant_id scoping обязателен для бизнес-рефералов.
FraudControlService::checkReferralAbuse() перед каждым начислением.
RateLimiter (tenant-aware и user-aware) на генерацию ссылок и применение рефералов.
DB::transaction() для всех начислений/выводов бонусов.
Логирование: Log::channel('referral') на генерацию ссылки, регистрацию, трату, начисление.
Запрещено: return null, автоматическое начисление без проверки оборота, TODO.
Все начисления бонусов логируются 3 года (ФЗ-152).
Запрещено требовать полного перехода с других платформ (риск ФАС) — фиксировать только факт миграции добровольно.

Обязательные таблицы

referrals
id
referrer_id (user_id или tenant_id)
referee_id (приглашённый user_id или tenant_id)
referral_code (string, unique)
referral_link (string)
status (pending, registered, qualified, rewarded, expired)
source_platform (Yandex Путешествия, Dikidi, Flowwow и т.д., nullable)
migrated_at (timestamp nullable)
turnover_threshold (int)
spent_threshold (int)
bonus_amount (int, копейки)
correlation_id
created_at

referral_rewards
id
referral_id
recipient_type (referrer/referee)
recipient_id (user_id или tenant_id)
amount (int)
type (referral_bonus, turnover_bonus, migration_bonus)
status (pending, credited, withdrawn)
credited_at
withdrawn_at (nullable)
correlation_id

referral_audit_logs
id
referral_id
action (link_generated, user_registered, turnover_reached, bonus_credited)
details (jsonb)
user_id / tenant_id
correlation_id
created_at


Обязательные методы ReferralService

generateReferralLink(int $referrerId, string $type = 'user'): string
Создаёт уникальный код и ссылку
registerReferral(string $code, int $newUserId): bool
Регистрирует приглашённого и проверяет факт миграции
checkQualification(int $referralId): QualificationResult
Проверяет, достигнут ли оборот/трата для начисления
awardBonus(int $referralId, int $recipientId): bool
Начисляет бонус на Wallet после квалификации
getReferralStats(int $referrerId): ReferralStats
Возвращает количество приглашённых, обороты, бонусы
validateMigration(int $tenantId, string $sourcePlatform, UploadedFile $proof = null): MigrationConfirmation
Фиксирует факт перехода с другой платформы

Правила начисления бонусов

После 50 000 ₽ оборота приглашённого бизнеса — 2000 ₽ бонус рефереру
Приглашение клиента (физлицо): 1000 ₽ рефереру после траты 10 000 ₽ приглашённым
Бизнес-реферал: 500 ₽ на баланс пригласившему бизнесу
Переход с Яндекс/Островок/ТПутешествия и т.д. — пониженная комиссия 12% на 2 года
Переход с Flowwow (цветы) — 10% первые 4 месяца, затем 12% на 2 года
Переход с Dikidi (бьюти) — 10% первые 4 месяца, затем 12% на 2 года
Бонусы реферера (физлицо) — только тратятся внутри платформы
Бонусы бизнеса — можно выводить

Интеграция в проект

ReferralService вызывается в:
Регистрация нового пользователя/бизнеса
Checkout / оплата (проверка реферального кода)
Достижение оборота/траты (TurnoverReachedEvent)
Подтверждение миграции (Tenant settings)
Дашборд бизнеса (виджет "Реферальная программа")


Мониторинг и аналитика

Ежедневный отчёт: количество новых рефералов, обороты, начисленные бонусы, ROI
Sentry-алерты при: >50% отказов по квалификации, >10 подозрительных регистраций с одного IP
Дашборд: топ-рефереров, конверсия по источникам миграции

Безопасность и юридические аспекты

Не требовать полного отключения от старых платформ (риск ФАС)
Фиксировать только добровольное подтверждение миграции (скриншот/письмо)
Запрет на накрутку рефералов (FraudControlService + ML-скоринг)
Аудит всех начислений и выводов (3 года хранения)
Пользователь может отключить реферальную программу в настройках

Кэширование

Ключ: referral:stats:referrer:{referrerId}
TTL: 3600 сек (1 час)
Инвалидация: после регистрации нового реферала, достижения оборота

Порог качества системы

Конверсия рефералов в квалификацию > 25%
ROI реферальной программы > 300%
Количество злоупотреблений < 0.3% от регистраций
Время проверки реферального кода < 100 мс

Канон для вертикали «Красота / Бьюти / Мастера / Салоны» (Beauty & Wellness)
Название домена
app/Domains/Beauty
Основные сущности (модели)

BeautySalon
Master (мастер, привязан к салону или работает самостоятельно)
Service (услуга: стрижка, маникюр, массаж и т.д.)
Appointment (запись на услугу)
BeautyProduct (товары для продажи в салоне: косметика, инструменты)
Consumable (расходники: перчатки, краска, полотенца — списываются автоматически)
PortfolioItem (фото работ мастера)
Review (отзывы после визита)

Ключевые таблицы / поля

beauty_salons: name, address, geo_point (point), schedule_json, rating, review_count, is_verified
masters: salon_id (nullable), full_name, specialization (jsonb), experience_years, rating
services: master_id / salon_id, name, duration_minutes, price, consumables_json (список расходников и кол-во)
appointments: salon_id, master_id, service_id, client_id, datetime_start, status (pending/confirmed/completed/cancelled), price, payment_status
beauty_products: salon_id, name, sku, current_stock, price, consumable_type (enum: none, low, medium, high расход)

Автоматические процессы

При завершении appointment → автоматическое списание consumables (DeductAppointmentConsumables listener)
При создании appointment → hold_stock на расходники (если хватает)
При отмене в пределах 24 ч → release_stock
При достижении min_stock_threshold → уведомление владельцу салона (LowStockNotification)
Ежедневный прогноз потребности расходников (DemandForecastService)
Автоматическое формирование графика мастера (StaffScheduleService)

Комиссия платформы

Стандарт: 14%
Переход с Dikidi: 10% первые 4 месяца → 12% следующие 24 месяца
Бонус за миграцию: фиксируется в CommissionRule

Особенности вертикали

Онлайн-примерка причёсок/макияжа (BeautyTryOnService)
Портфолио мастера с фото до/после
Тепловая карта загруженности салона/мастера
Автоматическое напоминание клиенту за 24 ч и за 2 ч до записи
Интеграция с видеозвонком для онлайн-консультаций

UI/UX канон

Карточка мастера: фото, рейтинг, специализация, ближайшее свободное время
Календарь записи: FullCalendar с занятыми слотами
Фильтры: цена, длительность, рейтинг, специализация, пол мастера
После визита: обязательная форма отзыва + фото до/после


Канон для вертикали «Автомобили / Такси / Автосервис / Мойка / Тюнинг» (Auto & Mobility)
Название домена
app/Domains/Auto
Основные сущности (модели)

TaxiDriver
TaxiVehicle
TaxiFleet (автопарк)
TaxiRide (поездка)
TaxiSurgeZone (зона повышенного спроса)
AutoPart (запчасть)
AutoService (услуга СТО: замена масла, шиномонтаж и т.д.)
AutoRepairOrder (заказ-наряд в СТО)
CarWashBooking (бронь мойки)
TuningProject (проект тюнинга)

Ключевые таблицы / поля

taxi_drivers: user_id, license_number, rating, current_location (point), is_active
taxi_vehicles: driver_id / fleet_id, brand, model, license_plate, class (economy/comfort/business), status
taxi_rides: passenger_id, driver_id, vehicle_id, pickup_point (point), dropoff_point, status, price, surge_multiplier
auto_parts: sku, name, brand, current_stock, price
auto_service_orders: client_id, car_id, service_type, status, total_price, appointment_datetime

Автоматические процессы

Surge pricing: TaxiSurgeService пересчитывает коэффициент каждые 5 минут по гео-зонам
Автоматическое списание запчастей при завершении ремонта (DeductRepairParts listener)
Прогноз спроса такси (DemandForecastService) → автоматическая активация surge
При создании заказа на СТО → hold_stock на запчасти
При отмене за 24 ч → release_stock
Ежедневный пересчёт рейтинга водителя/мастера СТО

Комиссия платформы

Стандарт: 15% + 5% автопарку (если автопарк есть)
Если водитель СЗ (самозанятый) без автопарка — 17.5%
Переход с Яндекс.Такси / Uber → фиксируется миграция, но комиссия не снижается

Особенности вертикали

GPS-трекинг поездок (Glonass / собственный трекер)
Тепловая карта спроса такси (GeoHeatmapWidget)
Автоматический расчёт маршрута и цены (OSRM / Yandex Maps API)
Онлайн-бронь мойки / СТО с выбором времени и бокса
Тюнинг-проекты с этапами и фото до/после

UI/UX канон

Карточка поездки: карта маршрута, водитель, авто, цена, статус
Фильтры такси: класс авто, цена, рейтинг водителя
Календарь записи на СТО/мойку с занятыми слотами
Профиль водителя/мастера СТО: рейтинг, отзывы, фото работ

Канон для вертикали «Еда / Рестораны / Кофейни / Доставка» (Food & Delivery)
Название домена
app/Domains/Food
Основные сущности (модели)

Restaurant (ресторан / кафе / кофейня / кулинария / столовая)
RestaurantMenu
Dish (блюдо)
DishVariant (вариант блюда: размер, добавки)
RestaurantOrder
RestaurantTable (столик)
DeliveryOrder (доставка)
DeliveryZone
KDSOrder (Kitchen Display System — кухонный заказ)

Ключевые таблицы / поля

restaurants: name, address, geo_point, cuisine_type (jsonb), schedule_json, rating, is_verified
restaurant_menus: restaurant_id, name, is_active
dishes: menu_id, name, price, calories, allergens (jsonb), cooking_time_minutes, consumables_json
restaurant_orders: restaurant_id, table_id (nullable), client_id, status (pending, cooking, ready, delivered, cancelled), total_price, payment_status
delivery_orders: restaurant_id, client_id, address, geo_point, delivery_price, status, courier_id
delivery_zones: restaurant_id, polygon (geometry), surge_multiplier

Автоматические процессы

При создании заказа → автоматическое списание ингредиентов (DeductOrderConsumables listener)
При отмене заказа до приготовления → release_stock
KDS: автоматическая передача заказа на кухню после оплаты
Surge pricing для доставки: пересчёт каждые 5 минут по зонам (DeliverySurgeService)
Прогноз спроса блюд (DemandForecastService) → уведомление о необходимости закупки
Автоматическое закрытие заказа через 2 часа после статуса "ready" (если не подтверждено вручную)

Комиссия платформы

Стандарт: 14%
Переход с Delivery Club / Яндекс.Еда → фиксируется миграция, но комиссия не снижается (только бонус за оборот)

Особенности вертикали

QR-меню для столиков
KDS-монитор (Kitchen Display System) в реальном времени
Тепловая карта спроса по блюдам и зонам доставки
Автоматическое формирование чека ОФД при оплате
Интеграция с агрегаторами (если бизнес подключён)

UI/UX канон

Карточка блюда: фото, калорийность, аллергены, время приготовления, рейтинг
Календарь брони столиков + карта свободных мест
Фильтры: кухня, цена, калорийность, аллергены, веган/постное
Корзина с возможностью добавления комментария к блюду
Трекинг доставки в реальном времени (карта)


Канон для вертикали «Недвижимость / Аренда / Продажа» (Real Estate & Rentals)
Название домена
app/Domains/RealEstate
Основные сущности (модели)

Property (жилая/коммерческая недвижимость)
LandPlot (земельный участок)
RentalListing (долгосрочная аренда)
SaleListing (продажа)
ReadyBusiness (готовый бизнес на продажу)
ViewingAppointment (просмотр объекта)
RealEstateAgent (агент/риелтор)
MortgageApplication (заявка на ипотеку, если интегрировано)

Ключевые таблицы / поля

properties: owner_id (tenant_id), address, geo_point, type (apartment/house/land/commercial), area, rooms, floor, price, status (active/sold/rented)
rental_listings: property_id, rent_price_month, deposit, lease_term_min, lease_term_max
sale_listings: property_id, sale_price, commission_percent
viewing_appointments: property_id, client_id, agent_id, datetime, status
real_estate_agents: tenant_id, license_number, rating

Автоматические процессы

При бронировании просмотра → hold депозита (если требуется)
При подписании договора аренды/продажи → списание комиссии платформы
Прогноз спроса по району (DemandForecastService) → автоматическое поднятие/опускание цены
Автоматическое закрытие объявления после статуса sold/rented
Ежемесячный отчёт владельцу объекта (аренда/продажа)

Комиссия платформы

Стандарт: 14% от суммы сделки (аренда — от первого платежа)
Переход с ЦИАН / Авито / Домклик → фиксируется миграция, комиссия не снижается

Особенности вертикали

Интерактивная 3D-карта объектов
Онлайн-тур по объекту (360° фото или видео)
Автоматический расчёт ипотеки (интеграция с банками)
Проверка юридической чистоты (интеграция с Росреестром API, если доступно)
Тепловая карта спроса по районам

UI/UX канон

Карточка объекта: фото, цена за м², площадь, этаж, ремонт, рейтинг
Фильтры: тип, цена, площадь, район, этаж, ремонт, ипотека
Календарь просмотров с занятыми слотами
Профиль агента: рейтинг, отзывы, количество сделок
После просмотра: форма отзыва + рейтинг агента/объекта 



Дополнительные правила

Все новые файлы — UTF-8 no BOM + CRLF
Все существующие файлы привести к этим правилам
Если файл пустой — заменить на реальную логику или удалить
Никаких новых вертикалей без команды

Нарушение любого пункта — критическая ошибка.

КАНОН ДЛЯ SECURITY (2026) — PRODUCTION-READY
✅ COMPLETED: Все 12 критических и high-risk уязвимостей исправлены

Обязательные компоненты безопасности:

Core Services (обязательны):
- IdempotencyService::check() — проверка повтора платежей (SHA-256 payload hash)
- WebhookSignatureService::verify() — валидация вебхуков (HMAC-SHA256, сертификаты OpenSSL)
- RateLimiterService — sliding window algorithm с burst protection
- IpWhitelistMiddleware — фильтрация IP с поддержкой CIDR notation

Request Validation (обязательна для всех API endpoints):
- Все POST/PUT endpoints должны наследовать BaseApiRequest
- FormRequest classes: PaymentInitRequest, PromoApplyRequest, ReferralClaimRequest
- failedValidation() возвращает JSON 422 с correlation_id

Exception Handling (обязательны):
- DuplicatePaymentException (409 Conflict)
- InvalidPayloadException (400 Bad Request)
- RateLimitException (429 Too Many Requests с Retry-After header)

Middleware Chain (обязательна для критичных endpoints):
- 'auth:sanctum' — API authentication
- 'rate-limit-payment' — 10 req/min для платежей
- 'rate-limit-promo' — 50 req/min для промо
- 'rate-limit-search' — 1000 light/100 heavy в час для поиска
- 'ip-whitelist' — только для вебхуков от платежных систем

RBAC (Role-Based Access Control — обязательна):
- Все модели должны иметь соответствующие Policies
- Все routes должны проверять authorize() в FormRequest
- Roles: admin, business_owner, manager, accountant, employee
- Abilities: view_dashboard, manage_employees, manage_payroll и т.д.

Configuration (обязательна):
- config/security.php — IP whitelists, webhook secrets, rate limits
- config/rbac.php — roles и abilities
- .env variables: WEBHOOK_SECRET_*, WEBHOOK_IP_WHITELIST

Testing (обязательны):
- Каждый security test должен использовать SecurityIntegrationTest base
- Проверять: idempotency, rate limiting, signature verification, policy authorization
- Не допускать false negatives (пропуск атак) — critical failure

Logging (обязательно):
- Все security события в Log::channel('audit') с correlation_id
- Rate limit violations в Log::channel('fraud_alert')
- Webhook signature failures в Log::channel('webhook_errors')

Deployment (обязательна):
- php artisan migrate (для api_keys table)
- Настроить webhook secrets в .env для каждой платежной системы
- Заполнить WEBHOOK_IP_WHITELIST для IP платежных систем
- Запустить queue:work для CleanupExpiredIdempotencyRecordsJob

Запрещено:
- Пропускать rate limiting на public endpoints
- Использовать фиксированный rate limit window (только sliding window)
- Хранить webhook secrets в коде (только в .env или AWS Secrets Manager)
- Возвращать sensitive errors пользователю (только internal logging)
- Игнорировать correlation_id в любом security-related логе

Next Phase (Week 2+):
- API Versioning (/api/v1/, /api/v2/) с middleware поддержкой
- OpenAPI/Swagger documentation с L5-Swagger
- Advanced ML-based fraud scoring с DemandForecastService
- PCI-DSS compliance audit

КАНОН ВЕРТИКАЛЕЙ 2026 — ПОЛНЫЙ СПИСОК И ТРЕБОВАНИЯ
Общее правило размещения

Бизнес-логика, модели, сервисы, события → app/Domains/{VerticalName}/
Публичная витрина, корзина, Livewire → modules/Marketplace/{VerticalName}/
Админка и ЛК бизнеса → app/Filament/Tenant/Resources/{VerticalName}Resource/
Миграции — плоско в database/migrations/ (без подпапок)
Фабрики и сиды — по канону (tenant_id, uuid, correlation_id, faker)

Глобальные требования ко всем вертикалям

Все модели имеют: uuid, tenant_id, business_group_id (nullable), correlation_id, tags (jsonb)
booted() с tenant + business_group scoping
Все сервисы: constructor injection, DB::transaction(), audit-лог, fraud-check, rate-limit
Комиссия платформы: 14% стандарт, пониженная при миграции (10–12%)
Интеграция с Wallet, Payment, FraudML, Recommendation, DemandForecast, Inventory
UI: glassmorphism, dark mode, mobile-first, Tailwind + Livewire/Vue


1. Auto (Авто / Такси / Мойка / Тюнинг / Автосервис)
Путь: app/Domains/Auto/
Статус: Полная (22 компонента)
Модели: TaxiDriver, TaxiVehicle, TaxiFleet, TaxiRide, TaxiSurgeZone, AutoPart, AutoService, AutoRepairOrder, CarWashBooking, TuningProject
Сервисы: TaxiService, SurgePricingService, AutoService, InventoryManagement (запчасти)
Процессы: Surge каждые 5 мин, списание запчастей при ремонте, hold при бронировании мойки
Комиссия: 15% + 5% автопарку / 17.5% самозанятым
Особенности: GPS-трекинг, тепловая карта спроса, онлайн-бронь мойки/СТО
UI: Карта маршрута, профиль водителя, календарь записи на СТО
2. Beauty (Красота / Салоны / Мастера)
Путь: app/Domains/Beauty/
Статус: 52% — критично доработать
Модели: BeautySalon, Master, Service, Appointment, BeautyProduct, Consumable, PortfolioItem, Review
Сервисы: BeautyService, AppointmentService, ConsumableDeductionService, PortfolioService
Процессы: Списание расходников при завершении услуги, hold при записи, напоминания за 24 ч и 2 ч
Комиссия: 14% / 10% первые 4 мес с Dikidi → 12% следующие 24 мес
Особенности: Онлайн-примерка причёсок/макияжа, портфолио до/после, тепловая карта загруженности
UI: Карточка мастера (фото, рейтинг, свободное время), календарь записи, фильтры по специализации
3. Food (Рестораны / Доставка / Кофейни)
Путь: app/Domains/Food/
Статус: Полная
Модели: Restaurant, RestaurantMenu, Dish, DishVariant, RestaurantOrder, RestaurantTable, DeliveryOrder, DeliveryZone, KDSOrder
Сервисы: RestaurantService, OrderService, DeliveryService, KDSService
Процессы: Списание ингредиентов при заказе, KDS на кухню, surge для доставки, QR-меню
Комиссия: 14%
Особенности: KDS, QR-меню, трекинг доставки, ОФД при оплате
UI: Карточка блюда (калории, аллергены), корзина, трекинг заказа
4. Hotels (Гостиницы / Отели)
Путь: app/Domains/Hotels/
Статус: Полная
Модели: Hotel, RoomType, Booking, HotelRoomInventory, Review, PayoutSchedule
Сервисы: HotelService, BookingService, InventoryManagement (номера)
Процессы: Выплата через 4 дня после выселения, hold при бронировании
Комиссия: 14% / 12% при миграции с Островок/Booking
Особенности: Календарь занятости, онлайн-тур 360°, интеграция с PMS
UI: Карточка отеля, календарь бронирования, фильтры по звёздам/удобствам
5. RealEstate (Недвижимость / Аренда / Продажа)
Путь: app/Domains/RealEstate/
Статус: Полная
Модели: Property, RentalListing, SaleListing, LandPlot, ViewingAppointment, RealEstateAgent
Сервисы: PropertyService, RentalService, SaleService
Процессы: Hold депозита при просмотре, списание комиссии после сделки
Комиссия: 14% от суммы сделки
Особенности: 3D-тур, расчёт ипотеки, проверка Росреестра
UI: Карточка объекта, фильтры по району/этажу, профиль агента
6. Игрушки и товары для детей (Toys & Kids) — потерянная вертикаль
Путь: app/Domains/ToysKids/
Модели: ToyProduct, ToyCategory, ToyOrder, ToyReview, ToyWishlistItem
Сервисы: ToyService, OrderService, InventoryManagement
Процессы: Автоматическое списание при заказе, прогноз спроса на сезон
Комиссия: 14%
Особенности: Возрастная категория, сертификаты безопасности, подарочная упаковка
UI: Яркие карточки, фильтры по возрасту/полу, вишлист-подарки
7. Электроника и гаджеты (Electronics)
Путь: app/Domains/Electronics/
Модели: ElectronicProduct, ElectronicCategory, ElectronicOrder, ElectronicReview
Сервисы: ElectronicService, WarrantyService
Процессы: Гарантийные случаи, возврат в течение 14 дней
Комиссия: 14%
Особенности: Сравнение характеристик, обзоры, интеграция
UI: Таблицы характеристик, фильтры по бренду/цене/рейтингу
8. Мебель и интерьер (Furniture & Interior)
Путь: app/Domains/Furniture/
Модели: FurnitureItem, FurnitureCategory, FurnitureOrder, InteriorDesignProject
Сервисы: FurnitureService, InteriorAI (AI-дизайн)
Процессы: 3D-визуализация, доставка + сборка
Комиссия: 14%
Особенности: AI-конструктор интерьера, доставка крупногабаритных товаров
UI: 3D-просмотр, фильтры по стилю/материалу

КАНОН ВЕРТИКАЛЕЙ 2026 — ПОЛНЫЙ СПИСОК И ТРЕБОВАНИЯ
Общее правило размещения

Бизнес-логика, модели, сервисы, события → app/Domains/{VerticalName}/
Публичная витрина, корзина, Livewire → modules/Marketplace/{VerticalName}/
Админка и ЛК бизнеса → app/Filament/Tenant/Resources/{VerticalName}Resource/
Миграции — плоско в database/migrations/ (без подпапок)
Фабрики и сиды — по канону (tenant_id, uuid, correlation_id, faker)

Глобальные требования ко всем вертикалям

Все модели имеют: uuid, tenant_id, business_group_id (nullable), correlation_id, tags (jsonb)
booted() с tenant + business_group scoping
Все сервисы: constructor injection, DB::transaction(), audit-лог, fraud-check, rate-limit
Комиссия платформы: 14% стандарт, пониженная при миграции (10–12%)
Интеграция с Wallet, Payment, FraudML, Recommendation, DemandForecast, Inventory
UI: glassmorphism, dark mode, mobile-first, Tailwind + Livewire/Vue


1. Auto (Авто / Такси / Мойка / Тюнинг / Автосервис)
Путь: app/Domains/Auto/
Статус: Полная (22 компонента)
Модели: TaxiDriver, TaxiVehicle, TaxiFleet, TaxiRide, TaxiSurgeZone, AutoPart, AutoService, AutoRepairOrder, CarWashBooking, TuningProject
Сервисы: TaxiService, SurgePricingService, AutoService, InventoryManagement (запчасти)
Процессы: Surge каждые 5 мин, списание запчастей при ремонте, hold при бронировании мойки
Комиссия: 15% + 5% автопарку / 17.5% самозанятым
Особенности: GPS-трекинг, тепловая карта спроса, онлайн-бронь мойки/СТО
UI: Карта маршрута, профиль водителя, календарь записи на СТО
2. Beauty (Красота / Салоны / Мастера)
Путь: app/Domains/Beauty/
Статус: 52% — критично доработать
Модели: BeautySalon, Master, Service, Appointment, BeautyProduct, Consumable, PortfolioItem, Review
Сервисы: BeautyService, AppointmentService, ConsumableDeductionService, PortfolioService
Процессы: Списание расходников при завершении услуги, hold при записи, напоминания за 24 ч и 2 ч
Комиссия: 14% / 10% первые 4 мес с Dikidi → 12% следующие 24 мес
Особенности: Онлайн-примерка причёсок/макияжа, портфолио до/после, тепловая карта загруженности
UI: Карточка мастера (фото, рейтинг, свободное время), календарь записи, фильтры по специализации
3. Food (Рестораны / Доставка / Кофейни)
Путь: app/Domains/Food/
Статус: Полная
Модели: Restaurant, RestaurantMenu, Dish, DishVariant, RestaurantOrder, RestaurantTable, DeliveryOrder, DeliveryZone, KDSOrder
Сервисы: RestaurantService, OrderService, DeliveryService, KDSService
Процессы: Списание ингредиентов при заказе, KDS на кухню, surge для доставки, QR-меню
Комиссия: 14%
Особенности: KDS, QR-меню, трекинг доставки, ОФД при оплате
UI: Карточка блюда (калории, аллергены), корзина, трекинг заказа
4. Hotels (Гостиницы / Отели)
Путь: app/Domains/Hotels/
Статус: Полная
Модели: Hotel, RoomType, Booking, HotelRoomInventory, Review, PayoutSchedule
Сервисы: HotelService, BookingService, InventoryManagement (номера)
Процессы: Выплата через 4 дня после выселения, hold при бронировании
Комиссия: 14% / 12% при миграции с Островок/Booking
Особенности: Календарь занятости, онлайн-тур 360°, интеграция с PMS
UI: Карточка отеля, календарь бронирования, фильтры по звёздам/удобствам
5. RealEstate (Недвижимость / Аренда / Продажа)
Путь: app/Domains/RealEstate/
Статус: Полная
Модели: Property, RentalListing, SaleListing, LandPlot, ViewingAppointment, RealEstateAgent
Сервисы: PropertyService, RentalService, SaleService
Процессы: Hold депозита при просмотре, списание комиссии после сделки
Комиссия: 14% от суммы сделки
Особенности: 3D-тур, расчёт ипотеки, проверка Росреестра
UI: Карточка объекта, фильтры по району/этажу, профиль агента
6. Игрушки и товары для детей (Toys & Kids) — потерянная вертикаль
Путь: app/Domains/ToysKids/
Модели: ToyProduct, ToyCategory, ToyOrder, ToyReview, ToyWishlistItem
Сервисы: ToyService, OrderService, InventoryManagement
Процессы: Автоматическое списание при заказе, прогноз спроса на сезон
Комиссия: 14%
Особенности: Возрастная категория, сертификаты безопасности, подарочная упаковка
UI: Яркие карточки, фильтры по возрасту/полу, вишлист-подарки
7. Электроника и гаджеты (Electronics)
Путь: app/Domains/Electronics/
Модели: ElectronicProduct, ElectronicCategory, ElectronicOrder, ElectronicReview
Сервисы: ElectronicService, WarrantyService
Процессы: Гарантийные случаи, возврат в течение 14 дней
Комиссия: 14%
Особенности: Сравнение характеристик, обзоры, интеграция с Яндекс.Маркет
UI: Таблицы характеристик, фильтры по бренду/цене/рейтингу
8. Мебель и интерьер (Furniture & Interior)
Путь: app/Domains/Furniture/
Модели: FurnitureItem, FurnitureCategory, FurnitureOrder, InteriorDesignProject
Сервисы: FurnitureService, InteriorAI (AI-дизайн)
Процессы: 3D-визуализация, доставка + сборка
Комиссия: 14%
Особенности: AI-конструктор интерьера, доставка крупногабаритных товаров
UI: 3D-просмотр, фильтры по стилю/материалу

9. Construction (Строительство / Ремонт / Материалы)
Путь: app/Domains/Construction/
Статус: Частично (нужна доработка)
Модели: ConstructionProject, Contractor, Material, MaterialOrder, ToolRental, ConstructionReview
Сервисы: ProjectService, MaterialService, ContractorMatchingService
Процессы: Автоматический расчёт сметы, hold материалов при заказе, прогноз спроса на стройматериалы
Комиссия: 14%
Особенности: 3D-смета проекта, подбор подрядчиков по рейтингу и гео, интеграция с поставщиками материалов
UI: Калькулятор сметы, профиль подрядчика (портфолио, отзывы), фильтры по типу работ/бюджету
10. Education / Courses (Образование / Онлайн-курсы)
Путь: app/Domains/Courses/
Статус: Полная
Модели: Course, Lesson, Enrollment, LessonProgress, Certificate, CourseReview, InstructorEarning
Сервисы: CourseService, EnrollmentService, ProgressTrackingService
Процессы: Автоматическое начисление сертификата после 100% прогресса, выплаты инструктору после курса
Комиссия: 14% от продажи курса
Особенности: Видеозвонки для живых уроков, прогресс-трекинг, сертификаты с QR-проверкой
UI: Карточка курса, плеер уроков, календарь живых занятий, личный кабинет ученика
11. Clinic / Medical (Клиники / Медицина)
Путь: app/Domains/Medical/
Статус: Полная
Модели: Clinic, Doctor, MedicalService, MedicalAppointment, MedicalCard, Prescription, MedicalReview
Сервисы: ClinicService, AppointmentService, MedicalRecordService
Процессы: Hold при записи, списание расходников (медицинских), напоминания за 24 ч
Комиссия: 14% / 12% при миграции с DocDoc/НаПоправку
Особенности: Электронная медкарта, онлайн-консультации, интеграция с ЕГИСЗ (если доступно)
UI: Профиль врача, календарь записи, фильтры по специальности/рейтингу
12. Vet / Pet (Ветеринария / Зоотовары / Услуги для животных)
Путь: app/Domains/Pet/
Статус: Полная
Модели: PetClinic, Vet, PetService, PetAppointment, PetProduct, PetBoarding, PetMedicalRecord
Сервисы: VetService, PetProductService, BoardingService
Процессы: Списание корма/лекарств при визите, напоминания о прививках
Комиссия: 14%
Особенности: Электронная веткарта, онлайн-консультации, продажа корма/аксессуаров
UI: Карточка клиники/ветеринара, календарь прививок, фильтры по типу животного
13. Toys & Kids (Игрушки / Товары для детей) — потерянная вертикаль
Путь: app/Domains/ToysKids/
Модели: ToyProduct, ToyCategory, ToyOrder, ToyReview, ToyWishlistItem, ToySubscription
Сервисы: ToyService, OrderService, SubscriptionService
Процессы: Автоматическое списание при заказе, прогноз спроса на сезон (Новый год, 1 сентября)
Комиссия: 14%
Особенности: Возрастная категория, сертификаты безопасности, подарочная упаковка
UI: Яркие карточки, фильтры по возрасту/полу/категории, вишлист-подарки
14. Electronics (Электроника / Гаджеты)
Путь: app/Domains/Electronics/
Модели: ElectronicProduct, ElectronicCategory, ElectronicOrder, ElectronicReview, WarrantyClaim
Сервисы: ElectronicService, WarrantyService, ReturnService
Процессы: Гарантийный учёт, возврат в течение 14 дней, списание при продаже
Комиссия: 14%
Особенности: Сравнение характеристик, обзоры, интеграция
UI: Таблицы характеристик, фильтры по бренду/цене/рейтингу
15. Furniture & Interior (Мебель / Интерьер)
Путь: app/Domains/Furniture/
Модели: FurnitureItem, FurnitureCategory, FurnitureOrder, InteriorDesignProject, FurnitureReview
Сервисы: FurnitureService, InteriorAI, DeliveryService
Процессы: 3D-визуализация, доставка + сборка, hold при заказе
Комиссия: 14%
Особенности: AI-конструктор интерьера, доставка крупногабаритных товаров
UI: 3D-просмотр, фильтры по стилю/материалу/размеру
16. Sporting Goods (Спорттовары)
Путь: app/Domains/SportingGoods/
Модели: SportProduct, SportCategory, SportOrder, SportReview
Сервисы: SportService, OrderService
Процессы: Списание при заказе, прогноз спроса на сезон (лето/зима)
Комиссия: 14%
Особенности: Размерная сетка, отзывы с фото использования
UI: Фильтры по виду спорта/размеру/бренду

17. Construction Materials (Строительные материалы)
Путь: app/Domains/ConstructionMaterials/
Статус: Потерянная / не реализована
Модели: ConstructionMaterial, MaterialCategory, MaterialSupplier, MaterialOrder, MaterialDelivery, MaterialReview
Сервисы: MaterialService, SupplierMatchingService, DeliveryService
Процессы: Автоматический расчёт объёма по проекту, прогноз спроса на сезон (весна/осень), hold при заказе
Комиссия: 14%
Особенности: Калькулятор объёмов (кирпич, бетон, краска), подбор поставщиков по цене/гео/рейтингу, доставка тяжёлых грузов
UI: Калькулятор материалов, карточка поставщика (склад, отзывы), фильтры по типу/объёму/цене
18. Books & Literature (Книги / Литература / Аудиокниги)
Путь: app/Domains/Books/
Статус: Потерянная / не реализована
Модели: Book, BookAuthor, BookCategory, BookOrder, BookReview, AudioBook
Сервисы: BookService, RecommendationService (по жанру/автору)
Процессы: Автоматическое начисление бонусов за отзывы, прогресс чтения для аудиокниг
Комиссия: 14%
Особенности: Рекомендации по жанру/автору/похожим книгам, интеграция с ЛитРес/Amazon Kindle
UI: Карточка книги (обложка, рейтинг, отрывок), фильтры по жанру/автору/цене, прогресс-бар чтения
19. Cosmetics & Perfume (Косметика / Парфюмерия)
Путь: app/Domains/Cosmetics/
Статус: Потерянная / не реализована
Модели: CosmeticProduct, CosmeticBrand, CosmeticCategory, CosmeticOrder, CosmeticReview
Сервисы: CosmeticService, TryOnService (AR-примерка)
Процессы: AR-примерка макияжа/парфюма, hold при заказе пробников
Комиссия: 14%
Особенности: AR-примерка (BeautyTryOn расширение), сертификаты натуральности, тестеры
UI: AR-примерка, карточка продукта (ингредиенты, отзывы), фильтры по типу кожи/запаху
20. Jewelry (Ювелирные изделия)
Путь: app/Domains/Jewelry/
Статус: Потерянная / не реализована
Модели: JewelryItem, JewelryCategory, JewelryOrder, JewelryReview, JewelryCertificate
Сервисы: JewelryService, CertificateService
Процессы: Сертификация подлинности, страховка при доставке
Комиссия: 14%
Особенности: Сертификаты GIA/IGI, 3D-просмотр украшений, подарочная упаковка
UI: 3D-вращение изделия, фильтры по металлу/камню/размеру
21. Gifts & Souvenirs (Подарки / Сувениры)
Путь: app/Domains/Gifts/
Статус: Потерянная / не реализована
Модели: GiftProduct, GiftCategory, GiftOrder, GiftReview
Сервисы: GiftService, WrappingService
Процессы: Подарочная упаковка, открытки, доставка сюрпризом
Комиссия: 14%
Особенности: Подбор подарков по поводу/бюджету, анонимная доставка
UI: Подбор по празднику, карточка подарка, вишлист-подарки
22. Medical Supplies (Медицинские товары / Аптеки)
Путь: app/Domains/MedicalSupplies/
Статус: Потерянная / не реализована
Модели: MedicalProduct, MedicalCategory, MedicalOrder, MedicalReview
Сервисы: MedicalSupplyService, PrescriptionService
Процессы: Проверка рецепта (OCR), списание при заказе
Комиссия: 14%
Особенности: Интеграция с рецептами, доставка лекарств, сертификаты качества
UI: Фильтры по действующему веществу/форме выпуска, карточка товара (инструкция)
23. Sporting Goods (Спорттовары)
Путь: app/Domains/SportingGoods/
Статус: Потерянная / не реализована
Модели: SportProduct, SportCategory, SportOrder, SportReview
Сервисы: SportService, SizeGuideService
Процессы: Размерная сетка, возврат по размеру
Комиссия: 14%
Особенности: Размерная сетка, отзывы с фото использования
UI: Фильтры по виду спорта/размеру/бренду
24. Auto Parts & Accessories (Автозапчасти / Аксессуары) — расширение Auto
Путь: app/Domains/Auto/Parts/ (или отдельно AutoParts)
Статус: Частично (расширить существующую Auto)
Модели: AutoPart, AutoPartCategory, AutoPartOrder, AutoPartReview
Сервисы: AutoPartService, CompatibilityService
Процессы: Проверка совместимости по VIN, списание при заказе
Комиссия: 14%
Особенности: Подбор по VIN/марке/модели, гарантия подлинности
UI: Подбор по авто, карточка запчасти (OEM/аналог), отзывы

25. Logistics & Courier (Логистика / Курьеры / Склады)
Путь: app/Domains/Logistics/
Статус: Частично (нужна доработка)
Модели: Courier, CourierTask, DeliveryOrder, DeliveryZone, Warehouse, StockMovement, CourierReview
Сервисы: CourierService, RouteOptimizationService, WarehouseService
Процессы: Автоматический расчёт маршрута (OSRM/Yandex), hold товаров при заказе, трекинг в реальном времени
Комиссия: 14% от доставки
Особенности: Тепловая карта загруженности курьеров, прогнозирование времени доставки, страховка груза
UI: Карта маршрута курьера, статус доставки в реальном времени, фильтры по зоне/весу
26. MedicalHealthcare (Здравоохранение / Телеконсультации)
Путь: app/Domains/MedicalHealthcare/
Статус: Полная
Модели: Clinic, Doctor, MedicalService, Teleconsultation, MedicalCard, Prescription, HealthRecommendation
Сервисы: TeleconsultationService, RecommendationService (здоровье)
Процессы: Видеозвонок для консультаций, напоминания о приёме лекарств, электронная карта
Комиссия: 14%
Особенности: Интеграция с ЕГИСЗ, AI-рекомендации по здоровью (анонимизировано)
UI: Профиль врача, календарь консультаций, онлайн-карта здоровья
27. PetServices (Услуги для животных — расширение Pet)
Путь: app/Domains/Pet/Services/
Модели: PetGroomingService, PetBoarding, PetTraining, PetWalking, PetAppointment
Сервисы: GroomingService, BoardingService, WalkingService
Процессы: Hold при бронировании груминга/передержки, напоминания о вакцинации
Комиссия: 14%
Особенности: Прогулки с собаками, передержка, груминг с фото до/после
UI: Профиль выгульщика/грумера, календарь записи, отзывы с фото питомцев
28. Photography & Video (Фотография / Видеосъёмка)
Путь: app/Domains/Photography/
Статус: Полная
Модели: PhotoStudio, Photographer, PhotoPackage, PhotoSession, PhotoGallery, PhotoReview
Сервисы: PhotoSessionService, GalleryService
Процессы: Онлайн-бронь съёмки, галерея после съёмки (с доступом по ссылке)
Комиссия: 14%
Особенности: Онлайн-галерея с водяными знаками, оплата после съёмки
UI: Портфолио фотографа, календарь съёмок, галерея клиента
29. Freelance & Services (Фриланс / Услуги)
Путь: app/Domains/Freelance/
Статус: Полная
Модели: Freelancer, FreelanceService, FreelanceJob, FreelanceProposal, FreelanceContract, FreelanceReview
Сервисы: FreelanceService, ProposalService, ContractService
Процессы: Эскроу-оплата (hold до сдачи работы), рейтинг фрилансера
Комиссия: 14% от суммы контракта
Особенности: Эскроу, арбитраж споров, портфолио фрилансера
UI: Профиль фрилансера, фильтры по навыкам/рейтингу/цене
30. HomeServices (Услуги для дома / Мастера)
Путь: app/Domains/HomeServices/
Статус: Полная
Модели: Contractor, HomeService, HomeJob, ContractorSchedule, HomeReview
Сервисы: HomeService, JobMatchingService
Процессы: Подбор мастера по рейтингу/гео, hold при бронировании
Комиссия: 14%
Особенности: Тепловая карта загруженности мастеров, гарантия качества
UI: Карточка мастера, календарь записи, фильтры по типу работы
31. Tickets & Events (Билеты / Мероприятия)
Путь: app/Domains/Tickets/
Статус: Полная
Модели: Event, EventCategory, Ticket, TicketType, TicketOrder, EventReview
Сервисы: EventService, TicketService, CheckinService
Процессы: Чекер билетов на входе, возврат до мероприятия (+2% комиссии)
Комиссия: 8–17% в зависимости от источника (Яндекс.Афиша и т.д.)
Особенности: QR-билеты, чекер на входе, гарантийное письмо для организаторов
UI: Карточка мероприятия, схема зала, фильтры по дате/жанру
32. Travel & Tourism (Путешествия / Туры / Экскурсии)
Путь: app/Domains/Travel/
Статус: Полная
Модели: TravelAgency, TravelTour, TravelBooking, TravelAccommodation, TravelFlight, TravelGuide
Сервисы: TourService, BookingService
Процессы: Hold при бронировании тура, выплата после поездки
Комиссия: 14% / 12% при миграции с Travelata/Level.Travel
Особенности: Подбор туров по бюджету/стране, интеграция с Booking/Aviasales
UI: Карточка тура, фильтры по стране/дате/звёздам, карта маршрута

33. Billiards & Entertainment Venues (Бильярд / Развлекательные заведения)
Путь: app/Domains/Billiards/
Статус: Потерянная / не реализована
Модели: BilliardClub, BilliardTable, BilliardBooking, BilliardTournament, BilliardReview
Сервисы: BilliardService, BookingService, TournamentService
Процессы: Бронирование стола на время, автоматическое закрытие брони через 15 мин без оплаты, турниры с призами
Комиссия: 14% от бронирования
Особенности: Онлайн-бронь стола, живые турниры с трансляцией, рейтинг игроков
UI: Карта столов в зале, календарь бронирования, профиль игрока (рейтинг, победы), фильтры по типу стола (русский/пул/снукер)
34. Event Venues & Halls (Залы / Конференц-залы / Банкетные залы)
Путь: app/Domains/EventVenues/
Статус: Потерянная / не реализована
Модели: EventHall, HallBooking, EventType, HallEquipment, HallReview
Сервисы: HallService, BookingService, EquipmentService
Процессы: Бронирование зала + оборудования (проектор, сцена, звук), гарантийное письмо для организаторов, +2% комиссии при предоплате
Комиссия: 8–17% в зависимости от источника (Яндекс.Афиша и т.д.)
Особенности: Схема зала с интерактивным планом, чек-лист для мероприятия, интеграция с кейтерингом
UI: Интерактивная схема зала, календарь бронирования, фильтры по вместимости/оборудованию/цене
35. Baths & Saunas (Бани / Сауны / СПА)
Путь: app/Domains/BathsSaunas/
Статус: Потерянная / не реализована
Модели: Bathhouse, SaunaRoom, BathBooking, BathService (веники, массаж), BathReview
Сервисы: BathService, BookingService
Процессы: Бронирование парной/номера, автоматическое списание расходников (веники, масло), напоминания за 24 ч
Комиссия: 14%
Особенности: Онлайн-консультация банщика, фото интерьера парной, пакетные услуги (баня + массаж)
UI: Карточка бани (температура, тип парной), календарь бронирования, фильтры по вместимости/типу
36. Short-Term Rentals (Квартиры / Апартаменты посуточно)
Путь: app/Domains/ShortTermRentals/
Статус: Потерянная / не реализована
Модели: Apartment, ApartmentBooking, ApartmentReview, ApartmentAmenity
Сервисы: ApartmentService, BookingService, CleaningService
Процессы: Выплата хозяину через 4 дня после выселения, hold депозита при бронировании, автоматический чек-ин/чек-аут
Комиссия: 14% / 12% при миграции с Суточно.ру / Airbnb
Особенности: Проверка паспорта/селфи при бронировании, интеграция с умными замками, страховка имущества
UI: Карточка квартиры (фото 360°, удобства), календарь бронирования, фильтры по району/цене/удобствам
37. Party & Celebration Venues (Вечеринки / Корпоративы / Праздники)
Путь: app/Domains/PartyVenues/
Статус: Потерянная / не реализована
Модели: PartyVenue, PartyBooking, PartyService (аниматоры, ведущие), PartyReview
Сервисы: PartyService, BookingService
Процессы: Бронирование + услуги (аниматоры, декор, кейтеринг), гарантийное письмо для организаторов
Комиссия: 14%
Особенности: Подбор ведущих/аниматоров по рейтингу, чек-лист подготовки праздника
UI: Карточка площадки, календарь бронирования, фильтры по типу праздника/вместимости

38. Детские центры / Детские развлечения / Клубы (Kids Centers & Playgrounds)
Путь: app/Domains/KidsCenters/
Статус: Потерянная / не реализована
Модели: KidsCenter, KidsEvent, KidsBooking, KidsActivity, KidsReview, KidsGroup
Сервисы: KidsCenterService, BookingService, ActivityService
Процессы: Бронирование занятия/праздника, автоматическое списание расходников (материалы для мастер-классов), напоминания родителям за 24 ч
Комиссия: 14%
Особенности: Возрастная категория (0–3, 3–6, 6–12), мастер-классы, праздники, аниматоры, фото/видео отчёт
UI: Карточка центра (фото залов, возраст), календарь занятий, фильтры по возрасту/типу занятия (рисование, танцы, робототехника)
39. Автошколы / Обучение вождению (Driving Schools)
Путь: app/Domains/DrivingSchools/
Статус: Потерянная / не реализована
Модели: DrivingSchool, Instructor, DrivingCourse, DrivingLesson, DrivingStudent, DrivingExam
Сервисы: DrivingSchoolService, LessonService, ExamService
Процессы: Бронирование урока, онлайн-теория, запись на экзамен, прогресс ученика
Комиссия: 14%
Особенности: Онлайн-теория (видеоуроки), симулятор вождения, интеграция с ГИБДД (если API доступно)
UI: Профиль инструктора, календарь уроков, прогресс-бар ученика, фильтры по категории (B, C, D)
40. Караоке / Караоке-клубы (Karaoke Venues)
Путь: app/Domains/Karaoke/
Статус: Потерянная / не реализована
Модели: KaraokeClub, KaraokeRoom, KaraokeBooking, KaraokeSong, KaraokeReview
Сервисы: KaraokeService, BookingService, SongQueueService
Процессы: Бронирование комнаты, онлайн-очередь песен, автоматическое списание по времени
Комиссия: 14%
Особенности: Онлайн-очередь песен, караоке-рейтинг исполнителей, фото/видео с вечеринки
UI: Карточка клуба (фото комнат), календарь бронирования, виртуальная очередь песен
41. Кофейни / Кофе с собой / Specialty Coffee (Coffee Shops)
Путь: app/Domains/CoffeeShops/
Статус: Потерянная / не реализована
Модели: CoffeeShop, CoffeeMenu, CoffeeDrink, CoffeeOrder, CoffeeReview
Сервисы: CoffeeService, OrderService
Процессы: Автоматическое списание зёрен/сиропов при заказе, прогноз спроса по времени суток
Комиссия: 14%
Особенности: Лояльность (10-й кофе бесплатно), онлайн-заказ с собой, интеграция с кофейным оборудованием
UI: Карточка кофейни (меню, рейтинг), фильтры по типу кофе/напитка, корзина to-go
42. Чайные / Чайные дома / Чайные церемонии (Tea Houses & Ceremonies)
Путь: app/Domains/TeaHouses/
Статус: Потерянная / не реализована
Модели: TeaHouse, TeaMenu, TeaCeremony, TeaBooking, TeaReview
Сервисы: TeaService, CeremonyService
Процессы: Бронирование чайной церемонии, автоматическое списание чая/сладостей
Комиссия: 14%
Особенности: Онлайн-чайная церемония (видеозвонок), подбор чая по вкусу/настроению
UI: Карточка чайной (атмосфера, фото), календарь церемоний, фильтры по типу чая/длительности

43. Танцевальные студии / Школы танцев (Dance Studios)
Путь: app/Domains/DanceStudios/
Статус: Потерянная / не реализована
Модели: DanceStudio, DanceTeacher, DanceCourse, DanceClass, DanceBooking, DanceReview
Сервисы: DanceStudioService, BookingService, GroupService
Процессы: Бронирование занятия/абонемента, автоматическое начисление посещаемости, напоминания за 24 ч
Комиссия: 14%
Особенности: Онлайн-трансляции групповых занятий (если разрешено), возрастные группы, сертификаты после курса
Юридические аспекты РФ: Возраст 0+ (детские группы), 18+ для взрослых, договор оферты, согласие родителей для детей <14 лет (ФЗ-152)
UI: Профиль преподавателя (видео-уроки), календарь занятий, фильтры по стилю (сальса, хип-хоп, балет)/уровню
44. Квест-комнаты / Квесты (Quest Rooms)
Путь: app/Domains/QuestRooms/
Статус: Потерянная / не реализована
Модели: QuestRoom, Quest, QuestBooking, QuestTeam, QuestReview
Сервисы: QuestService, BookingService, TeamService
Процессы: Бронирование комнаты на команду, автоматическое закрытие брони через 15 мин без оплаты, фото/видео отчёт после квеста
Комиссия: 14%
Особенности: Возрастные ограничения (12+, 16+, 18+), сюжетные квесты, интеграция с видеонаблюдением (с согласия)
Юридические аспекты РФ: Возрастные метки на сайте (ФЗ-436), договор оферты, согласие на фото/видео (ФЗ-152)
UI: Карточка квеста (сложность, время, возраст), календарь бронирования, фильтры по жанру (хоррор, детектив, приключение)
45. Бары / Пабы / Крафтовое пиво (Bars & Pubs)
Путь: app/Domains/Bars/
Статус: Потерянная / не реализована
Модели: Bar, BarMenu, BarDrink, BarOrder, BarTable, BarReview
Сервисы: BarService, OrderService
Процессы: Бронирование столика, автоматическое списание алкоголя, возрастная проверка
Комиссия: 14%
Особенности: Возраст 18+, онлайн-меню с фото, крафтовое пиво с описанием вкуса
Юридические аспекты РФ: Возрастная авторизация (18+), лицензия на алкоголь (проверяется при регистрации), запрет рекламы алкоголя несовершеннолетним (ФЗ-38)
UI: Карточка бара, онлайн-меню, бронирование столика, фильтры по типу напитка/цене
46. Кальянные / Hookah Lounges
Путь: app/Domains/HookahLounges/
Статус: Потерянная / не реализована
Модели: HookahLounge, HookahMenu, HookahBooking, HookahMix, HookahReview
Сервисы: HookahService, BookingService
Процессы: Бронирование зоны/кальяна, автоматическое списание табака/угля
Комиссия: 14%
Особенности: Возраст 18+, миксы табаков, онлайн-меню с фото
Юридические аспекты РФ: Возраст 18+, запрет курения в общественных местах (ФЗ-15), лицензия на табак (проверяется)
UI: Карточка кальянной, онлайн-меню миксов, бронирование зоны, фильтры по вкусу
47. Детские развлекательные центры / Игровые комнаты (Kids Play Centers)
Путь: app/Domains/KidsPlayCenters/
Статус: Потерянная / не реализована
Модели: KidsPlayCenter, PlayRoom, KidsEvent, KidsBooking, KidsReview
Сервисы: KidsPlayService, BookingService
Процессы: Бронирование комнаты/праздника, автоматическое списание материалов (аниматоры, игрушки)
Комиссия: 14%
Особенности: Возраст 0–12, праздники с аниматорами, фото/видео отчёт
Юридические аспекты РФ: Согласие родителей (ФЗ-152), возрастные ограничения, безопасность (проверяется при регистрации)
UI: Карточка центра (фото зон), календарь праздников, фильтры по возрасту/тематике
48. Йога-центры / Пилатес / Фитнес-студии (Yoga & Pilates Studios)
Путь: app/Domains/YogaPilates/
Статус: Потерянная / не реализована
Модели: YogaStudio, YogaTeacher, YogaClass, YogaBooking, YogaReview
Сервисы: YogaService, BookingService
Процессы: Бронирование занятия/абонемента, онлайн-трансляции, напоминания за 24 ч
Комиссия: 14%
Особенности: Онлайн-занятия, прогресс ученика, сертификаты после курса
Юридические аспекты РФ: Возраст 14+ (для взрослых), согласие родителей для детей
UI: Профиль преподавателя, календарь занятий, фильтры по направлению (хатха, аштанга, пилатес)
49. Квесты в реальности / Escape Rooms (расширение квестов)
Путь: app/Domains/EscapeRooms/
Статус: Потерянная / расширение квест-комнат
Модели: EscapeRoom, EscapeQuest, EscapeBooking, EscapeTeam, EscapeReview
Сервисы: EscapeService, BookingService
Процессы: Бронирование комнаты, таймер 60 мин, фото/видео отчёт
Комиссия: 14%
Особенности: Возрастные ограничения (12+, 16+, 18+), сюжетные квесты
Юридические аспекты РФ: Возрастные метки (ФЗ-436), договор оферты
UI: Карточка квеста, схема комнаты, фильтры по сложности/жанру
50. Настольные игры / Антикафе / Игровые клубы (Board Games & Anti-Cafes)
Путь: app/Domains/BoardGames/
Статус: Потерянная / не реализована
Модели: BoardGameClub, BoardGame, BoardGameBooking, BoardGameReview
Сервисы: BoardGameService, BookingService
Процессы: Бронирование стола, аренда игр по часам, автоматическое списание по времени
Комиссия: 14%
Особенности: Аренда настолок, турниры, чай/кофе в комплекте
Юридические аспекты РФ: Возраст 6+ (детские игры), 18+ для взрослых
UI: Каталог настолок, календарь бронирования, фильтры по жанру/количеству игроков

43. Танцевальные студии / Школы танцев (Dance Studios)
Путь: app/Domains/DanceStudios/
Статус: Потерянная / не реализована
Модели: DanceStudio, DanceTeacher, DanceCourse, DanceClass, DanceBooking, DanceReview
Сервисы: DanceStudioService, BookingService, GroupService
Процессы: Бронирование занятия/абонемента, автоматическое начисление посещаемости, напоминания за 24 ч
Комиссия: 14%
Особенности: Онлайн-трансляции групповых занятий (если разрешено), возрастные группы, сертификаты после курса
Юридические аспекты РФ: Возраст 0+ (детские группы), 18+ для взрослых, договор оферты, согласие родителей для детей <14 лет (ФЗ-152)
UI: Профиль преподавателя (видео-уроки), календарь занятий, фильтры по стилю (сальса, хип-хоп, балет)/уровню
44. Квест-комнаты / Квесты (Quest Rooms)
Путь: app/Domains/QuestRooms/
Статус: Потерянная / не реализована
Модели: QuestRoom, Quest, QuestBooking, QuestTeam, QuestReview
Сервисы: QuestService, BookingService, TeamService
Процессы: Бронирование комнаты на команду, автоматическое закрытие брони через 15 мин без оплаты, фото/видео отчёт после квеста
Комиссия: 14%
Особенности: Возрастные ограничения (12+, 16+, 18+), сюжетные квесты, интеграция с видеонаблюдением (с согласия)
Юридические аспекты РФ: Возрастные метки на сайте (ФЗ-436), договор оферты, согласие на фото/видео (ФЗ-152)
UI: Карточка квеста (сложность, время, возраст), календарь бронирования, фильтры по жанру (хоррор, детектив, приключение)
45. Бары / Пабы / Крафтовое пиво (Bars & Pubs)
Путь: app/Domains/Bars/
Статус: Потерянная / не реализована
Модели: Bar, BarMenu, BarDrink, BarOrder, BarTable, BarReview
Сервисы: BarService, OrderService
Процессы: Бронирование столика, автоматическое списание алкоголя, возрастная проверка
Комиссия: 14%
Особенности: Возраст 18+, онлайн-меню с фото, крафтовое пиво с описанием вкуса
Юридические аспекты РФ: Возрастная авторизация (18+), лицензия на алкоголь (проверяется при регистрации), запрет рекламы алкоголя несовершеннолетним (ФЗ-38)
UI: Карточка бара, онлайн-меню, бронирование столика, фильтры по типу напитка/цене
46. Кальянные / Hookah Lounges
Путь: app/Domains/HookahLounges/
Статус: Потерянная / не реализована
Модели: HookahLounge, HookahMenu, HookahBooking, HookahMix, HookahReview
Сервисы: HookahService, BookingService
Процессы: Бронирование зоны/кальяна, автоматическое списание табака/угля
Комиссия: 14%
Особенности: Возраст 18+, миксы табаков, онлайн-меню с фото
Юридические аспекты РФ: Возраст 18+, запрет курения в общественных местах (ФЗ-15), лицензия на табак (проверяется)
UI: Карточка кальянной, онлайн-меню миксов, бронирование зоны, фильтры по вкусу
47. Детские развлекательные центры / Игровые комнаты (Kids Play Centers)
Путь: app/Domains/KidsPlayCenters/
Статус: Потерянная / не реализована
Модели: KidsPlayCenter, PlayRoom, KidsEvent, KidsBooking, KidsReview
Сервисы: KidsPlayService, BookingService
Процессы: Бронирование комнаты/праздника, автоматическое списание материалов (аниматоры, игрушки)
Комиссия: 14%
Особенности: Возраст 0–12, праздники с аниматорами, фото/видео отчёт
Юридические аспекты РФ: Согласие родителей (ФЗ-152), возрастные ограничения, безопасность (проверяется при регистрации)
UI: Карточка центра (фото зон), календарь праздников, фильтры по возрасту/тематике
48. Йога-центры / Пилатес / Фитнес-студии (Yoga & Pilates Studios)
Путь: app/Domains/YogaPilates/
Статус: Потерянная / не реализована
Модели: YogaStudio, YogaTeacher, YogaClass, YogaBooking, YogaReview
Сервисы: YogaService, BookingService
Процессы: Бронирование занятия/абонемента, онлайн-трансляции, напоминания за 24 ч
Комиссия: 14%
Особенности: Онлайн-занятия, прогресс ученика, сертификаты после курса
Юридические аспекты РФ: Возраст 14+ (для взрослых), согласие родителей для детей
UI: Профиль преподавателя, календарь занятий, фильтры по направлению (хатха, аштанга, пилатес)
49. Квесты в реальности / Escape Rooms (расширение квестов)
Путь: app/Domains/EscapeRooms/
Статус: Потерянная / расширение квест-комнат
Модели: EscapeRoom, EscapeQuest, EscapeBooking, EscapeTeam, EscapeReview
Сервисы: EscapeService, BookingService
Процессы: Бронирование комнаты, таймер 60 мин, фото/видео отчёт
Комиссия: 14%
Особенности: Возрастные ограничения (12+, 16+, 18+), сюжетные квесты
Юридические аспекты РФ: Возрастные метки (ФЗ-436), договор оферты
UI: Карточка квеста, схема комнаты, фильтры по сложности/жанру
50. Настольные игры / Антикафе / Игровые клубы (Board Games & Anti-Cafes)
Путь: app/Domains/BoardGames/
Статус: Потерянная / не реализована
Модели: BoardGameClub, BoardGame, BoardGameBooking, BoardGameReview
Сервисы: BoardGameService, BookingService
Процессы: Бронирование стола, аренда игр по часам, автоматическое списание по времени
Комиссия: 14%
Особенности: Аренда настолок, турниры, чай/кофе в комплекте
Юридические аспекты РФ: Возраст 6+ (детские игры), 18+ для взрослых
UI: Каталог настолок, календарь бронирования, фильтры по жанру/количеству игроков

51. Fresh Produce Delivery (Доставка свежих фруктов и овощей)
Путь: app/Domains/FreshProduce/
Статус: Потерянная / высокоприоритетная
Модели: FreshProduct, FarmSupplier, ProduceOrder, ProduceBox, ProduceReview
Сервисы: FreshProduceService, SubscriptionService, QualityControlService
Процессы: Ежедневная доставка, подписки (еженедельные боксы), автоматическое списание со склада, контроль свежести (QR-код + фото)
Комиссия: 14% (12% при миграции с фермерских маркетплейсов)
Особенности: Фермерские поставки напрямую, сезонные боксы, контроль качества (фото при доставке), интеграция с 54-ФЗ (ОФД)
Юридические аспекты РФ: Сертификаты качества (ГОСТ/ЕАС), маркировка «Эко», возрастные ограничения не требуются
UI: Карточка бокса, подписка «Фрукты на неделю», фильтры по сезону/диете (веган, детское, спортивное)
52. Ready Meals & Home Cooking Kits (Готовые блюда + наборы для приготовления)
Путь: app/Domains/ReadyMeals/
Статус: Потерянная / высокоприоритетная
Модели: ReadyMeal, MealKit, KitchenOrder, Recipe, MealReview
Сервисы: ReadyMealService, MealKitService, SubscriptionService
Процессы: Доставка готовых блюд из столовых + наборы «готовь сам» (рецепт + ингредиенты), автоматическое списание ингредиентов
Комиссия: 14%
Особенности: Два потока: готовые блюда + meal-kits, подписки «Обеды на неделю», интеграция с ОФД
Юридические аспекты РФ: Маркировка аллергенов, срок годности, 54-ФЗ при доставке готовых блюд
UI: Карточка блюда (калории, время приготовления), подписка «5 обедов», фильтры по кухне/диете/времени приготовления
53. Grocery Delivery (Полная доставка продуктов / Супермаркеты)
Путь: app/Domains/Grocery/
Статус: Потерянная / стратегически важная
Модели: GroceryStore, GroceryProduct, GroceryOrder, GroceryCategory
Сервисы: GroceryService, OrderService, InventoryService
Процессы: Доставка из супермаркетов + фермерских магазинов, быстрые слоты (15–60 мин), подписки
Комиссия: 14%
Особенности: Интеграция с существующими сетями (Магнит, Пятёрочка, ВкусВилл), холодная цепь, прогноз спроса
Юридические аспекты РФ: 54-ФЗ, маркировка «Честный ЗНАК», возрастные ограничения на алкоголь/табак
UI: Поиск по категориям, быстрый повтор заказа, фильтры по цене/бренду/акциям
54. Pharmacy Delivery (Доставка лекарств и аптечных товаров)
Путь: app/Domains/Pharmacy/
Статус: Потерянная / высокоприоритетная
Модели: Pharmacy, Medicine, PharmacyOrder, Prescription, MedicineReview
Сервисы: PharmacyService, PrescriptionService
Процессы: Проверка рецепта (OCR + ручная), доставка по рецепту и без, холодная цепь для вакцин
Комиссия: 14%
Особенности: Электронные рецепты, интеграция с ЕГИСЗ, доставка в течение 1–2 часов
Юридические аспекты РФ: Лицензия на фармацевтическую деятельность, хранение рецептурных препаратов, ФЗ-152 (медданные)
UI: Поиск по МНН/торговому названию, фильтры по цене/аналогу, личная аптечка
55. Healthy Food & Diet Delivery (Здоровое питание / Диетические наборы)
Путь: app/Domains/HealthyFood/
Статус: Потерянная / высокоприоритетная
Модели: DietPlan, HealthyMeal, SubscriptionBox, NutritionReview
Сервисы: DietService, SubscriptionService
Процессы: Персонализированные рационы (по анализам или целям), подписки на 7/14/30 дней
Комиссия: 14%
Особенности: Интеграция с AI-рекомендациями (калории, БЖУ), доставка охлаждённых блюд
Юридические аспекты РФ: Маркировка БЖУ, отсутствие медицинских претензий (не лечение)
UI: Персональный план питания, прогресс по целям, фильтры по диете (кето, веган, низкоуглеводная)

55. Кондитерские / Сладости / Выпечка (Confectionery & Bakery)
Путь: app/Domains/Confectionery/
Статус: Потерянная / высокоприоритетная
Модели: ConfectioneryShop, Cake, Pastry, BakeryOrder, ConfectioneryReview, CakeDesign
Сервисы: ConfectioneryService, OrderService, CustomCakeService
Процессы: Доставка свежей выпечки, кастомные торты по фото/описанию, подписки «Сладкий день недели»
Комиссия: 14%
Особенности: Онлайн-дизайн торта, доставка в течение 1–2 часов (для свежей выпечки), контроль свежести
Юридические аспекты РФ: Маркировка аллергенов, 54-ФЗ при доставке готовых изделий
UI: Карточка торта (фото, начинка), конструктор торта, фильтры по вкусу/весу/поводу
56. Мясо и мясные продукты / Мясные лавки (Meat & Meat Products)
Путь: app/Domains/MeatShops/
Статус: Потерянная / высокоприоритетная
Модели: MeatShop, MeatProduct, MeatOrder, MeatBox, MeatReview
Сервисы: MeatService, SubscriptionService, QualityControlService
Процессы: Подписки «Мясной бокс на неделю», доставка охлаждённого/замороженного мяса, контроль качества (сертификаты)
Комиссия: 14%
Особенности: Фермерское мясо, разделка по частям, вакуумная упаковка, холодная цепь
Юридические аспекты РФ: Ветеринарные сертификаты, маркировка «Честный ЗНАК», 54-ФЗ
UI: Карточка мяса (порода, часть туши), бокс-подписка, фильтры по виду мяса/цене/сертификату
57. Офисное / Корпоративное питание (Office & Corporate Catering)
Путь: app/Domains/OfficeCatering/
Статус: Потерянная / высокоприоритетная
Модели: CorporateClient, OfficeMenu, CorporateOrder, CorporateDelivery
Сервисы: CorporateService, MenuService, DeliveryService
Процессы: Корпоративные подписки (обеды в офис), меню на неделю/месяц, автоматический расчёт по количеству сотрудников
Комиссия: 14%
Особенности: Интеграция с корпоративными чатами (Telegram/Slack), персональные предпочтения сотрудников
Юридические аспекты РФ: 54-ФЗ, маркировка аллергенов, корпоративный договор
UI: Корпоративный кабинет, меню на неделю, фильтры по диете/бюджету
58. Фермерские продукты напрямую (Farm Direct / Farmers Market)
Путь: app/Domains/FarmDirect/
Статус: Потерянная / высокоприоритетная
Модели: Farm, FarmProduct, FarmOrder, FarmReview, FarmSubscription
Сервисы: FarmService, SubscriptionService, QualityControlService
Процессы: Прямая доставка от фермера, боксы «От фермера», контроль качества (сертификаты + фото)
Комиссия: 12–14% (пониженная для фермеров)
Особенности: Прозрачность (фермер → клиент), сезонные боксы, подписки
Юридические аспекты РФ: Сертификаты качества, маркировка «Эко/Органик», 54-ФЗ
UI: Профиль фермера, бокс-подписка, фильтры по региону/сезону/типу продукта

## технико-технические особенности ##
1. Fresh Produce Delivery (Доставка свежих фруктов и овощей)
Техническое ядро

Модели: FreshProduct, FarmSupplier, ProduceBox, ProduceOrder, ProduceSubscription
Сервисы: FreshProduceService, QualityControlService, SubscriptionService
Джобы: DailyFreshDeliveryJob, QualityCheckJob, LowStockAlertJob
События: ProduceOrderCreated, BoxDelivered, QualityIssueDetected

Ключевые процессы

Ежедневный импорт остатков от фермеров
Автоматическое формирование боксов по подписке
Контроль свежести (QR + фото при упаковке и доставке)
Hold остатков при заказе → release при отмене

Интеграции

InventoryManagementService — списание/hold свежих продуктов
Wallet + Payment — оплата бокса, возврат при порче
DemandForecastService — прогноз спроса по сезонам
RecommendationService — «Боксы, которые вам понравятся»
FraudMLService — проверка частых отмен/возвратов
PromoCampaignService — сезонные акции «Фрукты недели»
Search — ранжирование по свежести и рейтингу фермера
Notifications — push «Ваш бокс в пути + фото»


2. Ready Meals & Home Cooking Kits (Готовые блюда + наборы для приготовления)
Техническое ядро

Модели: ReadyMeal, MealKit, KitchenOrder, Recipe, MealSubscription
Сервисы: ReadyMealService, MealKitService, SubscriptionService
Джобы: DailyMealPreparationJob, SubscriptionRenewalJob
События: MealOrderCreated, MealDelivered, RecipeUsed

Ключевые процессы

Два потока: готовые блюда и meal-kits
Автоматическое списание ингредиентов
Контроль срока годности (холодная цепь)

Интеграции

InventoryManagementService — списание ингредиентов/готовых блюд
Wallet + Payment — подписка + разовые заказы
DemandForecastService — прогноз популярных блюд
RecommendationService — «Блюда, которые вы любите»
FraudMLService — детекция частых возвратов
Promo — акции «Обед за 299 руб»
Search — ранжирование по калорийности и отзывам
Notifications — «Ваш обед готов + фото»


3. Grocery Delivery (Полная доставка продуктов / Супермаркеты)
Техническое ядро

Модели: GroceryStore, GroceryProduct, GroceryOrder, GroceryCategory
Сервисы: GroceryService, OrderService, InventoryService
Джобы: DailyStockSyncJob, FastDeliverySlotJob
События: GroceryOrderCreated, OrderPicked, OrderDelivered

Ключевые процессы

Синхронизация остатков с партнёрскими магазинами
Быстрые слоты (15–60 мин)
Подписки «Продукты на неделю»

Интеграции

InventoryManagementService — реальное списание со склада партнёра
Wallet + Payment — split-платежи (товары + доставка)
DemandForecastService — прогноз по категориям
RecommendationService — «Что обычно берут с этим товаром»
FraudMLService — проверка частых отмен
Promo — персональные акции по истории покупок
Search — умный поиск с учётом акций и свежести


4. Pharmacy Delivery (Доставка лекарств и аптечных товаров)
Техническое ядро

Модели: Pharmacy, Medicine, PharmacyOrder, Prescription
Сервисы: PharmacyService, PrescriptionService, AgeVerificationService
Джобы: PrescriptionValidationJob, ColdChainMonitoringJob
События: PrescriptionOrderCreated, MedicineDelivered

Ключевые процессы

OCR + ручная проверка рецептов
Холодная цепь для вакцин и термолабильных препаратов
Возрастная проверка (18+)

Интеграции

Wallet + Payment — оплата рецептурных и безрецептурных товаров
InventoryManagementService — списание со склада аптеки
FraudMLService — детекция частых заказов рецептурных препаратов
RecommendationService — «Часто берут вместе с этим лекарством»
Notifications — «Ваш рецепт проверен, доставка в пути»


5. Healthy Food & Diet Delivery (Здоровое питание / Диетические наборы)
Техническое ядро

Модели: DietPlan, HealthyMeal, MealSubscription, NutritionLog
Сервисы: DietService, SubscriptionService, NutritionTrackingService
Джобы: DietPlanGenerationJob, SubscriptionRenewalJob
События: DietPlanCreated, MealDelivered

Ключевые процессы

Персонализированные рационы (по целям или анализам)
Подписки на 7/14/30 дней
Трекинг питания пользователя

Интеграции

Wallet + Payment — подписка + разовые заказы
RecommendationService — рекомендации блюд по целям
DemandForecastService — прогноз спроса по диетам
Analytics — отчёт по прогрессу питания
Notifications — ежедневные напоминания о приёме пищи


6. Confectionery & Bakery (Кондитерские / Выпечка)
Техническое ядро

Модели: ConfectioneryShop, Cake, Pastry, BakeryOrder, CustomCake
Сервисы: ConfectioneryService, CustomOrderService
Джобы: DailyFreshBakeJob, CustomCakeProductionJob
События: CakeOrderCreated, OrderReady

Ключевые процессы

Онлайн-конструктор тортов
Доставка свежей выпечки (1–2 часа)
Подписки «Сладкий день»

Интеграции

InventoryManagementService — списание ингредиентов
Wallet + Payment — предоплата кастомных тортов
RecommendationService — «Торты, которые любят ваши друзья»
Promo — акции «Торт ко дню рождения»


7. Meat & Meat Products (Мясо и мясные продукты)
Техническое ядро

Модели: MeatShop, MeatProduct, MeatBox, MeatOrder
Сервисы: MeatService, SubscriptionService
Джобы: DailyMeatDeliveryJob, QualityCheckJob
События: MeatBoxCreated, OrderDelivered

Ключевые процессы

Подписки «Мясной бокс»
Разделка по частям
Контроль свежести и ветсертификатов

Интеграции

InventoryManagementService — списание мяса
Wallet + Payment — подписка + разовые заказы
FraudMLService — детекция частых возвратов
DemandForecastService — прогноз по сезонам


8. Office & Corporate Catering (Офисное / Корпоративное питание)
Техническое ядро

Модели: CorporateClient, OfficeMenu, CorporateOrder
Сервисы: CorporateService, MenuService
Джобы: DailyCorporateDeliveryJob, MenuUpdateJob
События: CorporateOrderCreated

Ключевые процессы

Корпоративные подписки (обеды в офис)
Персональные предпочтения сотрудников
Автоматический расчёт по количеству человек

Интеграции

Wallet + Payment — корпоративный счёт или split-платежи
InventoryManagementService — списание продуктов
RecommendationService — персональные меню для сотрудников
Analytics — отчёт по потреблению


9. Farm Direct (Фермерские продукты напрямую)
Техническое ядро

Модели: Farm, FarmProduct, FarmBox, FarmOrder
Сервисы: FarmService, SubscriptionService
Джобы: DailyFarmDeliveryJob, QualityControlJob
События: FarmBoxCreated

Ключевые процессы

Прямая доставка от фермера
Сезонные боксы
Прозрачность (фермер → клиент)

Интеграции

InventoryManagementService — списание со склада фермера
Wallet + Payment — оплата бокса
RecommendationService — «Что берут с этим продуктом»
Promo — фермерские акции

---

# КАНОН ДЛЯ AI КОНСТРУКТОРОВ И ML-АНАЛИЗА (2026)

## AI Конструкторы (обязательны для каждой вертикали)

Каждая вертикаль должна иметь AI-конструктор для создания/дизайна своих товаров/услуг:

### Типы AI-конструкторов по вертикалям

1. **Beauty** — AI-конструктор причёсок и макияжа
   - Загрузить фото лица → получить рекомендации причёсок/цветов/стилей
   - Виртуальная примерка (AR)
   - Сохранение в профиль пользователя

2. **Furniture & Interior** — AI-дизайнер интерьера
   - Загрузить фото комнаты → получить рекомендации мебели/стилей
   - 3D-визуализация комнаты
   - Список товаров с ценами для заказа

3. **Food** — AI-конструктор меню/рецептов
   - Выбрать ингредиенты → получить рецепты
   - Подбор блюд по калорийности/диете
   - Расчёт стоимости меню

4. **Fashion/Cosmetics** — AI-подбор стиля
   - Загрузить фото → получить рекомендации одежды/косметики по типу внешности
   - Цветовой анализ

5. **Real Estate** — AI-конструктор планировки
   - Загрузить план квартиры → рекомендации дизайна и мебели
   - Расчёт стоимости ремонта

### Требования к AI-конструкторам

```php
declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Support\Facades\Log;

final readonly class AIConstructorService
{
    public function __construct(
        private readonly \OpenAI\Client $openai,  // OpenAI или GigaChat
        private readonly \App\Services\RecommendationService $recommendation,
        private readonly \App\Services\InventoryService $inventory,
    ) {}

    /**
     * Анализировать загруженное фото и дать рекомендации
     */
    public function analyzePhotoAndRecommend(
        \Illuminate\Http\UploadedFile $photo,
        string $vertical,
        int $userId,
    ): array {
        try {
            // 1. Отправить фото в AI (OpenAI Vision или GigaChat)
            $analysis = $this->openai->vision()->analyze([
                'image' => $photo->getRealPath(),
                'prompt' => "Проанализируй это фото для вертикали '{$vertical}'",
            ]);

            // 2. Получить рекомендации на основе анализа
            $recommendations = $this->recommendation->getByAnalysis(
                analysis: $analysis,
                vertical: $vertical,
                userId: $userId,
            );

            // 3. Проверить наличие товаров
            foreach ($recommendations as &$item) {
                $item['inStock'] = $this->inventory->getCurrentStock($item['product_id']) > 0;
            }

            // 4. Логировать
            Log::channel('audit')->info('AI constructor used', [
                'user_id' => $userId,
                'vertical' => $vertical,
                'recommendations_count' => count($recommendations),
            ]);

            return [
                'success' => true,
                'analysis' => $analysis,
                'recommendations' => $recommendations,
            ];
        } catch (\Throwable $e) {
            Log::channel('audit')->error('AI constructor failed', [
                'user_id' => $userId,
                'vertical' => $vertical,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Сохранить результаты конструктора в профиль пользователя
     */
    public function saveDesignToProfile(
        int $userId,
        string $vertical,
        array $design,
    ): void {
        // Сохранить в user_ai_designs таблицу
        \Illuminate\Support\Facades\DB::table('user_ai_designs')->insert([
            'user_id' => $userId,
            'vertical' => $vertical,
            'design_data' => json_encode($design),
            'created_at' => now(),
        ]);
    }
}
```

---

## ML-Анализ вкусов и предпочтений пользователя (обязателен)

### Статический анализ (сохранение в профиле)

```php
declare(strict_types=1);

namespace App\Services\ML;

use Illuminate\Support\Facades\DB;

final readonly class UserTasteAnalyzerService
{
    /**
     * Анализировать вкусы пользователя на основе:
     * - Истории покупок
     * - Просмотров товаров
     * - Оценок и отзывов
     * - Времени, проведённого на товарах
     */
    public function analyzeAndSaveUserProfile(int $userId): void
    {
        $user = \App\Models\User::findOrFail($userId);

        // 1. Анализировать категории товаров, которые смотрел пользователь
        $viewedCategories = DB::table('product_views')
            ->where('user_id', $userId)
            ->groupBy('product_category')
            ->selectRaw('product_category, COUNT(*) as count')
            ->orderByRaw('count DESC')
            ->limit(10)
            ->get()
            ->mapWithKeys(fn ($row) => [$row->product_category => $row->count / 10])  // Нормализация
            ->toArray();

        // 2. Анализировать ценовой диапазон
        $avgPrice = DB::table('orders')
            ->where('user_id', $userId)
            ->avg('total_price');

        $priceRange = match (true) {
            $avgPrice < 1000 => 'budget',
            $avgPrice < 5000 => 'mid',
            $avgPrice < 15000 => 'premium',
            default => 'luxury',
        };

        // 3. Анализировать предпочтения по размерам (для Fashion)
        $preferredSizes = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->groupBy('products.size')
            ->selectRaw('products.size, COUNT(*) as count')
            ->pluck('count', 'size')
            ->toArray();

        // 4. Анализировать предпочтения по цветам (для Fashion/Beauty)
        $preferredColors = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->groupBy('products.color')
            ->selectRaw('products.color, COUNT(*) as count')
            ->pluck('count', 'color')
            ->toArray();

        // 5. Анализировать бренды
        $preferredBrands = DB::table('order_items')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->where('order_items.user_id', $userId)
            ->groupBy('products.brand')
            ->selectRaw('products.brand, COUNT(*) as count')
            ->orderByRaw('count DESC')
            ->limit(5)
            ->pluck('count', 'brand')
            ->toArray();

        // 6. Сохранить в user_taste_profile
        $user->update([
            'taste_profile' => [
                'categories' => $viewedCategories,
                'price_range' => $priceRange,
                'preferred_sizes' => $preferredSizes,
                'preferred_colors' => $preferredColors,
                'preferred_brands' => $preferredBrands,
                'analyzed_at' => now()->toIso8601String(),
            ],
        ]);

        \Illuminate\Support\Facades\Log::channel('audit')->info('User taste profile analyzed', [
            'user_id' => $userId,
            'categories_count' => count($viewedCategories),
            'price_range' => $priceRange,
        ]);
    }
}
```

---

## Запоминание адресов доставки и поездок (обязательно)

### До 5 адресов + история

```php
declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

final class UserAddress extends Model
{
    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'type',  // home, work, other
        'address',
        'lat',
        'lon',
        'is_default',
        'usage_count',
    ];

    protected $casts = [
        'lat' => 'float',
        'lon' => 'float',
        'is_default' => 'boolean',
    ];
}

// ✅ В миграции
Schema::create('user_addresses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained('users');
    $table->enum('type', ['home', 'work', 'other'])->default('other');
    $table->string('address');
    $table->decimal('lat', 10, 8)->nullable();
    $table->decimal('lon', 11, 8)->nullable();
    $table->boolean('is_default')->default(false);
    $table->integer('usage_count')->default(0);
    $table->timestamps();

    $table->unique(['user_id', 'address']);  // Максимум 5 адресов
});
```

```php
declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

final readonly class UserAddressService
{
    /**
     * Добавить или вернуть существующий адрес (максимум 5)
     */
    public function addOrGetAddress(int $userId, string $address, string $type = 'other'): \App\Models\UserAddress
    {
        // Проверить, существует ли уже такой адрес
        $existing = \App\Models\UserAddress::where([
            'user_id' => $userId,
            'address' => $address,
        ])->first();

        if ($existing) {
            $existing->increment('usage_count');
            return $existing;
        }

        // Проверить, не превышены ли 5 адресов
        $count = \App\Models\UserAddress::where('user_id', $userId)->count();
        if ($count >= 5) {
            // Удалить наименее используемый адрес
            \App\Models\UserAddress::where('user_id', $userId)
                ->orderBy('usage_count')
                ->limit(1)
                ->delete();
        }

        // Создать новый адрес
        return \App\Models\UserAddress::create([
            'user_id' => $userId,
            'address' => $address,
            'type' => $type,
            'usage_count' => 1,
        ]);
    }

    /**
     * Получить историю поездок/доставок пользователя
     */
    public function getAddressHistory(int $userId): \Illuminate\Database\Eloquent\Collection
    {
        return \App\Models\UserAddress::where('user_id', $userId)
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get();
    }
}
```

---

## AI-Калькуляторы цен и скидок (обязательны в каждой вертикали)

```php
declare(strict_types=1);

namespace App\Services\AI;

final readonly class AIPricingCalculatorService
{
    /**
     * Калькулятор для вертикали Furniture — расчёт стоимости ремонта
     */
    public function calculateFurnitureRepairCost(array $items): array
    {
        $baseCost = 0;
        $laborCost = 0;
        $materials = [];

        foreach ($items as $item) {
            $baseCost += $item['price'] * $item['quantity'];

            // Добавить трудозатраты
            $laborCost += $item['repair_hours'] * 500;  // 500 руб/час

            // Материалы
            $materials[] = [
                'name' => $item['material'],
                'quantity' => $item['quantity'],
                'cost' => $item['material_cost'],
            ];
        }

        $totalCost = $baseCost + $laborCost;
        $discount = $this->getVolumeDiscount($baseCost);

        return [
            'base_cost' => $baseCost,
            'labor_cost' => $laborCost,
            'materials' => $materials,
            'discount' => $discount,
            'total' => max($totalCost - $discount, 0),
        ];
    }

    /**
     * Калькулятор для вертикали Beauty — стоимость услуг
     */
    public function calculateBeautyServiceCost(array $services, bool $isFirstTime = false): array
    {
        $total = 0;

        foreach ($services as $service) {
            $cost = $service['base_price'] * $service['duration_multiplier'];
            $total += $cost;
        }

        // Скидка для новых клиентов
        $discount = $isFirstTime ? (int)($total * 0.1) : 0;

        return [
            'services' => $services,
            'subtotal' => $total,
            'discount' => $discount,
            'total' => $total - $discount,
        ];
    }

    private function getVolumeDiscount(int $baseCost): int
    {
        return match (true) {
            $baseCost > 50000 => (int)($baseCost * 0.15),
            $baseCost > 20000 => (int)($baseCost * 0.10),
            $baseCost > 10000 => (int)($baseCost * 0.05),
            default => 0,
        };
    }
}
```

---

## Миграция для AI-конструкторов и профилей вкусов

```php
declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // AI-дизайны и конструкции пользователей
        Schema::create('user_ai_designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('vertical');  // furniture, beauty, food и т.д.
            $table->json('design_data');
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->comment('AI-конструкции, созданные пользователями');
        });

        // Профили вкусов пользователей
        Schema::table('users', function (Blueprint $table) {
            $table->json('taste_profile')->nullable()->after('metadata');
            $table->comment('Анализ предпочтений пользователя: категории, цены, размеры, цвета, бренды');
        });

        // История просмотров товаров (для анализа вкусов)
        Schema::create('product_views', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->string('product_category');
            $table->integer('duration_seconds')->default(0);
            $table->string('correlation_id')->nullable()->index();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->comment('История просмотров товаров для анализа интересов');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_views');
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('taste_profile');
        });
        Schema::dropIfExists('user_ai_designs');
    }
};
```

---

## B2C vs B2B РЕЖИМЫ (2026)

### B2C Режим (Consumer-to-Business / физические лица)
```php
$isB2C = !$request->has('inn') || !$request->has('business_card_id');

if ($isB2C) {
    // B2C режим: розничные цены, одна корзина на продавца
    // Резерв 20 мин, товар не в наличии → чёрно-белый
    // Цена выросла → показать новую; упала → оставить старую
    
    $cart = CartService::getOrCreate($user, $seller, 'b2c');
    $prices = B2CPricingService::getPrices($productId);
}
```

### B2B Режим (Business-to-Business / юридические лица)
```php
$isB2B = $request->has('inn') && $request->has('business_card_id');

if ($isB2B) {
    // B2B режим: оптовые цены, специальные условия
    // Отдельные витрины и кредит
    
    $businessGroup = BusinessGroup::where('inn', $request->get('inn'))->first();
    $cart = CartService::getOrCreate($businessGroup, $seller, 'b2b');
    $prices = B2BPricingService::getPrices($productId, $businessGroup);
}
```

---

## ПРАВИЛО КОРЗИН (2026)

### Правила
- **1 продавец = 1 корзина**
- **Max 20 корзин** одновременно в памяти
- **Резерв 20 минут** — автоматическое снятие (CartCleanupJob)
- **Проверка наличия** при открытии корзины
- **Товар не в наличии** → чёрно-белый, без "В корзину"
- **Логика цены:** выросла → новая; упала → старая

--

# КАНОН ДЛЯ AI-КОНСТРУКТОРОВ (2026) — УНИФИЦИРОВАННЫЙ ФРЕЙМВОРК

## Общие правила AI-конструкторов (единый фреймворк для всех 52 вертикалей)

1. **AIConstructorService** — центральный оркестратор для всех вертикалей.
   - Использование **UserTasteProfile v2.0** для персонализации.
   - Обязательный **correlation_id** во всех AI-запросах.
   - Кэширование результатов генерации в Redis (TTL 3600 сек).
   - Сохранение каждого результата в таблицу `ai_constructions`.

2. **DTO: AIConstructionResult**
   ```php
   final readonly class AIConstructionResult {
       public function __construct(
           public string $vertical,
           public string $type, // 'image', 'list', 'design', 'calculation'
           public array $payload, // Основные данные
           public array $suggestions, // Рекомендованные товары из Inventory
           public float $confidence_score,
           public string $correlation_id
       ) {}
   }
   ```

3. **Обязательная интеграция**:
   - **InventoryManagementService**: Проверка наличия предложенных AI товаров.
   - **RecommendationService**: Уточнение выдачи на базе профиля вкусов.
   - **FraudMLService**: Блокировка подозрительных массовых генераций.
   - **WalletService**: Списание квот за "Heavy AI" запросы.

## Детализированные особенности AI-конструкторов по вертикалям

### 1. Beauty & Wellness (Красота)
- **AI-анализ лица (Vision)**: Определение цветотипа, формы лица, состояния кожи.
- **Генерация**: Подбор причёсок, макияжа и ухода.
- **Output**: Фото-превью (Stable Diffusion / DALL-E) + список услуг и косметики.
- **Инфо**: "Ваш идеальный образ на базе анализа 15 параметров лица".

### 2. Furniture & Interior (Интерьер)
- **Vision-мерчандайзинг**: Анализ пустого или жилого пространства с фото.
- **Генерация**: 3D-расстановка мебели в выбранном стиле (сканди, лофт и т.д.).
- **Output**: Спецификация мебели со ссылками на Inventory текущего tenant.
- **Инфо**: "AI-дизайнер подобрал 12 предметов мебели под ваш метраж".

### 3. Fashion (Одежда и стиль)
- **Body-score**: Анализ параметров фигуры (рост, тип).
- **Генерация**: Готовые капсулы (Outfit) на неделю или под событие.
- **Output**: Список товаров (SKU) + визуализация "Lookbook".

### 4. Food & Restaurants (Еда)
- **AI-шеф**: Генерация рецепта или состава блюда по фото ингредиентов.
- **Генерация**: Кастомизация состава (без аллергенов, КБЖУ под цель).
- **Output**: Итоговая цена (AIPricingCalculator) + КБЖУ.

### 5. Medical & Clinic (Медицина)
- **AI-диагност (вспомогательный)**: Предварительная сортировка по симптомам (Triage).
- **Генерация**: Рекомендованный план анализов и выбор профильного специалиста.
- **Output**: Предварительная запись (Appointment) + памятка.

### 6. Auto (Автомобили)
- **AI-тюнинг**: Визуализация дисков, цвета кузова и обвесов на фото авто.
- **Vision-дефектовка**: Оценка повреждений по фото для предварительной сметы СТО.
- **Output**: Смета запчастей из Inventory + стоимость работ.

### 7. Education (Обучение)
- **AI-куратор**: Генерация индивидуальной траектории обучения (Roadmap).
- **Генерация**: Тесты и упражнения на базе текущего прогресса.
- **Output**: План занятий на 30 дней.

### 8. RealEstate (Недвижимость)
- **AI-реноватор**: Виртуальный ремонт в серых стенах новостройки.
- **Vision-оценка**: Сравнение объекта с похожими по фото отделки.
- **Output**: Прогноз арендной ставки или цены продажи (DemandForecast).

---

**Версия:** 1.3 (AI Constructor Framework)  
**Дата:** 25.03.2026  
**Статус:** PRODUCTION READY
