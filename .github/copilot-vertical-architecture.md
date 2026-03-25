declare(strict_types=1);

# АРХИТЕКТУРА ВЕРТИКАЛЕЙ 2026 — 9-СЛОЙНАЯ СТРУКТУРА

## 📐 Общая архитектура всех вертикалей

Каждая вертикаль должна следовать **СТРОГОЙ 9-слойной архитектуре**:

```
app/Domains/{VerticalName}/
├── Models/                          [LAYER 1: DATA]
│   ├── MainEntity.php
│   ├── RelatedEntity1.php
│   └── RelatedEntity2.php
├── DTOs/                            [LAYER 2: TRANSFER OBJECTS]
│   ├── CreateMainEntityDto.php
│   ├── UpdateMainEntityDto.php
│   └── MainEntityQueryDto.php
├── Services/                        [LAYER 3: BUSINESS LOGIC]
│   ├── MainEntityService.php
│   ├── RelatedService.php
│   └── CalculationService.php
├── Requests/                        [LAYER 4: VALIDATION]
│   ├── CreateMainEntityRequest.php
│   ├── UpdateMainEntityRequest.php
│   └── ListMainEntityRequest.php
├── Resources/                       [LAYER 5: API RESPONSE]
│   ├── MainEntityResource.php
│   └── MainEntityCollectionResource.php
├── Events/                          [LAYER 6: EVENTS]
│   ├── MainEntityCreatedEvent.php
│   ├── MainEntityUpdatedEvent.php
│   └── MainEntityDeletedEvent.php
├── Listeners/                       [LAYER 7: EVENT HANDLERS]
│   ├── NotifyOnMainEntityCreated.php
│   ├── UpdateInventoryOnMainEntity.php
│   └── LogMainEntityEvent.php
├── Jobs/                            [LAYER 8: ASYNC PROCESSING]
│   ├── ProcessMainEntityJob.php
│   └── SendNotificationJob.php
└── Filament/                        [LAYER 9: ADMIN INTERFACE]
    ├── Resources/
    │   ├── MainEntityResource.php
    │   └── RelatedEntityResource.php
    └── Pages/
        ├── Dashboard.php
        └── Analytics.php
```

---

## 🔵 LAYER 1: MODELS (Данные)

### Требования к моделям:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class MainEntity extends Model
{
    protected $table = '{vertical}_main_entities';

    protected $fillable = [
        'tenant_id',
        'business_group_id',
        'uuid',
        'correlation_id',
        'name',
        'status',
        'tags',
    ];

    protected $hidden = [
        'password',
        'token',
        'secret',
    ];

    protected $casts = [
        'tags' => 'json',
        'metadata' => 'json',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        // Global scope для tenant scoping
        static::addGlobalScope('tenant', function ($query) {
            if (auth()->check() && auth()->user()->tenant_id) {
                $query->where('tenant_id', auth()->user()->tenant_id);
            }
        });

        // Auto-fill uuid при создании
        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = \Illuminate\Support\Str::uuid();
            }
        });
    }

    // Отношения
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Tenant::class);
    }

    public function businessGroup(): BelongsTo
    {
        return $this->belongsTo(\App\Models\BusinessGroup::class, 'business_group_id');
    }

    public function relatedEntities(): HasMany
    {
        return $this->hasMany(RelatedEntity::class);
    }

    // Accessors
    public function getDisplayNameAttribute(): string
    {
        return "{$this->name} ({$this->id})";
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }
}
```

**Требования ОБЯЗАТЕЛЬНЫ:**
- ✅ `$table` явно указан
- ✅ `$fillable` полностью заполнен (все поля)
- ✅ `$hidden` содержит чувствительные поля
- ✅ `$casts` для всех JSON/boolean полей
- ✅ `booted()` с global scope для tenant_id
- ✅ Все отношения определены явно
- ✅ Scopes для фильтрации

---

## 🟣 LAYER 2: DTOs (Transfer Objects)

### Требования к DTOs:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\DTOs;

use Illuminate\Http\Request;

final readonly class CreateMainEntityDto
{
    public function __construct(
        public int $tenantId,
        public ?int $businessGroupId,
        public string $name,
        public string $status,
        public array $tags = [],
        public ?string $correlationId = null,
        public ?string $idempotencyKey = null,
    ) {}

    public static function from(Request $request): self
    {
        return new self(
            tenantId: auth()->user()->tenant_id,
            businessGroupId: $request->input('business_group_id'),
            name: $request->input('name'),
            status: $request->input('status', 'draft'),
            tags: $request->input('tags', []),
            correlationId: $request->header('X-Correlation-ID') ?? \Illuminate\Support\Str::uuid(),
            idempotencyKey: $request->header('Idempotency-Key'),
        );
    }

    public function toArray(): array
    {
        return [
            'tenant_id' => $this->tenantId,
            'business_group_id' => $this->businessGroupId,
            'name' => $this->name,
            'status' => $this->status,
            'tags' => $this->tags,
            'correlation_id' => $this->correlationId,
        ];
    }
}
```

