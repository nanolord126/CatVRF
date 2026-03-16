#!/usr/bin/env php
<?php
/**
 * BLADE PAGES AUDIT - PowerShell версия
 */

$bladeFiles = array_map(function($f) { 
    return getcwd() . '\\' . str_replace('/', '\\', $f); 
}, [
    'resources/views/wishlist/public.blade.php',
    'resources/views/welcome.blade.php',
    'resources/views/scribe/index.blade.php',
    'resources/views/offline.blade.php',
    'resources/views/index.blade.php',
    'resources/views/app.blade.php',
    'resources/views/layouts/app.blade.php',
    'resources/views/livewire/webrtc/room.blade.php',
    'resources/views/livewire/try-on-widget.blade.php',
    'resources/views/livewire/transition-confirmation-widget.blade.php',
    'resources/views/livewire/support/chat-component.blade.php',
    'resources/views/livewire/public/recommended-for-you.blade.php',
    'resources/views/livewire/hotel-catalog.blade.php',
    'resources/views/livewire/communication/video-call-room.blade.php',
    'resources/views/livewire/beauty-shop-showcase.blade.php',
    'resources/views/livewire/b2b/interactive-procurement.blade.php',
    'resources/views/livewire/b2b/branch-importer.blade.php',
    'resources/views/hotels/show.blade.php',
    'resources/views/hotels/catalog.blade.php',
    'resources/views/filament/widgets/taxi-heatmap-widget.blade.php',
    'resources/views/filament/widgets/b2b-recommended-suppliers-widget.blade.php',
    'resources/views/filament/widgets/b2b-demand-heatmap-widget.blade.php',
    'resources/views/filament/tenant/widgets/vertical-b2-b-recommendations-widget.blade.php',
    'resources/views/filament/tenant/widgets/vertical-ai-recommendations-widget.blade.php',
    'resources/views/filament/tenant/widgets/geo-heatmap-widget.blade.php',
    'resources/views/filament/tenant/widgets/branch-switcher.blade.php',
    'resources/views/filament/tenant/widgets/ai-recommendations-widget.blade.php',
    'resources/views/filament/tenant/resources/marketplace/taxi/widgets/taxi-heatmap-widget.blade.php',
    'resources/views/filament/tenant/resources/hr/employee/modals/visit-history.blade.php',
    'resources/views/filament/tenant/resources/hr/employee/modals/pet-history.blade.php',
    'resources/views/filament/tenant/resources/crm/pages/task-kanban.blade.php',
    'resources/views/filament/tenant/resources/crm/pages/deal-kanban.blade.php',
    'resources/views/filament/tenant/pages/transition-confirmation.blade.php',
    'resources/views/filament/tenant/pages/quick-onboarding.blade.php',
    'resources/views/filament/tenant/pages/public-marketplace-facade.blade.php',
    'resources/views/filament/tenant/pages/personal-checklist.blade.php',
    'resources/views/filament/tenant/pages/health-dashboard.blade.php',
    'resources/views/filament/tenant/pages/global-business-dashboard.blade.php',
    'resources/views/filament/tenant/pages/ecosystem-rewards-dashboard.blade.php',
    'resources/views/filament/tenant/pages/digital-twin-scenario-dashboard.blade.php',
    'resources/views/filament/tenant/pages/dashboard.blade.php',
    'resources/views/filament/tenant/pages/consumer-behavior-analytics-dashboard.blade.php',
    'resources/views/filament/tenant/pages/b2b-supply-dashboard.blade.php',
    'resources/views/filament/tenant/pages/ai-voice-assistant-overlay.blade.php',
    'resources/views/filament/tenant/pages/ai-security-gateway-dashboard.blade.php',
    'resources/views/filament/tenant/pages/ai-pricing-simulation-dashboard.blade.php',
    'resources/views/filament/tenant/pages/ai-predictive-staffing-dashboard.blade.php',
    'resources/views/filament/tenant/pages/ai-logistics-communications-dashboard.blade.php',
    'resources/views/filament/tenant/components/sla-timer.blade.php',
    'resources/views/filament/tenant/components/order-stepper.blade.php',
    'resources/views/filament/tenant/components/courier-map.blade.php',
    'resources/views/filament/pages/active-devices.blade.php',
    'resources/views/filament/forms/components/chat-interface.blade.php',
    'resources/views/filament/admin/resources/admin-resource/pages/settings/transition-confirmation.blade.php',
    'resources/views/filament/admin/resources/admin-resource/pages/settings/transition-confirmation-page.blade.php',
    'resources/views/filament/admin/pages/ai-dashboard.blade.php',
    'resources/views/components/hotel-card.blade.php',
]);

