<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);
    
    // Inject the strict tenant check at the beginning of each method that takes a model.
    $pattern = '/(public function (view|update|delete|restore|forceDelete)\s*\([\\\]*[\w\\\\]*User \$user,\s*([\\\]*[\w\\\\]+)\s+\$(\w+)\)\s*:\s*bool\s*\{)/';
    
    $replacement = function ($matches) {
        $signature = $matches[1];
        $method = $matches[2];
        $modelType = $matches[3];
        $modelVar = $matches[4]; // variable name without $
        
        $inject = "
        // CANON 2026: Strict tenant scoping check
        if (isset(\$$modelVar->tenant_id) && \$user->tenant_id !== \$$modelVar->tenant_id && !\$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => \$user->id,
                'user_tenant_id' => \$user->tenant_id,
                'model_tenant_id' => \$$modelVar->tenant_id,
            ]);
            return false;
        }\n";
        
        return $signature . $inject;
    };
    
    $content = preg_replace_callback($pattern, $replacement, $content);
    file_put_contents($file, $content);
}

echo "Policies updated with strict CANON 2026 tenant scoping.\n";
