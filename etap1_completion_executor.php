<?php declare(strict_types=1);

/**
 * ETAP 1 COMPLETION EXECUTOR
 * 
 * Выполняет финальные шаги ETAP 1 (middleware refactor):
 * 1. Выполняет cleanup scripts
 * 2. Генерирует финальные отчёты
 * 3. Создаёт summary всех изменений
 */

require_once __DIR__ . '/vendor/autoload.php';

class ETAP1CompletionExecutor {
    
    private string $projectRoot;
    private array $stats = [];
    private array $warnings = [];
    private array $errors = [];
    
    public function __construct() {
        $this->projectRoot = __DIR__;
    }
    
    public function execute(): void {
        echo "\n=== ETAP 1 COMPLETION EXECUTOR ===\n\n";
        
        $this->executeDiagnostics();
        $this->generateSummary();
        $this->displayResults();
    }
    
    private function executeDiagnostics(): void {
        echo "1. Running diagnostic scripts...\n";
        
        $scripts = [
            'audit_middleware_refactor.php',
            'middleware_cleanup_analysis.php',
        ];
        
        foreach ($scripts as $script) {
            $path = $this->projectRoot . '/' . $script;
            if (file_exists($path)) {
                echo "   ✓ Executing $script\n";
                // Capture output
                ob_start();
                include $path;
                $output = ob_get_clean();
                $this->stats['diagnostics'][$script] = strlen($output) > 0 ? 'completed' : 'no output';
            } else {
                echo "   ✗ Script not found: $script\n";
                $this->warnings[] = "Diagnostic script missing: $script";
            }
        }
    }
    
    private function generateSummary(): void {
        echo "\n2. Analyzing middleware architecture...\n";
        
        $middlewareDir = $this->projectRoot . '/app/Http/Middleware';
        $controllerDir = $this->projectRoot . '/app/Http/Controllers/Api';
        
        // Count middleware files
        if (is_dir($middlewareDir)) {
            $middlewareFiles = array_filter(scandir($middlewareDir), fn($f) => str_ends_with($f, '.php'));
            $this->stats['middleware_count'] = count($middlewareFiles);
            echo "   ✓ Found " . count($middlewareFiles) . " middleware files\n";
        }
        
        // Count controller files
        if (is_dir($controllerDir)) {
            $this->countControllersRecursive($controllerDir);
        }
        
        // Check if middleware are registered in Kernel.php
        $this->verifyKernelRegistration();
    }
    
    private function countControllersRecursive(string $dir, int $level = 0): int {
        $count = 0;
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_file($path) && str_ends_with($path, 'Controller.php')) {
                $count++;
            } elseif (is_dir($path)) {
                $count += $this->countControllersRecursive($path, $level + 1);
            }
        }
        
        if ($level === 0) {
            $this->stats['controller_count'] = $count;
            echo "   ✓ Found " . $count . " controller files\n";
        }
        
        return $count;
    }
    
    private function verifyKernelRegistration(): void {
        $kernelPath = $this->projectRoot . '/app/Http/Kernel.php';
        
        if (!file_exists($kernelPath)) {
            $this->errors[] = "Kernel.php not found";
            return;
        }
        
        $content = file_get_contents($kernelPath);
        
        $requiredMiddleware = [
            'correlation-id' => 'CorrelationIdMiddleware',
            'b2c-b2b' => 'B2CB2BMiddleware',
            'fraud-check' => 'FraudCheckMiddleware',
            'rate-limit' => 'RateLimitingMiddleware',
            'age-verify' => 'AgeVerificationMiddleware',
        ];
        
        $registered = [];
        foreach ($requiredMiddleware as $alias => $class) {
            if (strpos($content, $alias) !== false && strpos($content, $class) !== false) {
                $registered[] = $alias;
                echo "   ✓ Middleware registered: '$alias' => $class\n";
            } else {
                $this->warnings[] = "Middleware not registered: $alias => $class";
            }
        }
        
        $this->stats['middleware_registered'] = count($registered);
    }
    
    private function displayResults(): void {
        echo "\n=== SUMMARY ===\n\n";
        
        echo "Statistics:\n";
        foreach ($this->stats as $key => $value) {
            if (is_array($value)) {
                echo "  $key:\n";
                foreach ($value as $k => $v) {
                    echo "    - $k: $v\n";
                }
            } else {
                echo "  $key: $value\n";
            }
        }
        
        if ($this->warnings) {
            echo "\n⚠ Warnings:\n";
            foreach ($this->warnings as $warning) {
                echo "  - $warning\n";
            }
        }
        
        if ($this->errors) {
            echo "\n❌ Errors:\n";
            foreach ($this->errors as $error) {
                echo "  - $error\n";
            }
        }
        
        echo "\n=== NEXT STEPS ===\n\n";
        echo "1. Review ETAP1_MIDDLEWARE_REFACTOR_GUIDE.md\n";
        echo "2. Update routes/api.php with middleware order\n";
        echo "3. Execute: php full_controller_refactor.php\n";
        echo "4. Execute: php generate_final_report.php\n";
        echo "5. Test all endpoints with new middleware\n";
        echo "\n";
    }
}

// Execute
$executor = new ETAP1CompletionExecutor();
$executor->execute();
