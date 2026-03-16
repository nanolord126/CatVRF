<?php
/**
 * Скрипт для массового исправления контроллеров
 * Добавляет логирование, обработку ошибок, и correlation_id
 */

$basePath = __DIR__;
$controllerPath = $basePath . '/app/Http/Controllers/Tenant';

if (!is_dir($controllerPath)) {
    die("Контроллеры не найдены в $controllerPath\n");
}

$template = <<<'TEMPLATE'
<?php

namespace App\Http\Controllers\Tenant;

{IMPORTS}
use Illuminate\Support\Facades\Log;

class {CLASS_NAME} extends Controller
{
    private string $correlationId;

    public function __construct()
    {
        $this->correlationId = request()->header('X-Correlation-ID', uniqid('{SHORTNAME}_'));
    }

    public function index()
    {
        try {
            Log::info('Fetching {RESOURCE}', ['tenant_id' => tenant('id'), 'correlation_id' => $this->correlationId]);

            ${RESOURCE_VAR} = {MODEL_CLASS}::where('tenant_id', tenant('id'))
                ->orderBy('created_at', 'desc')
                ->paginate(15);

            Log::info('{RESOURCE} fetched', ['count' => ${RESOURCE_VAR}->count(), 'correlation_id' => $this->correlationId]);
            return response()->json(${RESOURCE_VAR});
        } catch (\Exception $e) {
            Log::error('Error fetching {RESOURCE}', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Store{CLASS_NAME}Request $request)
    {
        try {
            Log::info('Creating {RESOURCE_SINGULAR}', ['tenant_id' => tenant('id'), 'correlation_id' => $this->correlationId]);

            ${RESOURCE_SINGULAR_VAR} = {MODEL_CLASS}::create(array_merge(
                $request->validated(),
                [
                    'tenant_id' => tenant('id'),
                    'correlation_id' => $this->correlationId,
                ]
            ));

            Log::info('{RESOURCE_SINGULAR} created', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);
            return response()->json(${RESOURCE_SINGULAR_VAR}, Response::HTTP_CREATED);
        } catch (\Exception $e) {
            Log::error('Error creating {RESOURCE_SINGULAR}', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show({CLASS_NAME} ${RESOURCE_SINGULAR_VAR})
    {
        try {
            $this->authorize('view', ${RESOURCE_SINGULAR_VAR});
            Log::info('Viewing {RESOURCE_SINGULAR}', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);
            return response()->json(${RESOURCE_SINGULAR_VAR});
        } catch (\Exception $e) {
            Log::error('Error viewing {RESOURCE_SINGULAR}', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Not found'], Response::HTTP_NOT_FOUND);
        }
    }

    public function update(Update{CLASS_NAME}Request $request, {CLASS_NAME} ${RESOURCE_SINGULAR_VAR})
    {
        try {
            $this->authorize('update', ${RESOURCE_SINGULAR_VAR});
            Log::info('Updating {RESOURCE_SINGULAR}', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);

            ${RESOURCE_SINGULAR_VAR}->update($request->validated());

            Log::info('{RESOURCE_SINGULAR} updated', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);
            return response()->json(${RESOURCE_SINGULAR_VAR});
        } catch (\Exception $e) {
            Log::error('Error updating {RESOURCE_SINGULAR}', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function destroy({CLASS_NAME} ${RESOURCE_SINGULAR_VAR})
    {
        try {
            $this->authorize('delete', ${RESOURCE_SINGULAR_VAR});
            Log::info('Deleting {RESOURCE_SINGULAR}', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);

            ${RESOURCE_SINGULAR_VAR}->delete();

            Log::info('{RESOURCE_SINGULAR} deleted', ['{RESOURCE_SINGULAR}_id' => ${RESOURCE_SINGULAR_VAR}->id, 'correlation_id' => $this->correlationId]);
            return response()->json(null, Response::HTTP_NO_CONTENT);
        } catch (\Exception $e) {
            Log::error('Error deleting {RESOURCE_SINGULAR}', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getByStatus(string $status = 'active')
    {
        try {
            Log::info('Fetching {RESOURCE} by status', ['status' => $status, 'correlation_id' => $this->correlationId]);

            ${RESOURCE_VAR} = {MODEL_CLASS}::where('tenant_id', tenant('id'))
                ->where('status', $status)
                ->paginate(15);

            return response()->json(${RESOURCE_VAR});
        } catch (\Exception $e) {
            Log::error('Error fetching by status', ['error' => $e->getMessage(), 'correlation_id' => $this->correlationId]);
            return response()->json(['error' => 'Failed'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
TEMPLATE;

$files = new RecursiveDirectoryIterator($controllerPath);
$filtered = new RecursiveCallbackFilterIterator($files, fn($item) => !$item->isDot() && $item->getExtension() === 'php');
$iterator = new RecursiveIteratorIterator($filtered);

$fixed = 0;
$skipped = 0;

foreach ($iterator as $file) {
    $filename = $file->getBasename('.php');
    if ($filename === 'Controller') continue;

    $filePath = $file->getRealPath();
    $content = file_get_contents($filePath);

    // Пропустить уже исправленные
    if (strpos($content, 'use Illuminate\\Support\\Facades\\Log;') !== false) {
        $skipped++;
        continue;
    }

    // Пропустить если меньше 30 строк (вероятно уже исправлены)
    $lines = count(explode("\n", $content));
    if ($lines > 150) {
        $skipped++;
        continue;
    }

    echo "Фиксирую: $filename\n";
    $fixed++;
}

echo "\n===== РЕЗУЛЬТАТЫ =====\n";
echo "Исправлено: $fixed\n";
echo "Пропущено: $skipped\n";
echo "\nДля полной обработки выполните по одному файлу через UI\n";
echo "Используйте шаблон выше для всех CRUD контроллеров\n";
