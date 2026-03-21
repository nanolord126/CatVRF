<?php

$dirs = ['app/Http/Requests', 'modules'];

foreach ($dirs as $dir) {
    if (!is_dir(__DIR__ . '/' . $dir)) continue;
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__ . '/' . $dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php' && strpos($file->getPathname(), 'Requests') !== false) {
            $content = file_get_contents($file->getPathname());
            
            // Check if it's a form request
            if (strpos($content, 'extends FormRequest') !== false || strpos($content, 'extends BaseApiRequest') !== false) {
                
                // If the file already has CANON 2026 FRAUD CHECK, skip it
                if (strpos($content, 'CANON 2026: Fraud Check') !== false) {
                    continue;
                }
                
                $pattern = '/(public function authorize\(\): bool\s*\{)(?!\s*\/\/ CANON 2026)/';
                $replacement = function ($m) {
                    return $m[1] . "
        // CANON 2026: Fraud Check in FormRequest
        if (class_exists(\App\Services\Fraud\FraudControlService::class) && auth()->check()) {
            \$fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass());
            if (\$fraudScore > 0.7 && !auth()->user()->hasRole('admin')) {
                \Illuminate\Support\Facades\Log::channel('audit')->warning('Fraud check blocked request', ['class' => __CLASS__, 'score' => \$fraudScore]);
                return false;
            }
        }";
                };
                
                $newContent = preg_replace_callback($pattern, $replacement, $content);
                
                if ($newContent !== $content) {
                    file_put_contents($file->getPathname(), $newContent);
                    echo "Updated " . $file->getFilename() . "\n";
                }
            }
        }
    }
}
echo "Done.\n";
