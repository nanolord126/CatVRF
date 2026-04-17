<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;

/**
 * Generate AI Constructors for ALL verticals
 * 
 * Command: php artisan ai:generate-constructors:all [--dry-run] [--show-diff] [--force]
 * 
 * This command iterates through all verticals in app/Domains and generates
 * PromptBuilder classes for each one.
 */
final class GenerateAllAIConstructorsCommand extends Command
{
    protected $signature = 'ai:generate-constructors:all 
                            {--dry-run : Preview changes without writing files}
                            {--show-diff : Show diff between old and new files}
                            {--force : Skip confirmation prompts}
                            {--skip-validation : Skip phpstan/pint validation}
                            {--verticals=* : Specific verticals to process (default: all)}';

    protected $description = 'Generate AI Constructor components for ALL verticals';

    private bool $dryRun = false;
    private bool $showDiff = false;
    private array $verticals = [];
    private array $results = [];

    public function handle(): int
    {
        $this->dryRun = $this->option('dry-run');
        $this->showDiff = $this->option('show-diff');
        $force = $this->option('force');
        $skipValidation = $this->option('skip-validation');
        $specificVerticals = $this->option('verticals');

        $this->info('=== AI Constructor Generation for ALL Verticals ===');
        $this->info('Mode: ' . ($this->dryRun ? 'DRY-RUN' : 'LIVE'));

        // Get all verticals
        $this->verticals = $this->getAllVerticals();

        if (!empty($specificVerticals)) {
            $this->verticals = array_intersect($this->verticals, $specificVerticals);
            $this->info('Processing specific verticals: ' . implode(', ', $this->verticals));
        }

        $this->info('Found ' . count($this->verticals) . ' verticals to process');

        if (!$this->dryRun && !$force) {
            if (!$this->confirm('This will generate PromptBuilders for ' . count($this->verticals) . ' verticals. Continue?')) {
                $this->warn('Aborted by user.');
                return Command::FAILURE;
            }
        }

        // Process each vertical
        foreach ($this->verticals as $vertical) {
            $this->processVertical($vertical);
        }

        // Show summary
        $this->showSummary();

        // Validate if not dry-run and not skipped
        if (!$this->dryRun && !$skipValidation) {
            $this->validateAllGeneratedCode();
        }

        if ($this->dryRun) {
            $this->warn('DRY-RUN mode: No files were written.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ AI Constructor generation completed for all verticals.');
        }

        return Command::SUCCESS;
    }

    private function getAllVerticals(): array
    {
        $domainsPath = base_path('app/Domains');
        
        if (!File::exists($domainsPath)) {
            $this->error('app/Domains directory not found');
            return [];
        }

        $verticals = [];
        $directories = File::directories($domainsPath);

        foreach ($directories as $directory) {
            $verticalName = basename($directory);
            
            // Skip technical directories
            $technicalDirs = ['Common', 'B2B', 'AI', 'Audit', 'BigData', 'Bonuses', 'Cart', 
                            'Commissions', 'Compliance', 'DemandForecast', 'FraudML', 'ML',
                            'Notifications', 'Payout', 'PromoCampaigns', 'Realtime', 
                            'Recommendation', 'Referral', 'Search', 'Security', 'UserProfile',
                            'VerticalName', 'Webhooks'];
            
            if (!in_array($verticalName, $technicalDirs)) {
                $verticals[] = $verticalName;
            }
        }

        sort($verticals);
        return $verticals;
    }

    private function processVertical(string $vertical): void
    {
        $this->newLine();
        $this->info("Processing: {$vertical}");

        $result = [
            'vertical' => $vertical,
            'prompt_builder' => false,
            'service_updated' => false,
            'dtos' => false,
            'errors' => [],
        ];

        try {
            // Generate PromptBuilder
            $this->generatePromptBuilder($vertical);
            $result['prompt_builder'] = true;

            // Update AI Constructor Service
            $this->updateAIConstructorService($vertical);
            $result['service_updated'] = true;

            // Generate DTOs
            $this->generateDTOs($vertical);
            $result['dtos'] = true;

        } catch (\Throwable $e) {
            $result['errors'][] = $e->getMessage();
            $this->error("Error processing {$vertical}: {$e->getMessage()}");
        }

        $this->results[] = $result;
    }