$missing = [];
$empty = [];
$errors = [];
$ok = [];

foreach ($bladeFiles as $file) {
    if (!file_exists($file)) {
        $missing[] = str_replace('\\', '/', str_replace(getcwd() . '\\', '', $file));
    } else {
        $content = file_get_contents($file);
        $size = strlen($content);
        
        if ($size < 20) {
            $empty[] = str_replace('\\', '/', str_replace(getcwd() . '\\', '', $file)) . " ($size bytes)";
        } else {
            $ifCount = substr_count($content, '@if');
            $endifCount = substr_count($content, '@endif');
            $foreachCount = substr_count($content, '@foreach');
            $endforeachCount = substr_count($content, '@endforeach');
            
            if ($ifCount !== $endifCount || $foreachCount !== $endforeachCount) {
                $errors[] = [
                    'file' => str_replace('\\', '/', str_replace(getcwd() . '\\', '', $file)),
                    'size' => $size,
                    'issues' => [
                        "@if mismatch: $ifCount vs $endifCount",
                        "@foreach mismatch: $foreachCount vs $endforeachCount"
                    ]
                ];
            } else {
                $ok[] = str_replace('\\', '/', str_replace(getcwd() . '\\', '', $file));
            }
        }
    }
}

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           BLADE PAGES AUDIT REPORT - 57 FILES              ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n";
echo "\n";

$total = count($bladeFiles);
$totalErrors = count($missing) + count($empty) + count($errors);
$totalOk = count($ok);

echo "📊 SUMMARY:\n";
printf("   Total Files: %d\n", $total);
printf("   ✅ OK: %d (%.0f%%)\n", $totalOk, $totalOk/$total*100);
printf("   ❌ Errors: %d\n", $totalErrors);
printf("   Missing: %d\n", count($missing));
printf("   Empty: %d\n", count($empty));
printf("   Syntax Issues: %d\n\n", count($errors));

if (!empty($missing)) {
    echo "🔴 MISSING FILES (" . count($missing) . "):\n";
    foreach ($missing as $m) {
        echo "   ❌ $m\n";
    }
    echo "\n";
}

if (!empty($empty)) {
    echo "🟡 EMPTY/STUB FILES (" . count($empty) . "):\n";
    foreach ($empty as $e) {
        echo "   ⚠️  $e\n";
    }
    echo "\n";
}

if (!empty($errors)) {
    echo "🔴 SYNTAX ERRORS (" . count($errors) . "):\n";
    foreach ($errors as $e) {
        echo "   File: {$e['file']} ({$e['size']} bytes)\n";
        foreach ($e['issues'] as $issue) {
            echo "      ⚠️  $issue\n";
        }
    }
    echo "\n";
}

echo str_repeat("=", 60) . "\n";
echo "🎯 ACTION PLAN:\n";
echo "   1. Create " . count($missing) . " missing files\n";
echo "   2. Fill " . count($empty) . " empty/stub files\n";
echo "   3. Fix " . count($errors) . " files with mismatched tags\n";
echo "   4. Convert all to UTF-8 CRLF\n";
echo "   5. Run validation after fixes\n";
echo "\n";
