<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');
$count = 0;

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);
    $lines = explode("\n", $content);
    $modified = false;
    
    foreach ($lines as $idx => $line) {
        if (preg_match('/public function (view|create|update|delete|restore|forceDelete)\s*\(\s*([\\\\\w]*User)\s+\$user\s*,\s*([\\\\\w]+)\s+\$(\w+)\s*\)/', $line, $matches)) {
            $method = $matches[1];
            $modelClass = $matches[3];
            $modelVar = $matches[4];
            
            // Skip if the next line already has CANON 2026 string
            if (isset($lines[$idx+2]) && strpos($lines[$idx+2], 'CANON 2026') !== false) {
                continue;
            }
            
            // We want to insert tenant validation.
            // Some models might not have tenant_id, so we use property_exists or just isset
            $injection = "
        // CANON 2026: Strict tenant scoping check
        if (isset(\${$modelVar}->tenant_id) && \$user->tenant_id !== \${$modelVar}->tenant_id && !\$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => \$user->id,
                'user_tenant_id' => \$user->tenant_id,
                'model_tenant_id' => \${$modelVar}->tenant_id,
            ]);
            return false;
        }";
            
            // If the bracket is on the next line:
            if (strpos($lines[$idx], '{') !== false) {
                $lines[$idx] = $lines[$idx] . $injection;
            } elseif (isset($lines[$idx+1]) && strpos($lines[$idx+1], '{') !== false) {
                $lines[$idx+1] = $lines[$idx+1] . $injection;
            } else {
                // assume same line
                $lines[$idx] = $lines[$idx] . " {" . $injection;
                $lines[$idx] = str_replace("  {", " {", $lines[$idx]); // Fix double bracket if any
            }
            $modified = true;
            $count++;
        }
    }
    
    if ($modified) {
        file_put_contents($file, implode("\n", $lines));
    }
}
echo "Injected checks in $count places.\n";
