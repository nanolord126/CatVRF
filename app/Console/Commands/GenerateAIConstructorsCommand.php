<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Process\Process as SymfonyProcess;

/**
 * Generate AI Constructors for verticals
 * 
 * Command: php artisan ai:generate-constructors {vertical} [--dry-run] [--show-diff] [--force]
 * 
 * Features:
 * - Generates PromptBuilder classes for verticals
 * - Updates AI Constructor Services to use PromptBuilder
 * - Dry-run mode to preview changes
 * - Diff mode to show what will change
 * - Validation pipeline (phpstan, pint)
 * - Git backup before generation
 */
final class GenerateAIConstructorsCommand extends Command
{
    protected $signature = 'ai:generate-constructors 
                            {vertical : Vertical name (e.g., taxi, beauty, medical)}
                            {--dry-run : Preview changes without writing files}
                            {--show-diff : Show diff between old and new files}
                            {--force : Skip confirmation prompts}
                            {--skip-validation : Skip phpstan/pint validation}
                            {--skip-backup : Skip git backup}';

    protected $description = 'Generate AI Constructor components (PromptBuilder, Service updates) for a vertical';

    private bool $dryRun = false;
    private bool $showDiff = false;
    private array $generatedFiles = [];
    private array $diffs = [];

    public function handle(): int
    {
        $vertical = $this->argument('vertical');
        $this->dryRun = $this->option('dry-run');
        $this->showDiff = $this->option('show-diff');
        $force = $this->option('force');
        $skipValidation = $this->option('skip-validation');
        $skipBackup = $this->option('skip-backup');

        $this->info("AI Constructor Generation for: {$vertical}");
        $this->info("Mode: " . ($this->dryRun ? 'DRY-RUN' : 'LIVE'));

        if (!$this->dryRun && !$skipBackup) {
            $this->createGitBackup();
        }

        // Generate PromptBuilder
        $this->generatePromptBuilder($vertical);

        // Update AI Constructor Service
        $this->updateAIConstructorService($vertical);

        // Generate DTOs
        $this->generateDTOs($vertical);

        // Show summary
        $this->showSummary();

        // Validate if not dry-run and not skipped
        if (!$this->dryRun && !$skipValidation) {
            $this->validateGeneratedCode();
        }

        if ($this->dryRun) {
            $this->warn('DRY-RUN mode: No files were written.');
            $this->info('Run without --dry-run to apply changes.');
        } else {
            $this->info('✓ AI Constructor generation completed successfully.');
        }

        return Command::SUCCESS;
    }

    private function createGitBackup(): void
    {
        $this->info('Creating git backup...');

        $branchName = 'ai-constructor-backup-' . now()->format('Y-m-d-His');
        
        $process = Process::fromShellCommandline(
            'git checkout -b ' . escapeshellarg($branchName),
            base_path()
        );

        $process->run();

        if (!$process->isSuccessful()) {
            $this->warn('Could not create git branch. Continuing without backup.');
            return;
        }

        $this->info("✓ Created backup branch: {$branchName}");
    }

    private function generatePromptBuilder(string $vertical): void
    {
        $this->info("Generating PromptBuilder for {$vertical}...");

        $className = $this->getPromptBuilderClassName($vertical);
        $filePath = $this->getPromptBuilderPath($vertical);

        $content = $this->generatePromptBuilderContent($vertical, $className);

        $this->writeOrDiff($filePath, $content, "PromptBuilder: {$className}");
    }

    private function updateAIConstructorService(string $vertical): void
    {
        $this->info("Updating AI Constructor Service for {$vertical}...");

        $servicePath = $this->getAIConstructorServicePath($vertical);

        if (!File::exists($servicePath)) {
            $this->warn("AI Constructor Service not found at: {$servicePath}");
            $this->warn("Skipping service update.");
            return;
        }

        $currentContent = File::get($servicePath);
        $updatedContent = $this->updateServiceContent($currentContent, $vertical);

        $this->writeOrDiff($servicePath, $updatedContent, "AI Constructor Service: {$vertical}");
    }

    private function generateDTOs(string $vertical): void
    {
        $this->info("Generating DTOs for {$vertical}...");

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
            $this->diffs[$filePath] = $diff;
            $this->line("\n<fg=yellow>Diff for {$description}:</>");
            $this->line($diff);
        }

        if ($this->dryRun) {
            $this->generatedFiles[] = "{$description} (dry-run)";
            return;
        }

        File::ensureDirectoryExists(dirname($filePath));
        File::put($filePath, $content);
        $this->generatedFiles[] = $description;
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

    private function validateGeneratedCode(): void
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
                $this->error('Aborting due to validation errors. Use --force to skip.');
                exit(1);
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
                $this->error('Aborting due to validation errors. Use --force to skip.');
                exit(1);
            }
        } else {
            $this->info('✓ Pint validation passed');
        }
    }

    private function showSummary(): void
    {
        $this->newLine();
        $this->info('=== Generation Summary ===');
        $this->table(['File'], array_map(fn($f) => [$f], $this->generatedFiles));
        $this->info('Total files: ' . count($this->generatedFiles));
    }

    // Helper methods for content generation

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