    private function generatePromptBuilder(string $vertical): void
    {
        $className = $this->getPromptBuilderClassName($vertical);
        $filePath = $this->getPromptBuilderPath($vertical);

        $content = $this->generatePromptBuilderContent($vertical, $className);

        $this->writeOrDiff($filePath, $content, "PromptBuilder: {$className}");
    }

    private function updateAIConstructorService(string $vertical): void
    {
        $servicePath = $this->getAIConstructorServicePath($vertical);

        if (!File::exists($servicePath)) {
            $this->warn("AI Constructor Service not found at: {$servicePath}");
            return;
        }

        $currentContent = File::get($servicePath);
        $updatedContent = $this->updateServiceContent($currentContent, $vertical);

        $this->writeOrDiff($servicePath, $updatedContent, "AI Constructor Service: {$vertical}");
    }

    private function generateDTOs(string $vertical): void
    {
        $dtoPath = base_path("app/DTOs/AI/{$vertical}");
        
        if (!File::exists($dtoPath)) {
            File::makeDirectory($dtoPath, 0755, true);
        }

        // Generate Request DTO
        $requestDto = $this->generateRequestDtoContent($vertical);
        $requestDtoPath = "{$dtoPath}/{$vertical}RequestDto.php";
        $this->writeOrDiff($requestDtoPath, $requestDto, "Request DTO: {$vertical}RequestDto");

        // Generate Response DTO
        $responseDto = $this->generateResponseDtoContent($vertical);
        $responseDtoPath = "{$dtoPath}/{$vertical}ResponseDto.php";
        $this->writeOrDiff($responseDtoPath, $responseDto, "Response DTO: {$vertical}ResponseDto");
    }

