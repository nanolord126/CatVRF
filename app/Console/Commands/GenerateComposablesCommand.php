<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * Generate Vue Composables for verticals
 *
 * Command: php artisan composables:generate {vertical} [--all] [--dry-run] [--force]
 *
 * Features:
 * - Generates use{Vertical}Api.ts composables for verticals
 * - TypeScript types included
 * - CRUD operations
 * - Error handling
 * - Correlation ID support
 * - Dry-run mode to preview changes
 */
final class GenerateComposablesCommand extends Command
{
    public function __construct(
        private readonly Filesystem $files,
    ) {
        parent::__construct();
    }
    protected $signature = 'composables:generate 
                            {vertical? : Vertical name (e.g., medical, payment, wallet). Use --all for all verticals}
                            {--all : Generate composables for all business verticals}
                            {--dry-run : Preview changes without writing files}
                            {--force : Skip confirmation prompts}
                            {--priority=p0 : Priority level (p0, p1, p2, all)}';

    protected $description = 'Generate Vue composables (use{Vertical}Api.ts) for verticals';

    private bool $dryRun = false;

    private array $generatedFiles = [];

    // Business verticals (from memory)
    private array $businessVerticals = [
        // P0 - Critical (medical, payment, wallet, cart, search)
        'medical', 'payment', 'wallet', 'cart', 'search',
        // P1 - High priority
        'userprofile', 'delivery', 'logistics', 'travel', 'auto', 'realestate', 'fashion', 'electronics',
        // P2 - Medium priority
        'beauty', 'food', 'hotels', 'fitness', 'sports', 'luxury', 'insurance', 'legal',
        'education', 'crm', 'analytics', 'consulting', 'content', 'freelance', 'eventplanning',
        'staff', 'inventory', 'taxi', 'tickets', 'pet', 'weddingplanning', 'veterinary',
        'toysandgames', 'advertising', 'carrental', 'finances', 'flowers', 'furniture',
        'pharmacy', 'photography', 'shorttermrentals', 'sportsnutrition', 'personaldevelopment',
        'homeservices', 'gardening', 'geo', 'geologistics', 'groceryanddelivery', 'farmdirect',
        'meatshops', 'officecatering', 'partysupplies', 'confectionery', 'constructionandrepair',
        'cleaningservices', 'communication', 'booksandliterature', 'collectibles', 'hobbyandcraft',
        'householdgoods', 'marketplace', 'musicandinstruments', 'veganproducts', 'art',
    ];

    // Technical modules (not business verticals)
    private array $technicalModules = [
        'bigdata', 'bonuses', 'commissions', 'ml', 'payout', 'promocampaigns', 'webhooks',
        'audit', 'b2b', 'common', 'compliance', 'demandforecast', 'fraudml', 'notifications',
        'realtime', 'recommendation', 'referral', 'security', 'verticalname',
    ];

    // Priority groups
    private array $priorityP0 = ['medical', 'payment', 'wallet', 'cart', 'search'];
    private array $priorityP1 = ['userprofile', 'delivery', 'logistics', 'travel', 'auto', 'realestate', 'fashion', 'electronics'];

