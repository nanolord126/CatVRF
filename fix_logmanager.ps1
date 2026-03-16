#!/usr/bin/env pwsh
Set-StrictMode -Off

$eventsDir = "c:\opt\kotvrf\CatVRF\app\Events"
$count = 0

Get-ChildItem "$eventsDir/*.php" | ForEach-Object {
    $file = $_.FullName
    $content = Get-Content $file -Raw
    $original = $content
    
    if ($content -notmatch 'LogManager') {
        return
    }
    
    $content = $content -replace 'use App\\Services\\LogManager;', 'use Illuminate\Support\Facades\Log;'
    $content = $content -replace 'app\(\\App\\Services\\LogManager::class\)->channel\(''audit''\)->info\(', 'Log::channel(''audit'')->info('
    $content = $content -replace 'app\(\\App\\Services\\LogManager::class\)->channel\(''errors''\)->error\(', 'Log::channel(''errors'')->error('
    $content = $content -replace 'app\(LogManager::class\)->channel\(''audit''\)->info\(', 'Log::channel(''audit'')->info('
    $content = $content -replace 'app\(LogManager::class\)->channel\(''errors''\)->error\(', 'Log::channel(''errors'')->error('
    
    if ($content -ne $original) {
        Set-Content $file $content -Encoding UTF8 -NoNewline
        $count++
        Write-Host "✓ Fixed: $($_.Name)"
    }
}

Write-Host "Total files fixed: $count"
