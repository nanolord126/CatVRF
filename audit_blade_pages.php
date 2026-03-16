<?php
/**
 * BLADE PAGES AUDIT SCRIPT
 * Проводит полный анализ всех .blade.php файлов на ошибки и полноту
 */

declare(strict_types=1);

$bladeFiles = [
    // Resources/Views Root
    'resources/views/wishlist/public.blade.php',
    'resources/views/welcome.blade.php',
    'resources/views/scribe/index.blade.php',
    'resources/views/offline.blade.php',
    'resources/views/index.blade.php',
    'resources/views/app.blade.php',
    'resources/views/layouts/app.blade.php',
    
    // Livewire Components
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
    
    // Hotels
    'resources/views/hotels/show.blade.php',
    'resources/views/hotels/catalog.blade.php',
    
    // Filament Widgets
    'resources/views/filament/widgets/taxi-heatmap-widget.blade.php',
    'resources/views/filament/widgets/b2b-recommended-suppliers-widget.blade.php',
    'resources/views/filament/widgets/b2b-demand-heatmap-widget.blade.php',
    
    // Filament Tenant Widgets
    'resources/views/filament/tenant/widgets/vertical-b2-b-recommendations-widget.blade.php',
    'resources/views/filament/tenant/widgets/vertical-ai-recommendations-widget.blade.php',
    'resources/views/filament/tenant/widgets/geo-heatmap-widget.blade.php',
    'resources/views/filament/tenant/widgets/branch-switcher.blade.php',
    'resources/views/filament/tenant/widgets/ai-recommendations-widget.blade.php',
    
    // Filament Tenant Resources
    'resources/views/filament/tenant/resources/marketplace/taxi/widgets/taxi-heatmap-widget.blade.php',
    'resources/views/filament/tenant/resources/hr/employee/modals/visit-history.blade.php',
    'resources/views/filament/tenant/resources/hr/employee/modals/pet-history.blade.php',
    
    // Filament Tenant CRM Pages
    'resources/views/filament/tenant/resources/crm/pages/task-kanban.blade.php',
    'resources/views/filament/tenant/resources/crm/pages/deal-kanban.blade.php',
    
    // Filament Tenant Pages
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
    
    // Filament Tenant Components
    'resources/views/filament/tenant/components/sla-timer.blade.php',
    'resources/views/filament/tenant/components/order-stepper.blade.php',
    'resources/views/filament/tenant/components/courier-map.blade.php',
    
    // Filament Admin Pages
    'resources/views/filament/pages/active-devices.blade.php',
    
    // Filament Forms
    'resources/views/filament/forms/components/chat-interface.blade.php',
    
    // Filament Admin Resources
    'resources/views/filament/admin/resources/admin-resource/pages/settings/transition-confirmation.blade.php',
    'resources/views/filament/admin/resources/admin-resource/pages/settings/transition-confirmation-page.blade.php',
    
    // Filament Admin Dashboards
    'resources/views/filament/admin/pages/ai-dashboard.blade.php',
    
    // Components
    'resources/views/components/hotel-card.blade.php',
];

$issues = [
    'missing_files' => [],
    'empty_files' => [],
    'syntax_errors' => [],
    'incomplete_files' => [],
    'encoding_errors' => [],
];

$report = [];
$totalChecked = 0;
$totalErrors = 0;

