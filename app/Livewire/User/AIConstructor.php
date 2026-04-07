<?php declare(strict_types=1);

namespace App\Livewire\User;



use Illuminate\Http\Request;
use Illuminate\Auth\AuthManager;
use App\Models\User;
use App\Services\AI\AIConstructorService;
use App\Services\FraudControlService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;


use Illuminate\Support\Str;
use Illuminate\View\View;
use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Log\LogManager;
use Illuminate\Database\DatabaseManager;

/**
 * AIConstructor — Livewire-компонент запуска AI-конструкторов из личного кабинета.
 *
 * Канон:
 *  - AI-конструктор доступен для всех вертикалей из Tenant и User Cabinet.
 *  - Результат сохраняется в user_ai_designs.
 *  - B2C/B2B — разные цены и доступность товаров в рекомендациях.
 *  - Fraud-check перед каждым тяжёлым AI-запросом.
 *  - correlation_id + AuditService обязательны.
 */
final class AIConstructor extends Component
{
    use WithFileUploads;

    // ── публичные свойства ───────────────────────────────────────────────────

    /** @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null */
    private $photo = null;

    private string $vertical      = 'beauty';  // выбранная вертикаль
    private array $params        = [];        // доп. параметры (стиль, бюджет, диета и т.д.)
    private bool $isProcessing  = false;
    private bool $hasResult     = false;
    private array $result        = [];
    private array $savedDesigns  = [];
    private string $errorMessage  = '';
    private string $correlationId = '';

    /** Поддерживаемые вертикали */
    private array $verticals = [
        'beauty'    => 'Красота и стиль',
        'furniture' => 'Интерьер и мебель',
        'food'      => 'Меню и рецепты',
        'fashion'   => 'Мода и гардероб',
        'fitness'   => 'Фитнес и питание',
        'hotel'     => 'Отели',
        'travel'    => 'Путешествия',
    ];

    // ── lifecycle ───────────────────────────────────────────────────────────

    public function __construct(
        private readonly Request $request,
        private readonly AuthManager $authManager,
        private AIConstructorService $aiConstructor,
        private FraudControlService  $fraud,
        private readonly LogManager $logger,
        private readonly DatabaseManager $db,
    ) {}

    public function mount(string $vertical = 'beauty'): void
    {
        $this->correlationId = (string) Str::uuid();
        $this->vertical      = array_key_exists($vertical, $this->verticals) ? $vertical : 'beauty';
        $this->loadSavedDesigns();
    }

    // ── публичные экшены ─────────────────────────────────────────────────────

    /**
     * Запустить AI-конструктор.
     * Fraud-check + upload + анализ через AIConstructorService.
     */
    public function run(): void
    {
        $this->validate([
            'photo'    => 'required|image|max:10240',  // 10 MB
            'vertical' => 'required|string|in:' . implode(',', array_keys($this->verticals)),
        ]);

        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            $this->redirect(route('login'));
            return;
        }

        $this->isProcessing = true;
        $this->errorMessage = '';

        try {
            $this->fraud->check(
                $user->id,
                'ai_constructor_run',
                0,
                $this->request->ip(),
                null,
                $this->correlationId,
            );

            /** @var UploadedFile $file */
            $file = $this->photo->getRealPath()
                ? new UploadedFile(
                    $this->photo->getRealPath(),
                    $this->photo->getClientOriginalName(),
                    $this->photo->getMimeType(),
                    null,
                    true
                )
                : null;

            if (!$file) {
                $this->errorMessage = 'Не удалось обработать файл.';
                return;
            }

            $this->result    = $this->aiConstructor->run($user, $this->vertical, $file, $this->params);
            $this->hasResult = true;

            $this->logger->channel('audit')->info('AI constructor run from user cabinet', [
                'user_id'        => $user->id,
                'vertical'       => $this->vertical,
                'correlation_id' => $this->correlationId,
            ]);

            $this->loadSavedDesigns();
            $this->dispatch('ai-result', $this->result);

        } catch (\Throwable $e) {
            $this->errorMessage = 'Ошибка AI-конструктора: ' . $e->getMessage();
            $this->logger->channel('audit')->error('AI constructor failed in user cabinet', [
                'user_id'        => $this->authManager->id(),
                'vertical'       => $this->vertical,
                'error'          => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
        } finally {
            $this->isProcessing  = false;
            $this->correlationId = (string) Str::uuid(); // сброс для следующего запроса
        }
    }

    public function selectVertical(string $vertical): void
    {
        if (!array_key_exists($vertical, $this->verticals)) {
            return;
        }
        $this->vertical  = $vertical;
        $this->hasResult = false;
        $this->result    = [];
        $this->photo     = null;
        $this->params    = [];
    }

    public function deleteDesign(int $designId): void
    {
        /** @var User $user */
        $user = $this->authManager->user();
        if (!$user) {
            return;
        }

        $this->db->table('user_ai_designs')
            ->where('id', $designId)
            ->where('user_id', $user->id)  // только свои
            ->delete();

        $this->loadSavedDesigns();
        $this->dispatch('design-deleted', ['id' => $designId]);
    }

    // ── приватные методы ─────────────────────────────────────────────────────

    private function loadSavedDesigns(): void
    {
        /** @var User|null $user */
        $user = $this->authManager->user();
        if (!$user) {
            $this->savedDesigns = [];
            return;
        }

        $this->savedDesigns = $this->db->table('user_ai_designs')
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->select(['id', 'vertical', 'created_at'])
            ->get()
            ->map(fn(object $row): array => [
                'id'       => $row->id,
                'vertical' => $row->vertical,
                'label'    => $this->verticals[$row->vertical] ?? $row->vertical,
                'created'  => $row->created_at,
            ])
            ->toArray();
    }

    // ── рендер ──────────────────────────────────────────────────────────────

    public function render(): View
    {
        return view('livewire.user.ai-constructor')
            ->layout('layouts.user-cabinet');
    }
}
