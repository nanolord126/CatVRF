<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);
    
    // Skip if already canonical
    if (strpos($content, 'FraudControlService::check') !== false) {
        continue;
    }
    
    // Inject use FraudControlService
    if (strpos($content, 'FraudControlService') === false) {
         $content = preg_replace(
             '/use Illuminate\\\\Auth\\\\Access\\\\HandlesAuthorization;\n/s',
             "use Illuminate\\Auth\\Access\\HandlesAuthorization;\nuse App\\Services\\Fraud\\FraudControlService;\n",
             $content
         );
    }
    
    // Need to hook into methods. 
    // Usually they are: public function methodName(User $user, Model $model)
    // We can do a regex that captures the method signature and injects code at the start of the method body.
    $pattern = '/(public function (create|update|delete|restore|forceDelete)\s*\([^{]+?\)\s*:\s*bool\s*\{)(.*?)(^\s*\})/ms';
    
    // But what about tenant_id check? 
    // Often it is: $user->tenant_id === $model->tenant_id.
    
    // Simple approach: inject FraudControlService check at the start of mutator methods.
    $replacement = function ($matches) {
        $signature = $matches[1];
        $method = $matches[2];
        $body = $matches[3];
        $closing = $matches[4];
        
        $inject = "\n        if (!FraudControlService::check(request()->all(), '$method')) {\n            Log::warning('Fraud check failed in Policy', ['user_id' => \$user->id ?? null]);\n            return false;\n        }\n";
        
        return $signature . $inject . $body . $closing;
    };
    
    $content = preg_replace_callback($pattern, $replacement, $content);
    
    file_put_contents($file, $content);
}

echo "Policies updated.\n";