**Требования ОБЯЗАТЕЛЬНЫ:**
- ✅ `final readonly class`
- ✅ Все параметры в конструкторе с типами
- ✅ Метод `from(Request)` для создания из request
- ✅ Метод `toArray()` для конвертации

---

## 🔴 LAYER 3: SERVICES (Бизнес-логика)

### Требования к сервисам:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\Collection;

final readonly class MainEntityService
{
    public function __construct(
        private readonly \App\Services\FraudControlService $fraud,
        private readonly \App\Services\AuditService $audit,
        private readonly \App\Services\IdempotencyService $idempotency,
    ) {}

    /**
     * Создание сущности
     *
     * @throws \App\Exceptions\FraudException
     * @throws \App\Exceptions\InvalidDataException
     */
    public function create(CreateMainEntityDto $dto): \App\Domains\{Vertical}\Models\MainEntity | never
    {
        // 1. Проверка idempotency
        if ($dto->idempotencyKey) {
            $cached = $this->idempotency->get($dto->idempotencyKey);
            if ($cached) {
                return $cached;
            }
        }

        // 2. Fraud check (обязательно!)
        $fraudScore = $this->fraud->check($dto);
        if ($fraudScore > 0.8) {
            throw new \App\Exceptions\FraudException('Suspicious activity');
        }

        // 3. Транзакция (обязательно!)
        return DB::transaction(function () use ($dto) {
            $entity = new \App\Domains\{Vertical}\Models\MainEntity($dto->toArray());
            $entity->uuid = \Illuminate\Support\Str::uuid();
            $entity->save();

            // 4. Аудит (обязательно!)
            Log::channel('audit')->info('Entity created', [
                'entity_id' => $entity->id,
                'correlation_id' => $dto->correlationId,
                'tenant_id' => $dto->tenantId,
                'data' => $dto->toArray(),
            ]);

            // 5. Idempotency cache
            if ($dto->idempotencyKey) {
                $this->idempotency->set($dto->idempotencyKey, $entity);
            }

            // 6. Event dispatch
            event(new \App\Domains\{Vertical}\Events\MainEntityCreatedEvent($entity, $dto->correlationId));

            return $entity;
        });
    }

    /**
     * Получение сущности
     */
    public function getById(int $id): \App\Domains\{Vertical}\Models\MainEntity | never
    {
        return \App\Domains\{Vertical}\Models\MainEntity::findOrFail($id);
    }

    /**
     * Обновление сущности
     */
    public function update(int $id, UpdateMainEntityDto $dto): \App\Domains\{Vertical}\Models\MainEntity | never
    {
        return DB::transaction(function () use ($id, $dto) {
            $entity = $this->getById($id);
            $before = $entity->toArray();

            $entity->update($dto->toArray());

            Log::channel('audit')->info('Entity updated', [
                'entity_id' => $entity->id,
                'correlation_id' => $dto->correlationId,
                'before' => $before,
                'after' => $entity->toArray(),
            ]);

            event(new \App\Domains\{Vertical}\Events\MainEntityUpdatedEvent($entity, $dto->correlationId));

            return $entity;
        });
    }

    /**
     * Удаление сущности
     */
    public function delete(int $id, string $correlationId): bool | never
    {
        return DB::transaction(function () use ($id, $correlationId) {
            $entity = $this->getById($id);
            $before = $entity->toArray();

            $entity->delete();

            Log::channel('audit')->info('Entity deleted', [
                'entity_id' => $id,
                'correlation_id' => $correlationId,
                'deleted_data' => $before,
            ]);

            event(new \App\Domains\{Vertical}\Events\MainEntityDeletedEvent($id, $correlationId));

            return true;
        });
    }

    /**
     * Список сущностей
     */
    public function list(MainEntityQueryDto $dto): Collection
    {
        $query = \App\Domains\{Vertical}\Models\MainEntity::query();

        if ($dto->status) {
            $query->byStatus($dto->status);
        }

        if ($dto->search) {
            $query->where('name', 'like', "%{$dto->search}%");
        }

        return $query
            ->orderBy('created_at', 'desc')
            ->paginate($dto->perPage)
            ->items();
    }
}
```

**Требования ОБЯЗАТЕЛЬНЫ:**
- ✅ `final readonly class`
- ✅ Constructor injection с readonly зависимостями
- ✅ DB::transaction() для всех мутаций
- ✅ FraudControlService::check() перед создание/изменением
- ✅ Log::channel('audit') для всех операций
- ✅ Event dispatch после мутаций
- ✅ Idempotency check для критичных операций
- ✅ Методы возвращают конкретный тип или never

---

## 🟠 LAYER 4: REQUESTS (Валидация)

### Требования к FormRequest:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class CreateMainEntityRequest extends FormRequest
{
    public function authorize(): bool
    {
        // 1. Проверка прав
        if (!auth()->user() || !auth()->user()->can('create', \App\Domains\{Vertical}\Models\MainEntity::class)) {
            return false;
        }

        // 2. Fraud check (для критичных операций)
        try {
            $dto = CreateMainEntityDto::from($this);
            \App\Services\FraudControlService::check($dto);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::channel('fraud_alert')->warning('Fraud detected', [
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);
            return false;
        }

        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'status' => ['required', 'in:draft,active,archived'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'Имя сущности обязательно',
            'status.required' => 'Статус обязателен',
            'status.in' => 'Неверный статус',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Очистка данных перед валидацией
        if ($this->has('tags') && is_string($this->tags)) {
            $this->merge([
                'tags' => json_decode($this->tags, true) ?? [],
            ]);
        }
    }
}
```