    public function handle(): int
    {
        $vertical = $this->argument('vertical');
        $all = $this->option('all');
        $this->dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $priority = $this->option('priority');

        $this->info('Vue Composable Generation');
        $this->info('Mode: '.($this->dryRun ? 'DRY-RUN' : 'LIVE'));

        $verticalsToGenerate = [];

        if ($all) {
            if ($priority === 'p0') {
                $verticalsToGenerate = $this->priorityP0;
                $this->info('Generating P0 composables only');
            } elseif ($priority === 'p1') {
                $verticalsToGenerate = array_merge($this->priorityP0, $this->priorityP1);
                $this->info('Generating P0 + P1 composables');
            } else {
                $verticalsToGenerate = $this->businessVerticals;
                $this->info('Generating all business vertical composables');
            }
        } elseif ($vertical) {
            $verticalsToGenerate = [$vertical];
            $this->info("Generating composable for: {$vertical}");
        } else {
            $this->error('Please provide a vertical name or use --all flag');
            return Command::FAILURE;
        }

        if (! $force && ! $this->dryRun) {
            if (! $this->confirm("Generate ".count($verticalsToGenerate)." composables?")) {
                $this->info('Aborted.');
                return Command::SUCCESS;
            }
        }

        // Progress bar
        $progressBar = $this->output->createProgressBar(count($verticalsToGenerate));
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %elapsed:6s%/%estimated:-6s% %message%');
        $progressBar->setMessage('Starting...');
        $progressBar->start();

        foreach ($verticalsToGenerate as $vert) {
            $progressBar->setMessage("Generating {$vert}...");
            $this->generateComposable($vert);
            $progressBar->advance();
        }

        $progressBar->setMessage('Completed');
        $progressBar->finish();
        $this->newLine(2);

        $this->showSummary();

        if ($this->dryRun) {
            $this->warn('DRY-RUN mode: No files were written.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ Composable generation completed successfully.');
        }

        return Command::SUCCESS;
    }

    private function generateComposable(string $vertical): void
    {
        $this->info("Generating use{$vertical}Api.ts...");

        $fileName = $this->getComposableFileName($vertical);
        $filePath = $this->getComposablePath($vertical);

        if ($this->files->exists($filePath) && ! $this->option('force')) {
            $this->warn("  File already exists: {$fileName} (use --force to overwrite)");
            return;
        }

        $content = $this->generateComposableContent($vertical);
        $this->writeFile($filePath, $content, $fileName);
    }

    private function writeFile(string $filePath, string $content, string $description): void
    {
        if ($this->dryRun) {
            $this->generatedFiles[] = "{$description} (dry-run)";
            $this->line("  ✓ {$description} (dry-run)");
            return;
        }

        if (! $this->files->isDirectory(dirname($filePath))) {
            $this->files->makeDirectory(dirname($filePath), 0755, true);
        }
        $this->files->put($filePath, $content);
        $this->generatedFiles[] = $description;
        $this->line("  ✓ {$description}");
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('=== Generation Summary ===');
        $this->table(['File'], array_map(fn ($f) => [$f], $this->generatedFiles));
        $this->info('Total files: '.count($this->generatedFiles));
    }

    private function getComposableFileName(string $vertical): string
    {
        return 'use'.ucfirst($vertical).'Api.ts';
    }

    private function getComposablePath(string $vertical): string
    {
        return $this->laravel->resourcePath("js/composables/{$this->getComposableFileName($vertical)}");
    }

