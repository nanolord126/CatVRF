# Convert all .blade.php files to UTF-8 CRLF
# This ensures compliance with the project's encoding standards

Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║   BLADE FILES ENCODING & LINE ENDINGS CONVERTER           ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$bladeFiles = @(
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
    'resources/views/components/hotel-card.blade.php'
)

$converted = 0
$failed = 0
$skipped = 0

Write-Host "🔄 Converting $($bladeFiles.Count) Blade files..." -ForegroundColor Yellow
Write-Host ""

foreach ($file in $bladeFiles) {
    $fullPath = Join-Path (Get-Location) $file
    
    if (-not (Test-Path $fullPath)) {
        Write-Host "⚠️  SKIP: $file (not found)" -ForegroundColor Gray
        $skipped++
        continue
    }
    
    try {
        # Read file content
        $content = [System.IO.File]::ReadAllText($fullPath)
        
        # Normalize line endings to CRLF
        $content = $content -replace "`r`n", "`n"  # Remove Windows line endings first
        $content = $content -replace "`n", "`r`n"  # Add Windows line endings
        
        # Remove BOM if exists
        $bytes = [System.Text.Encoding]::UTF8.GetBytes($content)
        
        # Write file with UTF-8 (NO BOM)
        $utf8NoBOM = New-Object System.Text.UTF8Encoding $false
        [System.IO.File]::WriteAllText($fullPath, $content, $utf8NoBOM)
        
        Write-Host "✅ OK: $file" -ForegroundColor Green
        $converted++
    } catch {
        Write-Host "❌ ERROR: $file - $_" -ForegroundColor Red
        $failed++
    }
}

Write-Host ""
Write-Host "╔═══════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║                  CONVERSION COMPLETE                      ║" -ForegroundColor Cyan
Write-Host "╚═══════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""
Write-Host "📊 Results:" -ForegroundColor Yellow
Write-Host "   ✅ Converted: $converted files"
Write-Host "   ❌ Failed: $failed files"
Write-Host "   ⏭️  Skipped: $skipped files"
Write-Host "   📁 Total: $($bladeFiles.Count) files"
Write-Host ""
Write-Host "✨ All Blade files are now UTF-8 with CRLF line endings!" -ForegroundColor Green
Write-Host ""
