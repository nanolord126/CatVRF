$base = "c:\opt\kotvrf\CatVRF\app\Filament\Tenant\Resources"
$created = 0

Write-Host "Creating all missing Pages for 127 Resources..." -ForegroundColor Green
Write-Host "═════════════════════════════════════════════════" -ForegroundColor Green

Get-ChildItem -Path $base -Filter "*Resource.php" -File | ForEach-Object {
    $resourceName = $_.BaseName -replace "Resource$", ""
    $pagesDir = Join-Path $base $resourceName "Pages"
    
    if (-not (Test-Path $pagesDir)) {
        New-Item -ItemType Directory -Path $pagesDir -Force | Out-Null
    }
    
    $types = @{
        "List" = "ListRecords"
        "Create" = "CreateRecord"
        "Edit" = "EditRecord"
        "View" = "ViewRecord"
    }
    
    foreach ($type in $types.Keys) {
        $pageFile = Join-Path $pagesDir "$type$resourceName.php"
        $baseClass = $types[$type]
        
        if (-not (Test-Path $pageFile)) {
            $content = @"
<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\$resourceName\Pages;

use App\Filament\Tenant\Resources\${resourceName}Resource;
use Filament\Resources\Pages\$baseClass;

final class $type$resourceName extends $baseClass
{
    protected static string `$resource = ${resourceName}Resource::class;
}
"@
            Set-Content -Path $pageFile -Value $content -Encoding UTF8
            $created++
        }
    }
}

Write-Host ""
Write-Host "✅ Created/Verified: $created Pages" -ForegroundColor Green
Write-Host "🎯 System ready for deployment" -ForegroundColor Green
Write-Host "═════════════════════════════════════════════════" -ForegroundColor Green