foreach ($bladeFiles as $file) {
    $totalChecked++;
    $path = base_path($file);
    
    if (!file_exists($path)) {
        $issues['missing_files'][] = $file;
        $totalErrors++;
        $report[] = "❌ MISSING: $file";
        continue;
    }
    
    $content = file_get_contents($path);
    $size = strlen($content);
    
    // Check encoding
    if (!mb_detect_encoding($content, 'UTF-8', true)) {
        $issues['encoding_errors'][] = $file;
        $totalErrors++;
        $report[] = "⚠️  ENCODING: $file (not UTF-8)";
    }
    
    // Check if file is empty
    if ($size < 10) {
        $issues['empty_files'][] = $file;
        $totalErrors++;
        $report[] = "❌ EMPTY: $file ($size bytes)";
        continue;
    }
    
    // Check for syntax issues
    $hasOpenTag = strpos($content, '@') !== false || strpos($content, '<') !== false;
    $hasCloseTag = strpos($content, '</') !== false || strpos($content, '?>') !== false;
    
    if (!$hasOpenTag || (!$hasCloseTag && !strpos($content, '@'))) {
        $issues['syntax_errors'][] = $file;
        $totalErrors++;
        $report[] = "⚠️  SYNTAX: $file (missing proper structure)";
    }
    
    // Check for incomplete patterns
    if (preg_match_all('/<\w+[^>]*(?<!>)$/', $content) || 
        preg_match_all('/@[\w]+\s*(?![\w(;])$/', $content)) {
        $issues['incomplete_files'][] = $file;
        $totalErrors++;
        $report[] = "⚠️  INCOMPLETE: $file (unclosed tags/directives)";
    }
    
    // Check for Laravel Blade specific issues
    $bladeErrors = [];
    
    // Check for mismatched @if/@endif
    $ifCount = substr_count($content, '@if');
    $endifCount = substr_count($content, '@endif');
    if ($ifCount !== $endifCount) {
        $bladeErrors[] = "Mismatched @if/@endif ($ifCount vs $endifCount)";
    }
    
    // Check for mismatched @foreach/@endforeach
    $foreachCount = substr_count($content, '@foreach');
    $endforeachCount = substr_count($content, '@endforeach');
    if ($foreachCount !== $endforeachCount) {
        $bladeErrors[] = "Mismatched @foreach/@endforeach ($foreachCount vs $endforeachCount)";
    }
    
    // Check for mismatched @while/@endwhile
    $whileCount = substr_count($content, '@while');
    $endwhileCount = substr_count($content, '@endwhile');
    if ($whileCount !== $endwhileCount) {
        $bladeErrors[] = "Mismatched @while/@endwhile ($whileCount vs $endwhileCount)";
    }
    
    // Check for undefined Blade variables (common issue)
    if (preg_match('/\$\w+(?![a-zA-Z0-9_])/', $content, $matches)) {
        // This is acceptable, but log for review
    }
    
    if (!empty($bladeErrors)) {
        $totalErrors++;
        $report[] = "🔴 BLADE ERRORS in $file:\n   - " . implode("\n   - ", $bladeErrors);
    } else {
        $report[] = "✅ OK: $file ($size bytes)";
    }
}

// Generate summary
echo "=====================================\n";
echo "   BLADE PAGES AUDIT REPORT\n";
echo "=====================================\n\n";

echo "📊 SUMMARY:\n";
echo "   Total Files Checked: $totalChecked\n";
echo "   Total Issues Found: $totalErrors\n";
echo "   Success Rate: " . round(($totalChecked - $totalErrors) / $totalChecked * 100) . "%\n\n";

echo "⚠️  ISSUES BY CATEGORY:\n";
echo "   Missing Files: " . count($issues['missing_files']) . "\n";
echo "   Empty Files: " . count($issues['empty_files']) . "\n";
echo "   Syntax Errors: " . count($issues['syntax_errors']) . "\n";
echo "   Incomplete Files: " . count($issues['incomplete_files']) . "\n";
echo "   Encoding Errors: " . count($issues['encoding_errors']) . "\n\n";

echo "📋 DETAILED REPORT:\n";
echo str_repeat("-", 50) . "\n";

foreach ($report as $line) {
    echo $line . "\n";
}

echo "\n" . str_repeat("=", 50) . "\n";

if (!empty($issues['missing_files'])) {
    echo "\n🔴 MISSING FILES (" . count($issues['missing_files']) . "):\n";
    foreach ($issues['missing_files'] as $file) {
        echo "   - $file\n";
    }
}

if (!empty($issues['empty_files'])) {
    echo "\n🔴 EMPTY FILES (" . count($issues['empty_files']) . "):\n";
    foreach ($issues['empty_files'] as $file) {
        echo "   - $file\n";
    }
}

echo "\n✨ RECOMMENDATIONS:\n";
echo "   1. Create missing Blade files\n";
echo "   2. Fix syntax errors and close all tags\n";
echo "   3. Ensure UTF-8 encoding for all files\n";
echo "   4. Fill empty/incomplete templates\n";
echo "   5. Convert line endings to CRLF\n";
