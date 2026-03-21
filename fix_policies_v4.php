<?php

$dir = __DIR__ . '/app/Policies';
$files = glob($dir . '/*.php');

foreach ($files as $file) {
    if (strpos($file, 'Domain') !== false) continue;
    $content = file_get_contents($file);

    // 1. Remove all old broken injections
    // It looks like:
    //         // CANON 2026: Strict tenant scoping check
    //         if (isset($) && $user->tenant_id !== $ && !$user->hasRole('admin')) {
    //             \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
    //                 'user_id' => $user->id,
    //                 'user_tenant_id' => $user->tenant_id,
    //                 'model_tenant_id' => $,
    //             ]);
    //             return false;
    //         }
    $pattern1 = '/\s*\/\/\s*CANON 2026: Strict tenant scoping check.*?return false;\s*\}/s';
    $content = preg_replace($pattern1, '', $content);

    // 2. Remove broken Fraud injections:
    //         if (!FraudControlService::check(request()->all(), '...')) {
    //             ...
    //         }
    $pattern2 = '/\s*if \(\!FraudControlService::check\(request\(\)->all\(\), \'[a-zA-Z]+\'\)\) \{.*?return false;\s*\}/s';
    $content = preg_replace($pattern2, '', $content);

    
    // 3. Re-inject tenant check
    $pattern3 = '/(public function (view|update|delete|restore|forceDelete)\s*\([\w\\\]*User \$user,\s*[\w\\\]+\s+\$(\w+)\)\s*:\s*bool\s*\{)(?!\s*\/\/ CANON 2026)/';
    $replacement3 = function ($m) {
        $sig = $m[1];
        $var = $m[3];
        $code = "
        // CANON 2026: Strict tenant scoping check
        if (isset(\${$var}->tenant_id) && \$user->tenant_id !== \${$var}->tenant_id && !\$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Tenant mismatch in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => \$user->id,
                'user_tenant_id' => \$user->tenant_id,
                'model_tenant_id' => \${$var}->tenant_id,
            ]);
            return false;
        }";
        return $sig . $code;
    };
    $content = preg_replace_callback($pattern3, $replacement3, $content);
    
    // 4. Re-inject fraud check
    $pattern4 = '/(public function (create|update|delete|restore|forceDelete)\s*\([\w\\\]*User \$user(?:,\s*[\w\\\]+\s+\$\w+)?\)\s*:\s*bool\s*\{)(?!\s*\/\/ CANON 2026 FRAUD)/';
    $replacement4 = function ($m) {
        $sig = $m[1];
        $op = $m[2];
        $code = "
        // CANON 2026 FRAUD: Predict/check operation before mutating
        \$fraudScore = app(\App\Services\Fraud\FraudControlService::class)->scoreOperation(new \stdClass()); // FIXME: DTO needed
        if (\$fraudScore > 0.7 && !\$user->hasRole('admin')) {
            \Illuminate\Support\Facades\Log::warning('Fraud check blocked action in ' . __CLASS__ . '::' . __FUNCTION__, [
                'user_id' => \$user->id,
                'score' => \$fraudScore
            ]);
            return false;
        }";
        return $sig . $code;
    };
    $content = preg_replace_callback($pattern4, $replacement4, $content);

    file_put_contents($file, $content);
}

echo "Policies fixed and updated.\n";
