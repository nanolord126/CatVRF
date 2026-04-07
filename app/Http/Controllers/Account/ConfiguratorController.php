<?php declare(strict_types=1);

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ConfiguratorController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}


    /**
         * Дашборд конфигураторов и калькуляторов
         */
        public function dashboard(): View
        {
            $templates = ConfiguratorTemplate::where('is_active', true)->get();
            return view('account.constructor.dashboard', compact('templates'));
        }
        /**
         * Показ конкретного конструктора
         */
        public function show(string $slug): View
        {
            $template = ConfiguratorTemplate::where('slug', $slug)
                ->with(['options'])
                ->firstOrFail();
            return view("account.constructor.show", compact('template'));
        }
        /**
         * API: Сохранить проект
         */
        public function save(Request $request): JsonResponse
        {
            $correlationId = (string) Str::uuid();
            try {
                $validated = $request->validate([
                    'template_id' => 'required|exists:configurator_templates,id',
                    'project_name' => 'required|string|max:255',
                    'payload' => 'required|array',
                    'total_price' => 'required|integer',
                    'total_weight' => 'required|integer',
                ]);
                $savedConfig = $this->db->transaction(function () use ($validated, $correlationId) {
                    return SavedConfiguration::create([
                        'tenant_id' => tenant('id'),
                        'user_id' => $this->guard->id(),
                        'template_id' => $validated['template_id'],
                        'project_name' => $validated['project_name'],
                        'payload' => $validated['payload'],
                        'total_price_kopeks' => $validated['total_price'],
                        'total_weight_grams' => $validated['total_weight'],
                        'status' => 'draft',
                        'correlation_id' => $correlationId,
                    ]);
                });
                $this->logger->channel('audit')->info('Configurator project saved', [
                    'user_id' => $this->guard->id(),
                    'project_id' => $savedConfig->id,
                    'correlation_id' => $correlationId,
                ]);
                return $this->response->json([
                    'success' => true,
                    'uuid' => $savedConfig->uuid,
                    'correlation_id' => $correlationId
                ]);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->error('Failed to save configurator project', [
                    'error' => $e->getMessage(),
                    'correlation_id' => $correlationId,
                    'trace' => $e->getTraceAsString()
                ]);
                return $this->response->json([
                    'success' => false,
                    'message' => 'Ошибка сохранения проекта',
                    'correlation_id' => $correlationId
                ], 500);
            }
        }
        /**
         * API: Расчет стоимости (если требуется серверная валидация)
         */
        public function calculate(Request $request): JsonResponse
        {
            // В реальном топ-решении здесь проверяются правила совместимости matrix_rules
            // и актуальные цены из БД/1С
            $templateId = $request->input('template_id');
            $selectedOptions = $request->input('options', []); // IDs
            $options = ConfiguratorOption::whereIn('id', $selectedOptions)->get();
            $totalPrice = $options->sum('price_kopeks');
            $totalWeight = $options->sum('weight_grams');
            $totalVolume = $options->sum('volume_cm3');
            return $this->response->json([
                'price_kopeks' => $totalPrice,
                'price_formatted' => number_format($totalPrice / 100, 2, '.', ' ') . ' ₽',
                'weight_grams' => $totalWeight,
                'volume_cm3' => $totalVolume,
            ]);
        }
}
