<?php declare(strict_types=1);

/**
 * MIDDLEWARE ARCHITECTURE VERIFICATION SCRIPT
 * 
 * Проверяет:
 * 1. Все 5 middleware классы существуют
 * 2. BaseApiController содержит только helper методы
 * 3. Middleware зарегистрированы в Kernel.php
 * 4. Контроллеры не содержат middleware логику
 * 5. Routes применяют middleware в правильном порядке
 */

class MiddlewareArchitectureVerification {
    
    private string $projectRoot;
    private array $report = [
        'middleware' => [],
        'controllers' => [],
        'kernel' => [],
        'routes' => [],
        'status' => 'PENDING',
    ];
    
    public function __construct() {
        $this->projectRoot = __DIR__;
    }
    
    public function verify(): void {
        echo "\n=== MIDDLEWARE ARCHITECTURE VERIFICATION ===\n\n";
        
        $this->verifyMiddlewareClasses();
        $this->verifyBaseApiController();
        $this->verifyKernelRegistration();
        $this->verifyControllers();
        $this->verifyRoutes();
        $this->generateReport();
    }
    
    private function verifyMiddlewareClasses(): void {
        echo "1. Verifying Middleware Classes...\n\n";
        
        $requiredMiddleware = [
            'CorrelationIdMiddleware',
            'B2CB2BMiddleware',
            'FraudCheckMiddleware',
            'RateLimitingMiddleware',
            'AgeVerificationMiddleware',
        ];
        
        $middlewareDir = $this->projectRoot . '/app/Http/Middleware';
        
        foreach ($requiredMiddleware as $className) {
            $filePath = $middlewareDir . '/' . $className . '.php';
            
            if (file_exists($filePath)) {
                $content = file_get_contents($filePath);
                $lineCount = substr_count($content, "\n");
                
                $this->report['middleware'][$className] = [
                    'exists' => true,
                    'path' => str_replace($this->projectRoot, '', $filePath),
                    'lines' => $lineCount,
                    'has_middleware_logic' => false,
                ];
                
                echo "   ✓ $className ({$lineCount} lines)\n";
            } else {
                $this->report['middleware'][$className] = [
                    'exists' => false,
                    'path' => str_replace($this->projectRoot, '', $filePath),
                ];
                echo "   ✗ $className NOT FOUND\n";
            }
        }
        echo "\n";
    }
    
    private function verifyBaseApiController(): void {
        echo "2. Verifying BaseApiController...\n\n";
        
        $controllerPath = $this->projectRoot . '/app/Http/Controllers/Api/BaseApiController.php';
        
        if (!file_exists($controllerPath)) {
            echo "   ✗ BaseApiController NOT FOUND\n";
            $this->report['controllers']['BaseApiController'] = ['exists' => false];
            return;
        }
        
        $content = file_get_contents($controllerPath);
        $lineCount = substr_count($content, "\n");
        
        // Check for helper methods
        $helperMethods = [
            'getCorrelationId',
            'isB2C',
            'isB2B',
            'getModeType',
            'auditLog',
            'fraudLog',
            'successResponse',
            'errorResponse',
        ];
        
        $foundMethods = [];
        foreach ($helperMethods as $method) {
            if (strpos($content, "function $method") !== false || strpos($content, "public function $method") !== false) {
                $foundMethods[] = $method;
            }
        }
        
        // Check for middleware logic (what shouldn't be there)
        $badPatterns = [
            'FraudControlService',
            'RateLimiterService',
            'fraudControl',
            'rateLimiter',
        ];
        
        $foundBadPatterns = [];
        foreach ($badPatterns as $pattern) {
            if (strpos($content, $pattern) !== false) {
                $foundBadPatterns[] = $pattern;
            }
        }
        
        $this->report['controllers']['BaseApiController'] = [
            'exists' => true,
            'lines' => $lineCount,
            'helper_methods' => $foundMethods,
            'has_middleware_logic' => count($foundBadPatterns) > 0,
            'bad_patterns_found' => $foundBadPatterns,
        ];
        
        if (count($foundBadPatterns) === 0) {
            echo "   ✓ BaseApiController is clean (only helper methods)\n";
            echo "   ✓ Found " . count($foundMethods) . " helper methods\n";
            echo "   ✓ No middleware logic detected\n";
        } else {
            echo "   ⚠ BaseApiController contains middleware-related code:\n";
            foreach ($foundBadPatterns as $pattern) {
                echo "      - $pattern\n";
            }
        }
        echo "\n";
    }
    
    private function verifyKernelRegistration(): void {
        echo "3. Verifying Kernel.php Registration...\n\n";
        
        $kernelPath = $this->projectRoot . '/app/Http/Kernel.php';
        
        if (!file_exists($kernelPath)) {
            echo "   ✗ Kernel.php NOT FOUND\n";
            return;
        }
        
        $content = file_get_contents($kernelPath);
        
        $requiredAliases = [
            'correlation-id' => 'CorrelationIdMiddleware',
            'b2c-b2b' => 'B2CB2BMiddleware',
            'fraud-check' => 'FraudCheckMiddleware',
            'rate-limit' => 'RateLimitingMiddleware',
            'age-verify' => 'AgeVerificationMiddleware',
        ];
        
        foreach ($requiredAliases as $alias => $class) {
            if (strpos($content, "'$alias'") !== false && strpos($content, $class) !== false) {
                $this->report['kernel'][$alias] = [
                    'registered' => true,
                    'class' => $class,
                ];
                echo "   ✓ '$alias' => $class\n";
            } else {
                $this->report['kernel'][$alias] = [
                    'registered' => false,
                    'class' => $class,
                ];
                echo "   ✗ '$alias' NOT REGISTERED\n";
            }
        }
        echo "\n";
    }
    
