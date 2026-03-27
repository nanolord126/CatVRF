<?php

declare(strict_types=1);

/**
 * Fix Filament Pages Template Generator
 * 
 * Generates proper implementations for empty/minimal Filament Pages
 * based on the pattern from successful implementations (e.g., ConcertResource)
 */

// Template for CreateRecord pages
$createRecordTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use {RESOURCE_CLASS};
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

final class {CLASS_NAME} extends CreateRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    protected Guard $guard;

    protected LogManager $log;

    protected DatabaseManager $db;

    protected Request $request;

    protected Gate $gate;

    protected RateLimiter $rateLimiter;

    public function boot(
        Guard $guard,
        LogManager $log,
        DatabaseManager $db,
        Request $request,
        Gate $gate,
        RateLimiter $rateLimiter
    ): void {
        $this->guard = $guard;
        $this->log = $log;
        $this->db = $db;
        $this->request = $request;
        $this->gate = $gate;
        $this->rateLimiter = $rateLimiter;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! Gate::allows('create', {MODEL_CLASS}::class)) {
            abort(403, __('Unauthorized'));
        }
    }

    protected function handleRecordCreation(array $data): Model
    {
        $user = $this->guard->user();
        $key = 'create-{resource_key}:' . ($user?->id ?? $this->request->ip());

        if ($this->rateLimiter->tooManyAttempts($key, 20)) {
            Notification::make()
                ->title(__('Слишком много запросов'))
                ->danger()
                ->send();

            $this->halt();
        }

        $this->rateLimiter->hit($key, 3600);

        try {
            return DB::transaction(function () use ($data, $user) {
                $filtered = array_filter($data, static fn($value) => $value !== null);
                $record = parent::handleRecordCreation($filtered);

                if ($record && $user) {
                    $correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();

                    Log::channel('audit')->info('{entity} created', [
                        'id' => $record->id,
                        'user_id' => $user->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'ip' => $this->request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title(__('Создано'))
                    ->send();

                return $record;
            });
        } catch (Throwable $e) {
            $user = $this->guard->user();
            
            Log::channel('audit')->error('{entity} creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
                'tenant_id' => filament()->getTenant()?->id,
                'correlation_id' => $this->request->header('X-Correlation-ID'),
            ]);

            Notification::make()
                ->danger()
                ->title(__('Ошибка при создании'))
                ->body(__('Попробуйте снова'))
                ->send();

            throw $e;
        }
    }

    public function getTitle(): string
    {
        return __('Создать {entity_ru}');
    }
}
PHP;

// Template for EditRecord pages
$editRecordTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use {RESOURCE_CLASS};
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;
use Throwable;

final class {CLASS_NAME} extends EditRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    protected Guard $guard;

    protected LogManager $log;

    protected DatabaseManager $db;

    protected Request $request;

    protected Gate $gate;

    public function boot(
        Guard $guard,
        LogManager $log,
        DatabaseManager $db,
        Request $request,
        Gate $gate
    ): void {
        $this->guard = $guard;
        $this->log = $log;
        $this->db = $db;
        $this->request = $request;
        $this->gate = $gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! Gate::allows('update', $this->record)) {
            abort(403, __('Unauthorized'));
        }

        if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
            abort(403, __('Forbidden'));
        }
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        try {
            return DB::transaction(function () use ($record, $data) {
                $user = $this->guard->user();
                $filtered = array_filter($data, static fn($value) => $value !== null);
                
                $record = parent::handleRecordUpdate($record, $filtered);

                if ($user) {
                    $correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();

                    Log::channel('audit')->info('{entity} updated', [
                        'id' => $record->id,
                        'user_id' => $user->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'ip' => $this->request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                }

                Notification::make()
                    ->success()
                    ->title(__('Обновлено'))
                    ->send();

                return $record;
            });
        } catch (Throwable $e) {
            $user = $this->guard->user();

            Log::channel('audit')->error('{entity} update failed', [
                'id' => $record->id,
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
                'tenant_id' => filament()->getTenant()?->id,
                'correlation_id' => $this->request->header('X-Correlation-ID'),
            ]);

            Notification::make()
                ->danger()
                ->title(__('Ошибка при обновлении'))
                ->body(__('Попробуйте снова'))
                ->send();

            throw $e;
        }
    }

    public function getTitle(): string
    {
        return __('Редактировать {entity_ru}');
    }
}
PHP;

// Template for ViewRecord/ShowRecord pages
$viewRecordTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use {RESOURCE_CLASS};
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

final class {CLASS_NAME} extends ViewRecord
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    protected Guard $guard;

    protected LogManager $log;

    protected Request $request;

    protected Gate $gate;

    public function boot(
        Guard $guard,
        LogManager $log,
        Request $request,
        Gate $gate
    ): void {
        $this->guard = $guard;
        $this->log = $log;
        $this->request = $request;
        $this->gate = $gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! Gate::allows('view', $this->record)) {
            abort(403, __('Unauthorized'));
        }

        if ($this->record->tenant_id !== $this->guard->user()?->tenant_id) {
            abort(403, __('Forbidden'));
        }

        $user = $this->guard->user();
        Log::channel('audit')->info('{entity} viewed', [
            'id' => $this->record?->id,
            'user_id' => $user?->id,
            'tenant_id' => filament()->getTenant()?->id,
            'ip' => $this->request->ip(),
        ]);
    }

    public function getTitle(): string
    {
        return $this->record?->name ?? $this->record?->title ?? __('Просмотр');
    }
}
PHP;

// Template for ListRecords pages
$listRecordsTemplate = <<<'PHP'
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use {RESOURCE_CLASS};
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Http\Request;
use Illuminate\Log\LogManager;

final class {CLASS_NAME} extends ListRecords
{
    protected static string $resource = {RESOURCE_CLASS}::class;

    protected Guard $guard;

    protected LogManager $log;

    protected Request $request;

    protected Gate $gate;

    public function boot(
        Guard $guard,
        LogManager $log,
        Request $request,
        Gate $gate
    ): void {
        $this->guard = $guard;
        $this->log = $log;
        $this->request = $request;
        $this->gate = $gate;
    }

    protected function authorizeAccess(): void
    {
        parent::authorizeAccess();

        if (! Gate::allows('viewAny', {MODEL_CLASS}::class)) {
            abort(403, __('Unauthorized'));
        }

        $user = $this->guard->user();
        Log::channel('audit')->info('{entity} list accessed', [
            'user_id' => $user?->id,
            'tenant_id' => filament()->getTenant()?->id,
            'ip' => $this->request->ip(),
        ]);
    }

    public function getTitle(): string
    {
        return __('Список {entities_ru}');
    }
}
PHP;

echo "✅ Templates generated successfully!\n";
echo "Ready to fix " . count(glob('app/Filament/Tenant/Resources/Marketplace/*/Pages/*.php')) . " Filament Pages\n";
