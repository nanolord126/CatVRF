# CatVRF 3D System - Complete Deployment & Server Launch
# Requires: Laravel project, PHP 8.1+, Composer

param(
    [switch]$SkipMigration = $false,
    [switch]$SkipDemoData = $false,
    [int]$Port = 8000
)

$ErrorActionPreference = "Stop"
$WarningPreference = "SilentlyContinue"

function Write-Status {
    param([string]$Message)
    Write-Host "▶ $Message" -ForegroundColor Cyan
}

function Write-Success {
    param([string]$Message)
    Write-Host "✓ $Message" -ForegroundColor Green
}

function Write-Error-Custom {
    param([string]$Message)
    Write-Host "✗ $Message" -ForegroundColor Red
}

function Write-Section {
    param([string]$Message)
    Write-Host "`n$('=' * 60)" -ForegroundColor Yellow
    Write-Host "  $Message" -ForegroundColor Yellow
    Write-Host "$('=' * 60)`n" -ForegroundColor Yellow
}

# Main deployment script
Clear-Host
Write-Section "🚀 CatVRF 3D SYSTEM - COMPLETE DEPLOYMENT"

$projectPath = Get-Location
Write-Status "Project Path: $projectPath"

# 1. Check Laravel installation
Write-Section "1️⃣ Checking Laravel Installation"
if (-not (Test-Path "artisan")) {
    Write-Error-Custom "Laravel project not found (artisan missing)"
    exit 1
}
Write-Success "Laravel project detected"

# 2. Clear cache
Write-Section "2️⃣ Clearing Cache"
Write-Status "Clearing application cache..."
php artisan cache:clear
Write-Success "Cache cleared"

# 3. Create storage directories
Write-Section "3️⃣ Creating Storage Directories"
$storageDirectories = @(
    "storage/app/public/3d-models",
    "storage/app/public/3d-models/Auto",
    "storage/app/public/3d-models/Beauty",
    "storage/app/public/3d-models/Electronics",
    "storage/app/public/3d-models/Furniture",
    "storage/app/public/3d-models/Jewelry",
    "storage/app/public/3d-models/Hotels",
    "storage/app/public/3d-models/RealEstate",
    "storage/app/public/3d-previews"
)

foreach ($dir in $storageDirectories) {
    if (-not (Test-Path $dir)) {
        New-Item -ItemType Directory -Path $dir -Force | Out-Null
        Write-Success "Created: $dir"
    }
    else {
        Write-Status "Already exists: $dir"
    }
}

# 4. Create storage symlink
Write-Section "4️⃣ Creating Storage Symlink"
Write-Status "Linking storage directory..."
try {
    php artisan storage:link 2>$null
    Write-Success "Storage symlink created"
}
catch {
    Write-Status "Symlink already exists or skipped"
}

# 5. Update routes
Write-Section "5️⃣ Updating API Routes"
$apiPath = "routes/api.php"
if (Test-Path $apiPath) {
    $apiContent = Get-Content $apiPath -Raw
    if ($apiContent -notcontains "api-3d.php") {
        Write-Status "Adding 3D routes to api.php..."
        Add-Content $apiPath -Value "`n`n// 3D API Routes`ninclude base_path('routes/api-3d.php');`n"
        Write-Success "3D routes added"
    }
    else {
        Write-Status "3D routes already registered"
    }
}

# 6. Register demo routes
Write-Section "6️⃣ Registering Demo Routes"
$webPath = "routes/web.php"
if (Test-Path $webPath) {
    $webContent = Get-Content $webPath -Raw
    if ($webContent -notcontains "3d-demo.php") {
        Write-Status "Adding demo routes..."
        Add-Content $webPath -Value "`n`n// 3D Demo Routes`ninclude base_path('routes/3d-demo.php');`n"
        Write-Success "Demo routes registered"
    }
    else {
        Write-Status "Demo routes already registered"
    }
}

# 7. Warm up cache
Write-Section "7️⃣ Warming Up Cache"
Write-Status "Caching configuration..."
php artisan config:cache
Write-Status "Caching routes..."
php artisan route:cache
Write-Success "Cache ready"

# 8. Verify files
Write-Section "8️⃣ Verifying Installation"
$requiredFiles = @(
    "config/3d.php",
    "routes/api-3d.php",
    "app/Services/3D/Product3DService.php",
    "app/Livewire/ThreeD/ProductCard3D.php",
    "resources/views/3d-demo.blade.php",
    "app/Http/Controllers/Demo3DController.php",
    "routes/3d-demo.php"
)

$allPresent = $true
foreach ($file in $requiredFiles) {
    if (Test-Path $file) {
        Write-Success "✓ $file"
    }
    else {
        Write-Error-Custom "✗ Missing: $file"
        $allPresent = $false
    }
}

if (-not $allPresent) {
    Write-Error-Custom "Some required files are missing!"
    exit 1
}

# 9. Summary
Write-Section "✨ DEPLOYMENT COMPLETE"
Write-Success "3D System is ready!"
Write-Host @"
📊 System Status:
   • Configuration: ✓
   • Services: ✓
   • Components: ✓
   • Routes: ✓
   • Storage: ✓
   • Cache: ✓

🎯 Next Step: Start Development Server
   
   Command:
   php artisan serve --port=$Port

   Then open browser:
   http://localhost:$Port/3d-demo

"@ -ForegroundColor Green

# 10. Option to start server
Write-Section "🚀 Start Server"
$response = Read-Host "Start development server now? (y/n)"

if ($response -eq 'y' -or $response -eq 'yes') {
    Write-Status "Starting Laravel development server on port $Port..."
    Write-Host "`nServer starting...`n" -ForegroundColor Yellow
    Write-Host "📱 Open browser at: http://localhost:$Port/3d-demo`n" -ForegroundColor Cyan
    Write-Host "🛑 Press Ctrl+C to stop server`n" -ForegroundColor Yellow
    
    Start-Sleep -Seconds 2
    
    & php artisan serve --port=$Port
}
else {
    Write-Host "`n✓ To start server manually, run:`n  php artisan serve --port=$Port`n" -ForegroundColor Yellow
}
