<?php

/**
 * Исправить namespace ошибки во всех Pages
 */

$fixes = [
    // SalarySlipResource
    'app/Filament/Tenant/Resources/SalarySlipResource/Pages' => [
        'SalarySlipResourcePages' => 'SalarySlipResource\Pages',
    ],
    // PromoCampaignResource
    'app/Filament/Tenant/Resources/PromoCampaignResource/Pages' => [
        'PromoCampaignResourcePages' => 'PromoCampaignResource\Pages',
    ],
    // ProductResource
    'app/Filament/Tenant/Resources/ProductResource/Pages' => [
        'ProductResourcePages' => 'ProductResource\Pages',
    ],
    // PayrollRunResource
    'app/Filament/Tenant/Resources/PayrollRunResource/Pages' => [
        'PayrollRunResourcePages' => 'PayrollRunResource\Pages',
    ],
    // PayoutResource
    'app/Filament/Tenant/Resources/PayoutResource/Pages' => [
        'PayoutResourcePages' => 'PayoutResource\Pages',
    ],
];

foreach ($fixes as $dir => $replacements) {
    if (!is_dir($dir)) {
        continue;
    }

    foreach (glob("{$dir}/*.php") as $file) {
        $content = file_get_contents($file);
        $original = $content;
        
        foreach ($replacements as $wrong => $right) {
            $content = str_replace(
                "namespace App\\Filament\\Tenant\\Resources\\{$wrong};",
                "namespace App\\Filament\\Tenant\\Resources\\{$right};",
                $content
            );
        }
        
        if ($content !== $original) {
            file_put_contents($file, $content);
            $relative = str_replace(getcwd() . '/', '', $file);
            echo "✅ FIXED: {$relative}\n";
        }
    }
}

echo "\n✅ Все namespace ошибки исправлены!\n";