**Требования ОБЯЗАТЕЛЬНЫ:**
- ✅ `authorize()` с проверкой прав И fraud-check
- ✅ `rules()` с полной валидацией
- ✅ `messages()` с понятными сообщениями об ошибках
- ✅ `prepareForValidation()` для очистки данных

---

## 🟡 LAYER 5: RESOURCES (API Response)

### Требования к Resources:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class MainEntityResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'name' => $this->name,
            'status' => $this->status,
            'tags' => $this->tags,
            'tenant_id' => $this->tenant_id,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }

    public function with(Request $request): array
    {
        return [
            'success' => true,
            'correlation_id' => $request->header('X-Correlation-ID'),
        ];
    }
}
```

---

## 🟢 LAYER 6: EVENTS (События)

### Требования к Events:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

final class MainEntityCreatedEvent
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly \App\Domains\{Vertical}\Models\MainEntity $entity,
        public readonly string $correlationId,
    ) {}
}
```

---

## 🔵 LAYER 7: LISTENERS (Обработчики событий)

### Требования к Listeners:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Listeners;

use Illuminate\Support\Facades\Log;

final readonly class NotifyOnMainEntityCreated
{
    public function handle(\App\Domains\{Vertical}\Events\MainEntityCreatedEvent $event): void
    {
        Log::channel('audit')->info('Entity created notification', [
            'entity_id' => $event->entity->id,
            'correlation_id' => $event->correlationId,
        ]);

        // Отправка уведомлений, запуск jobs и т.д.
    }
}
```

---

## 🟣 LAYER 8: JOBS (Асинхронная обработка)

### Требования к Jobs:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

final class ProcessMainEntityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $entityId,
        public readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        try {
            DB::transaction(function () {
                $entity = \App\Domains\{Vertical}\Models\MainEntity::findOrFail($this->entityId);

                Log::channel('audit')->info('Job processing', [
                    'entity_id' => $entity->id,
                    'correlation_id' => $this->correlationId,
                ]);

                // Логика обработки
            });
        } catch (\Throwable $e) {
            Log::channel('audit')->error('Job failed', [
                'entity_id' => $this->entityId,
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
```

---

## 🔴 LAYER 9: FILAMENT RESOURCES (Admin Interface)

### Требования к Filament Resources:

```php
declare(strict_types=1);

namespace App\Domains\{Vertical}\Filament\Resources;

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;

final class MainEntityResource extends Resource
{
    protected static ?string $model = \App\Domains\{Vertical}\Models\MainEntity::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            \Filament\Forms\Components\TextInput::make('name')
                ->required()
                ->maxLength(255),
            \Filament\Forms\Components\Select::make('status')
                ->required()
                ->options([
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'archived' => 'Archived',
                ]),
            \Filament\Forms\Components\TagsInput::make('tags'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                \Filament\Tables\Columns\TextColumn::make('id')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('name')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('status')->sortable(),
                \Filament\Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'active' => 'Active',
                        'archived' => 'Archived',
                    ]),
            ])
            ->actions([
                \Filament\Tables\Actions\EditAction::make(),
                \Filament\Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with(['relatedEntities']);
    }
}
```

---

## 📋 ЧЕКЛИСТ ВЕРТИКАЛИ

- [ ] Layer 1: Модели с $fillable, $hidden, $casts, booted()
- [ ] Layer 2: DTOs с from() и toArray()
- [ ] Layer 3: Сервисы с constructor injection и DB::transaction()
- [ ] Layer 4: FormRequest с authorize() и fraud-check
- [ ] Layer 5: Resources с toArray() и with()
- [ ] Layer 6: Events с Dispatchable
- [ ] Layer 7: Listeners обрабатывающие события
- [ ] Layer 8: Jobs асинхронные с correlation_id
- [ ] Layer 9: Filament Resources с полными form()/table()

---

**Автор:** Vertical Architecture 2026  
**Версия:** 1.0  
**Дата:** 25.03.2026  
**Статус:** PRODUCTION READY