    private function writeOrDiff(string $filePath, string $content, string $description): void
    {
        $fileExists = File::exists($filePath);

        if ($this->showDiff && $fileExists) {
            $currentContent = File::get($filePath);
            $diff = $this->generateDiff($currentContent, $content, $filePath);
            $this->line("\n<fg=yellow>Diff for {$description}:</>");
            $this->line($diff);
        }

        if ($this->dryRun) {
            $this->line("  [DRY-RUN] {$description}");
            return;
        }

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $content);
        $this->line("  ✓ {$description}");
    }

    private function generateDiff(string $old, string $new, string $filePath): string
    {
        $tempOld = tempnam(sys_get_temp_dir(), 'old_');
        $tempNew = tempnam(sys_get_temp_dir(), 'new_');

        file_put_contents($tempOld, $old);
        file_put_contents($tempNew, $new);

        $process = Process::fromShellCommandline(
            'diff -u ' . escapeshellarg($tempOld) . ' ' . escapeshellarg($tempNew)
        );

        $process->run();

        unlink($tempOld);
        unlink($tempNew);

        if (!$process->isSuccessful()) {
            return $process->getOutput();
        }

        return "No changes detected.";
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('=== Generation Summary ===');

        $successCount = 0;
        $errorCount = 0;

        foreach ($this->results as $result) {
            $status = empty($result['errors']) ? '<fg=green>✓</>' : '<fg=red>✗</>';
            $this->line("{$status} {$result['vertical']}");
            
            if (empty($result['errors'])) {
                $successCount++;
            } else {
                $errorCount++;
                foreach ($result['errors'] as $error) {
                    $this->line("  Error: {$error}");
                }
            }
        }

        $this->newLine();
        $this->info("Total: " . count($this->results));
        $this->info("Success: {$successCount}");
        $this->info("Errors: {$errorCount}");
    }

    private function validateAllGeneratedCode(): void
    {
        $this->info('Validating generated code...');

        // PHPStan
        $this->info('Running PHPStan...');
        $phpstanProcess = Process::fromShellCommandline(
            'vendor/bin/phpstan analyse --memory-limit=2G',
            base_path()
        );
        $phpstanProcess->run();

        if (!$phpstanProcess->isSuccessful()) {
            $this->error('PHPStan validation failed:');
            $this->error($phpstanProcess->getErrorOutput());
            if (!$this->option('force')) {
                $this->error('Use --force to skip validation errors.');
            }
        } else {
            $this->info('✓ PHPStan validation passed');
        }

        // Pint
        $this->info('Running Pint...');
        $pintProcess = Process::fromShellCommandline(
            'vendor/bin/pint --test',
            base_path()
        );
        $pintProcess->run();

        if (!$pintProcess->isSuccessful()) {
            $this->error('Pint validation failed:');
            $this->error($pintProcess->getErrorOutput());
            if (!$this->option('force')) {
                $this->error('Use --force to skip validation errors.');
            }
        } else {
            $this->info('✓ Pint validation passed');
        }
    }

    // Helper methods

    private function getPromptBuilderClassName(string $vertical): string
    {
        return ucfirst($vertical) . 'PromptBuilder';
    }

    private function getPromptBuilderPath(string $vertical): string
    {
        return base_path("app/Services/AI/Prompts/{$this->getPromptBuilderClassName($vertical)}.php");
    }

    private function getAIConstructorServicePath(string $vertical): string
    {
        $verticalCapitalized = ucfirst($vertical);
        return base_path("app/Domains/{$verticalCapitalized}/Services/AI/{$verticalCapitalized}AIConstructorService.php");
    }

    private function generatePromptBuilderContent(string $vertical, string $className): string
    {
        $verticalLower = strtolower($vertical);
        $verticalCapitalized = ucfirst($vertical);
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\Services\\AI\\Prompts;

/**
 * Prompt builder for {$verticalCapitalized} AI
 * 
 * Vertical: {$verticalLower}
 * Type: ai_constructor
 * 
 * Generates prompts for AI-powered features in {$verticalCapitalized} vertical.
 */
final class {$className} extends AbstractPromptBuilder
{
    protected string \$version = '1.0.0';
    protected array \$metadata = [
        'vertical' => '{$verticalLower}',
        'type' => 'ai_constructor',
        'description' => 'AI constructor for {$verticalCapitalized} vertical',
        'language' => 'ru',
    ];

    public function getSystemPrompt(array \$context = []): string
    {
        \$prompt = <<<PROMPT
Ты — эксперт в вертикали {$verticalCapitalized}. 
Твоя задача — анализировать данные и предоставлять качественные рекомендации.

Контекст:
- Вертикаль: {$verticalLower}
- Тип: ai_constructor
- Язык ответа: русский

Правила:
1. Всегда учитывай контекст запроса
2. Предоставляй структурированные ответы
3. Используй фактические данные
PROMPT;

        \$this->logUsage('system', \$context);

        return \$this->sanitize(\$prompt);
    }

    public function getUserPrompt(array \$context = []): string
    {
        \$prompt = <<<PROMPT
Проанализируй следующие данные:

{{context_data}}

Предоставь рекомендации в структурированном формате.
PROMPT;

        \$interpolated = \$this->interpolate(\$prompt, [
            'context_data' => json_encode(\$context, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);

        \$this->logUsage('user', \$context);

        return \$this->sanitize(\$interpolated);
    }

    public function getOutputSchema(): array
    {
        return [
            'type' => 'object',
            'properties' => [
                'recommendations' => [
                    'type' => 'array',
                    'items' => ['type' => 'object'],
                ],
                'confidence' => ['type' => 'number'],
                'metadata' => ['type' => 'object'],
            ],
            'required' => ['recommendations', 'confidence'],
        ];
    }
}
PHP;
    }

    private function updateServiceContent(string $currentContent, string $vertical): string
    {
        $promptBuilderClass = $this->getPromptBuilderClassName($vertical);
        
        // Add import if not present
        $importStatement = "use App\\Services\\AI\\Prompts\\{$promptBuilderClass};";
        
        if (strpos($currentContent, $importStatement) === false) {
            // Find the last use statement and add after it
            $pattern = '/(use [^;]+;\n)/';
            $lastUse = null;
            preg_match_all($pattern, $currentContent, $matches);
            
            if (!empty($matches[0])) {
                $lastUse = end($matches[0]);
                $currentContent = str_replace($lastUse, $lastUse . $importStatement . "\n", $currentContent);
            } else {
                // Add after namespace
                $currentContent = preg_replace(
                    '/(namespace [^;]+;\n)/',
                    "$1\n" . $importStatement . "\n",
                    $currentContent
                );
            }
        }

        // Add to constructor if not present
        $constructorPattern = '/private readonly \\\\Illuminate\\\\Database\\\\DatabaseManager \\\$db,/';
        $constructorAddition = "private readonly {$promptBuilderClass} \$promptBuilder,";
        
        if (strpos($currentContent, $constructorAddition) === false) {
            $currentContent = preg_replace(
                $constructorPattern,
                "$0\n        $constructorAddition",
                $currentContent
            );
        }

        // Replace hardcoded prompt with PromptBuilder usage
        $hardcodedPromptPattern = "/\['role' => 'system', 'content' => '[^']+'\]/";
        
        if (preg_match($hardcodedPromptPattern, $currentContent)) {
            $replacement = <<<PHP
['role' => 'system', 'content' => \$this->promptBuilder->getSystemPrompt([
                'vertical' => '{$vertical}',
                'type' => 'ai_constructor',
            ])]
PHP;
            $currentContent = preg_replace($hardcodedPromptPattern, $replacement, $currentContent, 1);
        }

        return $currentContent;
    }

    private function generateRequestDtoContent(string $vertical): string
    {
        $className = ucfirst($vertical) . 'RequestDto';
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\DTOs\\AI\\{$vertical};

/**
 * Request DTO for {$vertical} AI Constructor
 */
final readonly class {$className}
{
    public function __construct(
        public int \$userId,
        public int \$tenantId,
        public string \$correlationId,
        public array \$inputData,
        public ?string \$idempotencyKey = null,
    ) {}

    public static function fromArray(array \$data): self
    {
        return new self(
            userId: \$data['user_id'],
            tenantId: \$data['tenant_id'],
            correlationId: \$data['correlation_id'],
            inputData: \$data['input_data'] ?? [],
            idempotencyKey: \$data['idempotency_key'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => \$this->userId,
            'tenant_id' => \$this->tenantId,
            'correlation_id' => \$this->correlationId,
            'input_data' => \$this->inputData,
            'idempotency_key' => \$this->idempotencyKey,
        ];
    }
}
PHP;
    }

    private function generateResponseDtoContent(string $vertical): string
    {
        $className = ucfirst($vertical) . 'ResponseDto';
        
        return <<<PHP
<?php

declare(strict_types=1);

namespace App\\DTOs\\AI\\{$vertical};

/**
 * Response DTO for {$vertical} AI Constructor
 */
final readonly class {$className}
{
    public function __construct(
        public bool \$success,
        public array \$recommendations,
        public float \$confidence,
        public array \$metadata,
        public string \$correlationId,
    ) {}

    public static function fromArray(array \$data): self
    {
        return new self(
            success: \$data['success'] ?? false,
            recommendations: \$data['recommendations'] ?? [],
            confidence: \$data['confidence'] ?? 0.0,
            metadata: \$data['metadata'] ?? [],
            correlationId: \$data['correlation_id'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'success' => \$this->success,
            'recommendations' => \$this->recommendations,
            'confidence' => \$this->confidence,
            'metadata' => \$this->metadata,
            'correlation_id' => \$this->correlationId,
        ];
    }
}
PHP;
    }
}
