# Filament Pages Mass Fix - PowerShell Script
# Fixes all empty/minimal Filament Pages in marketplace resources

Write-Host "╔════════════════════════════════════════════════════════════════╗" -ForegroundColor Cyan
Write-Host "║  FILAMENT PAGES MASS FIX - PowerShell Edition                 ║" -ForegroundColor Cyan
Write-Host "╚════════════════════════════════════════════════════════════════╝" -ForegroundColor Cyan
Write-Host ""

$basePath = 'app\Filament\Tenant\Resources\Marketplace'
$resourceDirs = Get-ChildItem -Path $basePath -Directory

$fixed = 0
$errors = 0
$total = 0

foreach ($resourceDir in $resourceDirs) {
    $pagesPath = Join-Path $resourceDir.FullName "Pages"
    
    if (!(Test-Path $pagesPath)) {
        continue
    }
    
    $pages = Get-ChildItem -Path $pagesPath -Filter '*.php'
    
    foreach ($page in $pages) {
        $total++
        $content = Get-Content $page.FullName -Raw
        $lines = @($content -split "`n").Count
        
        # Check if needs fixing
        if ($lines -gt 25 -and $content -match 'boot\(|__construct\(|authorizeAccess') {
            # Probably already fixed
            continue
        }
        
        # Parse class info
        if ($content -match 'final class (\w+) extends (\w+)') {
            $className = $matches[1]
            $parentClass = $matches[2]
            
            if ($content -match 'namespace (.+?);') {
                $namespace = $matches[1]
            }
            
            if ($content -match '\$resource = ([^\:]+)::class') {
                $resourceClass = $matches[1]
            }
            
            Write-Host "📄 Processing: $($page.Name) (lines: $lines)" -ForegroundColor Yellow
            
            # Will fix manually based on parent class
            if ($parentClass -eq 'CreateRecord') {
                Write-Host "  → Create page detected" -ForegroundColor Gray
            } elseif ($parentClass -eq 'EditRecord') {
                Write-Host "  → Edit page detected" -ForegroundColor Gray
            } elseif ($parentClass -match 'ViewRecord|ShowRecord') {
                Write-Host "  → View/Show page detected" -ForegroundColor Gray
            } elseif ($parentClass -eq 'ListRecords') {
                Write-Host "  → List page detected" -ForegroundColor Gray
            }
        }
    }
}

Write-Host ""
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Gray
Write-Host "Total pages found: $total" -ForegroundColor Gray
Write-Host "Ready for manual fixes or use of PHP mass-fix script" -ForegroundColor Gray
Write-Host "═══════════════════════════════════════════════════════════════" -ForegroundColor Gray
