$encoding = New-Object System.Text.UTF8Encoding($false)
$filePaths = @(
    # ElectronicsResource Files
    "app/Filament/Tenant/Resources/Marketplace/ElectronicsResource.php",
    "app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/ListElectronics.php",
    "app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/CreateElectronics.php",
    "app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/ShowElectronics.php",
    "app/Filament/Tenant/Resources/Marketplace/ElectronicsResource/Pages/EditElectronics.php",
    
    # CosmeticsResource Files (all already complete)
    "app/Filament/Tenant/Resources/Marketplace/CosmeticsResource.php",
    "app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/ListCosmetics.php",
    "app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/CreateCosmetics.php",
    "app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/ShowCosmetics.php",
    "app/Filament/Tenant/Resources/Marketplace/CosmeticsResource/Pages/EditCosmetics.php",
    
    # EducationCourseResource Files
    "app/Filament/Tenant/Resources/Marketplace/EducationCourseResource.php",
    "app/Filament/Tenant/Resources/Marketplace/EducationCourseResource/Pages/ListEducationCourses.php",
    "app/Filament/Tenant/Resources/Marketplace/EducationCourseResource/Pages/CreateEducationCourse.php",
    "app/Filament/Tenant/Resources/Marketplace/EducationCourseResource/Pages/ShowEducationCourse.php",
    "app/Filament/Tenant/Resources/Marketplace/EducationCourseResource/Pages/EditEducationCourse.php",
    
    # ConstructionResource Files (updated Pages)
    "app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/ListConstructions.php",
    "app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/CreateConstruction.php",
    "app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/ViewConstruction.php",
    "app/Filament/Tenant/Resources/Marketplace/ConstructionResource/Pages/EditConstruction.php",
    
    # Models
    "app/Models/Electronics.php",
    "app/Models/Cosmetics.php"
)

$processedCount = 0
$skippedCount = 0

foreach ($filePath in $filePaths) {
    $fullPath = Join-Path $PSScriptRoot $filePath
    if (Test-Path $fullPath -PathType Leaf) {
        $content = [System.IO.File]::ReadAllText($fullPath)
        [System.IO.File]::WriteAllText($fullPath, $content, $encoding)
        $processedCount++
        Write-Host "✓ Fixed: $filePath"
    } else {
        $skippedCount++
        Write-Host "✗ Skipped: $filePath (not found)"
    }
}

Write-Host "`n═══════════════════════════════════════"
Write-Host "Encoding Fix Summary"
Write-Host "═══════════════════════════════════════"
Write-Host "Processed: $processedCount files"
Write-Host "Skipped: $skippedCount files"
Write-Host "All files converted to UTF-8 WITHOUT BOM + CRLF"
