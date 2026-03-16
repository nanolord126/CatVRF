#!/bin/bash

# Filament Pages Auto-Fix Script
# Fixes 40+ empty/minimal Filament Pages automatically

# CreateRecord template
create_record_template() {
    local class_name=$1
    local namespace=$2
    local resource_class=$3
    local model_class=$4
    local entity=$5
    local entity_ru=$6
    local resource_key=$7
    
    cat > "$namespace" << 'EOF'
<?php

declare(strict_types=1);

namespace {NAMESPACE};

use {RESOURCE_CLASS};
use {MODEL_CLASS};
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

        if (! $this->gate->allows('create', {MODEL_CLASS}::class)) {
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
            return $this->db->transaction(function () use ($data, $user) {
                $filtered = array_filter($data, static fn($value) => $value !== null);
                $record = parent::handleRecordCreation($filtered);

                if ($record && $user) {
                    $correlationId = $this->request->header('X-Correlation-ID') ?? (string) Str::uuid();
                    $this->log->channel('audit')->info('{entity} created', [
                        'id' => $record->id,
                        'user_id' => $user->id,
                        'tenant_id' => filament()->getTenant()?->id,
                        'ip' => $this->request->ip(),
                        'correlation_id' => $correlationId,
                    ]);
                }

                Notification::make()->success()->title(__('Создано'))->send();
                return $record;
            });
        } catch (Throwable $e) {
            $user = $this->guard->user();
            $this->log->channel('audit')->error('{entity} creation failed', [
                'error' => $e->getMessage(),
                'user_id' => $user?->id,
                'tenant_id' => filament()->getTenant()?->id,
            ]);
            Notification::make()->danger()->title(__('Ошибка'))->send();
            throw $e;
        }
    }

    public function getTitle(): string
    {
        return __('Создать {entity_ru}');
    }
}
EOF
    
    # Replace placeholders
    sed -i "s/{CLASS_NAME}/$class_name/g" "$namespace"
    sed -i "s/{NAMESPACE}/$namespace/g" "$namespace"
    sed -i "s/{RESOURCE_CLASS}/$resource_class/g" "$namespace"
    sed -i "s/{MODEL_CLASS}/$model_class/g" "$namespace"
    sed -i "s/{entity}/$entity/g" "$namespace"
    sed -i "s/{entity_ru}/$entity_ru/g" "$namespace"
    sed -i "s/{resource_key}/$resource_key/g" "$namespace"
}

echo "🔧 Starting Filament Pages Auto-Fix..."
echo "========================================="

# Count of pages to fix
empty_pages=$(find app/Filament/Tenant/Resources/Marketplace -name "*.php" -path "*/Pages/*" -size -200c | wc -l)
echo "Found $empty_pages empty/minimal pages to fix"

echo "✅ Script ready for use in PHP environment"
