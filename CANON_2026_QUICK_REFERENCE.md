# CANON 2026 QUICK REFERENCE GUIDE

## 🚀 QUICK START

### Database Setup

```bash
php artisan migrate:fresh --seed
```

### Access Filament Admin

```
URL: /admin
Resources: 12 complete CRUD resources
Scoping: Auto-applied to active tenant
```

### Service Usage Pattern

```php
<?php
use App\Domains\Auto\Services\SurgeService;
use Illuminate\Support\Facades\Log;

$service = app(SurgeService::class);
$multiplier = $service->getSurgeMultiplier(
    latitude: 55.7558,
    longitude: 37.6173,
    correlationId: (string) Str::uuid()
);

// Logs to storage/logs/audit.log with correlation_id
```

---

## 📂 FILE STRUCTURE

### Services (Production-Ready)

```
app/Domains/{Vertical}/Services/{Service}Service.php
├── Constructor: readonly DI
├── All mutations: DB::transaction()
├── Logging: Log::channel('audit') with correlation_id
└── Patterns: SurgeService, ConsumableDeductionService, etc.
```

### Filament Resources (Full CRUD)

```
app/Filament/Tenant/Resources/{Vertical}/{Resource}Resource/
├── {Resource}Resource.php (form, table, queries)
├── Pages/
│   ├── List{Resource}s.php
│   ├── Create{Resource}.php
│   ├── View{Resource}.php
│   └── Edit{Resource}.php
└── Scoping: filament()->getTenant()->id
```

### Models (Tenant-Scoped)

```
app/Domains/{Vertical}/Models/{Model}.php
├── Fields: uuid, tenant_id, business_group_id, correlation_id, tags
├── Scoping: Global scope in booted()
├── Casts: Proper JSON/int/bool types
└── Relations: Eager loading ready
```

### Migrations (Idempotent)

```
database/migrations/2026_03_18_000XXX_create_{table}.php
├── Check: Schema::hasTable() before create
├── Fields: Standard tenant fields + domain-specific
└── Down: dropIfExists() safely
```

---

## 🔑 KEY PATTERNS

### Service Pattern (All 35 services)

```php
<?php declare(strict_types=1);

namespace App\Domains\{Domain}\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class {Service}Service
{
    public function __construct(
        private readonly DependencyClass $dependency,
    ) {}

    public function businessMethod(string $correlationId): ReturnType
    {
        try {
            $result = DB::transaction(function () use ($correlationId) {
                // Business logic
                Log::channel('audit')->info('Action', [
                    'action' => 'operation_name',
                    'correlation_id' => $correlationId,
                    'data' => $data,
                ]);
                return $result;
            });
            return $result;
        } catch (\Exception $e) {
            Log::channel('audit')->error('Failed', [
                'correlation_id' => $correlationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }
}
```

### Filament Resource Pattern (All 12 resources)

```php
<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\{Vertical};

use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms\Form;

final class {Resource}Resource extends Resource
{
    protected static ?string $model = {Model}::class;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Grid::make(2)->schema([
                // Fields
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            // Columns
        ])->filters([
            // Filters
        ]);
    }

    protected static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->where('tenant_id', filament()->getTenant()->id)
            ->with(['relations']);
    }
}
```

### Model Pattern (All 45+ models)

```php
<?php declare(strict_types=1);

namespace App\Domains\{Domain}\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class {Model} extends Model
{
    use SoftDeletes;

    protected $table = 'table_name';
    protected $fillable = ['uuid', 'tenant_id', 'business_group_id', ...];
    protected $hidden = ['password', 'token'];
    protected $casts = ['meta' => 'json', 'is_active' => 'boolean'];

    public function booted(): void
    {
        $this->addGlobalScope('tenant', 
            fn ($q) => $q->where('tenant_id', filament()?->getTenant()?->id ?? null)
        );
    }
}
```

### Factory Pattern (All 11 restoration factories)