    private function generateComposableContent(string $vertical): string
    {
        $verticalCapitalized = ucfirst($vertical);
        $verticalLower = strtolower($vertical);
        $apiBase = "/api/v1/{$verticalLower}";

        return <<<TS
/**
 * use{$verticalCapitalized}Api — composable для всех API-запросов {$verticalCapitalized}-вертикали.
 *
 * Централизованный слой связи фронтенда и бэкенда.
 * Все компоненты {$verticalCapitalized} получают данные ТОЛЬКО через этот composable.
 * Никаких хардкод-массивов, никаких mock-данных в продакшене.
 *
 * API prefix: {$apiBase}
 * Авторизация: auth:sanctum (cookie-based через Inertia)
 * Заголовки: X-Correlation-ID (автогенерация UUID)
 */
import { ref, readonly } from 'vue';
import axios, { type AxiosResponse, type AxiosError } from 'axios';

/* ─── Types ─── */
export interface {$verticalCapitalized}Item {
    id: number;
    uuid: string;
    name: string;
    status: string;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
}

export interface ApiError {
    message: string;
    errors?: Record<string, string[]>;
    correlation_id?: string;
}

/* ─── Helpers ─── */
function generateCorrelationId(): string {
    return crypto.randomUUID ? crypto.randomUUID() : `\${Date.now()}-\${Math.random().toString(36).slice(2, 11)}`;
}

function buildHeaders(correlationId?: string): Record<string, string> {
    return {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        'X-Correlation-ID': correlationId !== undefined ? correlationId : generateCorrelationId(),
        'X-Requested-With': 'XMLHttpRequest',
    };
}

const API_BASE = '{$apiBase}';

/* ─── Composable ─── */
export function use{$verticalCapitalized}Api() {
    const loading = ref(false);
    const error = ref<ApiError | null>(null);
    const correlationId = ref(generateCorrelationId());

    function resetError(): void {
        error.value = null;
    }

    function handleError(err: AxiosError<ApiError>): void {
        if (err.response?.data) {
            error.value = {
                message: err.response.data.message !== undefined ? err.response.data.message : 'Ошибка сервера',
                errors: err.response.data.errors,
                correlation_id: err.response.data.correlation_id,
            };
        } else if (err.request) {
            error.value = { message: 'Сервер не отвечает. Проверьте соединение.' };
        } else {
            error.value = { message: err.message !== undefined ? err.message : 'Неизвестная ошибка' };
        }
    }

    async function apiGet<T>(url: string, params?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.get(`\${API_BASE}\${url}`, {
                headers: buildHeaders(correlationId.value),
                params,
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiPost<T>(url: string, data?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.post(`\${API_BASE}\${url}`, data, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiPut<T>(url: string, data?: Record<string, unknown>): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.put(`\${API_BASE}\${url}`, data, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    async function apiDelete<T>(url: string): Promise<T | null> {
        loading.value = true;
        resetError();
        try {
            const response: AxiosResponse<T> = await axios.delete(`\${API_BASE}\${url}`, {
                headers: buildHeaders(correlationId.value),
            });
            return response.data;
        } catch (err) {
            handleError(err as AxiosError<ApiError>);
            return null;
        } finally {
            loading.value = false;
        }
    }

    /* ═══════════════════════════════════════════════════
     * CRUD OPERATIONS
     * ═══════════════════════════════════════════════════ */
    async function fetchItems(params?: Record<string, unknown>): Promise<{$verticalCapitalized}Item[]> {
        const result = await apiGet<{ data: {$verticalCapitalized}Item[] }>('/', params);
        return result !== null && result.data !== undefined ? result.data : [];
    }

    async function fetchItem(id: number): Promise<{$verticalCapitalized}Item | null> {
        const result = await apiGet<{ data: {$verticalCapitalized}Item }>(`/\${id}`);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function createItem(data: Partial<{$verticalCapitalized}Item>): Promise<{$verticalCapitalized}Item | null> {
        const result = await apiPost<{ data: {$verticalCapitalized}Item }>('/', data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function updateItem(id: number, data: Partial<{$verticalCapitalized}Item>): Promise<{$verticalCapitalized}Item | null> {
        const result = await apiPut<{ data: {$verticalCapitalized}Item }>(`/\${id}`, data as Record<string, unknown>);
        return result !== null && result.data !== undefined ? result.data : null;
    }

    async function deleteItem(id: number): Promise<boolean> {
        const result = await apiDelete(`/ \${id}`);
        return result !== null;
    }

    /* ═══════════════════════════════════════════════════
     * DASHBOARD / ANALYTICS
     * ═══════════════════════════════════════════════════ */
    async function fetchDashboard(): Promise<Record<string, unknown> | null> {
        return apiGet('/dashboard');
    }

    async function fetchAnalytics(params?: Record<string, unknown>): Promise<Record<string, unknown> | null> {
        return apiGet('/analytics', params);
    }

    return {
        /* state */
        loading: readonly(loading),
        error: readonly(error),
        correlationId: readonly(correlationId),

        /* utils */
        resetError,

        /* crud */
        fetchItems,
        fetchItem,
        createItem,
        updateItem,
        deleteItem,

        /* analytics */
        fetchDashboard,
        fetchAnalytics,
    };
}
TS;
    }
}