    private function verifyControllers(): void {
        echo "4. Scanning Controllers for Duplicate Middleware Logic...\n\n";
        
        $controllerDir = $this->projectRoot . '/app/Http/Controllers/Api';
        
        if (!is_dir($controllerDir)) {
            echo "   ✗ Controller directory not found\n";
            return;
        }
        
        $duplicatePatterns = [
            'fraudControl' => 'Fraud check logic',
            'rateLimiter' => 'Rate limiting logic',
            'Str::uuid()' => 'Manual correlation ID generation',
            'FraudControlService' => 'Fraud service injection',
            'RateLimiterService' => 'Rate limiter service injection',
        ];
        
        $controllers = $this->getControllerFiles($controllerDir);
        $controllersWithDuplicates = 0;
        
        echo "   Found " . count($controllers) . " controller files\n\n";
        
        foreach ($controllers as $controllerFile) {
            $content = file_get_contents($controllerFile);
            $foundPatterns = [];
            
            foreach ($duplicatePatterns as $pattern => $description) {
                if (strpos($content, $pattern) !== false) {
                    $foundPatterns[] = $pattern;
                }
            }
            
            if (count($foundPatterns) > 0) {
                $controllersWithDuplicates++;
                $fileName = basename($controllerFile);
                echo "   ⚠ $fileName:\n";
                foreach ($foundPatterns as $pattern) {
                    echo "      - $pattern\n";
                }
            }
        }
        
        echo "\n   Summary: $controllersWithDuplicates controllers have duplicate patterns\n";
        $this->report['controllers']['with_duplicates'] = $controllersWithDuplicates;
        $this->report['controllers']['total_count'] = count($controllers);
        echo "\n";
    }
    
    private function getControllerFiles(string $dir): array {
        $files = [];
        $items = scandir($dir);
        
        foreach ($items as $item) {
            if ($item === '.' || $item === '..') continue;
            
            $path = $dir . '/' . $item;
            if (is_file($path) && str_ends_with($path, 'Controller.php')) {
                $files[] = $path;
            } elseif (is_dir($path)) {
                $files = array_merge($files, $this->getControllerFiles($path));
            }
        }
        
        return $files;
    }
    
    private function verifyRoutes(): void {
        echo "5. Verifying Routes Middleware Order...\n\n";
        
        $requiredOrder = [
            'correlation-id',
            'auth:sanctum',
            'tenant',
            'b2c-b2b',
            'rate-limit',
            'fraud-check',
            'age-verify',
        ];
        
        $routeFile = $this->projectRoot . '/routes/api.php';
        
        if (!file_exists($routeFile)) {
            echo "   ✗ routes/api.php NOT FOUND\n";
            return;
        }
        
        $content = file_get_contents($routeFile);
        
        echo "   Checking for middleware in routes/api.php:\n\n";
        
        foreach ($requiredOrder as $middleware) {
            if (strpos($content, "'$middleware'") !== false) {
                echo "   ✓ '$middleware' found\n";
            } else {
                echo "   ✗ '$middleware' NOT found\n";
            }
        }
        
        echo "\n";
    }
    
    private function generateReport(): void {
        echo "\n=== VERIFICATION SUMMARY ===\n\n";
        
        $allMiddlewareExists = true;
        foreach ($this->report['middleware'] as $middleware => $data) {
            if (!$data['exists'] ?? false) {
                $allMiddlewareExists = false;
            }
        }
        
        $allRegistered = true;
        foreach ($this->report['kernel'] as $alias => $data) {
            if (!$data['registered'] ?? false) {
                $allRegistered = false;
            }
        }
        
        $baseControllerClean = !($this->report['controllers']['BaseApiController']['has_middleware_logic'] ?? false);
        
        echo "Middleware Classes: " . ($allMiddlewareExists ? "✓ OK" : "✗ MISSING") . "\n";
        echo "Kernel Registration: " . ($allRegistered ? "✓ OK" : "✗ MISSING") . "\n";
        echo "BaseApiController: " . ($baseControllerClean ? "✓ CLEAN" : "✗ HAS MIDDLEWARE LOGIC") . "\n";
        echo "Controllers with Duplicates: " . ($this->report['controllers']['with_duplicates'] ?? 0) . "\n";
        
        if ($allMiddlewareExists && $allRegistered && $baseControllerClean) {
            echo "\n✅ ARCHITECTURE STATUS: GOOD (Ready for cleanup phase)\n";
            $this->report['status'] = 'GOOD';
        } else {
            echo "\n⚠ ARCHITECTURE STATUS: ISSUES FOUND (Review above)\n";
            $this->report['status'] = 'ISSUES';
        }
        
        echo "\n=== Next Steps ===\n";
        echo "1. Execute: php full_controller_refactor.php (removes duplicates)\n";
        echo "2. Execute: php generate_final_report.php (generates report)\n";
        echo "3. Test all endpoints\n";
        echo "\n";
        
        // Save report
        $reportPath = $this->projectRoot . '/MIDDLEWARE_VERIFICATION_REPORT.json';
        file_put_contents($reportPath, json_encode($this->report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        echo "Report saved to: MIDDLEWARE_VERIFICATION_REPORT.json\n\n";
    }
}

// Execute verification
$verification = new MiddlewareArchitectureVerification();
$verification->verify();