```php
<?php declare(strict_types=1);

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

final class {Model}Factory extends Factory
{
    protected $model = {Model}::class;

    public function definition(): array
    {
        return [
            'uuid' => $this->faker->uuid(),
            'tenant_id' => $this->faker->randomNumber(2),
            'business_group_id' => $this->faker->randomNumber(2),
            'name' => $this->faker->name(),
            'tags' => ['test', 'seeded'],
            'correlation_id' => $this->faker->uuid(),
        ];
    }
}
```

---

## 🔍 DEBUGGING CHECKLIST

### Service Issues

- [ ] `DB::transaction()` wrapping all mutations?
- [ ] `Log::channel('audit')` logging with correlation_id?
- [ ] Tenant scoping applied in queries?
- [ ] `lockForUpdate()` on critical sections?
- [ ] No null returns (exceptions instead)?

### Filament Resource Issues

- [ ] `getEloquentQuery()` has tenant filter?
- [ ] Eager loading relations included?
- [ ] Form has all required fields?
- [ ] Table has proper filters?
- [ ] 4 Page classes exist (List/Create/View/Edit)?

### Database Issues

- [ ] All migrations in database/migrations/?
- [ ] Migration names follow 2026_03_18_XXXXX format?
- [ ] `Schema::hasTable()` check in `up()`?
- [ ] `dropIfExists()` in `down()`?
- [ ] uuid, tenant_id, business_group_id present?

---

## 📊 AUDIT LOG FORMAT

All audit logs follow this structure:

```json
{
    "message": "Action performed successfully",
    "context": {
        "action": "create_order",
        "correlation_id": "550e8400-e29b-41d4-a716-446655440000",
        "user_id": 123,
        "tenant_id": 456,
        "data": {...},
        "timestamp": "2026-03-18T12:34:56+00:00"
    }
}
```

Monitor with:

```bash
tail -f storage/logs/audit.log | grep correlation_id
```

---

## 🎯 COMMON TASKS

### Create New Service

1. Create `app/Domains/{Vertical}/Services/{Service}.php`
2. Follow service pattern with readonly DI
3. Add `DB::transaction()` wrapper
4. Include audit logging with correlation_id
5. Test with: `php artisan tinker`

### Add Filament Resource

1. Create `app/Filament/Tenant/Resources/{Vertical}/{Resource}Resource.php`
2. Create `Pages/List{Resource}s.php`, `Create{Resource}.php`, etc.
3. Implement `form()`, `table()`, `getEloquentQuery()`
4. Add tenant scoping filter
5. Test: navigate to /admin/{resource}

### Create Migration

1. Create file: `database/migrations/2026_03_18_NNNNN_create_table.php`
2. Add `Schema::hasTable()` check in `up()`
3. Include: uuid, tenant_id, business_group_id, correlation_id, tags
4. Test: `php artisan migrate:fresh`

### Seed Test Data

1. Run: `php artisan db:seed`
2. Or in tinker:

   ```php
   \App\Models\ToyProduct::factory(10)->create();
   ```

---

## 🚨 IMPORTANT RULES (CANON 2026)

1. ✅ All files: UTF-8 without BOM + CRLF
2. ✅ All PHP files: `<?php declare(strict_types=1);` first line
3. ✅ All classes: `final` unless inheritance required
4. ✅ All services: Constructor DI with `readonly`
5. ✅ All mutations: `DB::transaction()` wrapped
6. ✅ All operations: `Log::channel('audit')` with correlation_id
7. ✅ All queries: Tenant scoping applied
8. ✅ All resources: Proper eager loading
9. ✅ All models: Global scope with tenant_id filter
10. ✅ All migrations: Idempotent with `Schema::hasTable()`

---

## 📞 SUPPORT

**For questions on:**

- **Services:** Check `app/Domains/{Vertical}/Services/`
- **UI/Resources:** Check `app/Filament/Tenant/Resources/`
- **Database:** Check `database/migrations/`
- **Models:** Check `app/Domains/{Vertical}/Models/`

**All 35 verticals follow the same patterns** — once you understand one service, you understand them all.

---

**Last Updated:** 18 марта 2026 г.  
**Status:** ✅ Production-Ready
