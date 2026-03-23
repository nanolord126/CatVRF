#!/usr/bin/env pwsh

# CatVRF Real-Time Analytics - Multi-Server Startup Script
# Запускает PHP, Vite и Reverb в фоновом режиме

$ErrorActionPreference = "Stop"

Write-Host @"

==========================================
CatVRF Real-Time Analytics - Quick Start
==========================================

"@ -ForegroundColor Cyan

# Проверка зависимостей
Write-Host "Checking dependencies..." -ForegroundColor Yellow

$php = Get-Command php -ErrorAction SilentlyContinue
$npm = Get-Command npm -ErrorAction SilentlyContinue

if (-not $php) {
    Write-Host "Error: PHP not found in PATH" -ForegroundColor Red
    exit 1
}

if (-not $npm) {
    Write-Host "Error: npm not found in PATH" -ForegroundColor Red
    exit 1
}

Write-Host "✓ PHP found" -ForegroundColor Green
Write-Host "✓ npm found" -ForegroundColor Green
Write-Host ""

# Переход в директорию проекта
$projectDir = "c:\opt\kotvrf\CatVRF"
if (-not (Test-Path $projectDir)) {
    Write-Host "Error: Project directory not found: $projectDir" -ForegroundColor Red
    exit 1
}

Set-Location $projectDir
Write-Host "Starting servers in $projectDir" -ForegroundColor Yellow
Write-Host ""

# Функция для запуска сервера в отдельном PowerShell окне
function Start-ServerWindow {
    param(
        [string]$Title,
        [string]$Command,
        [int]$Delay = 0
    )
    
    Write-Host "[+] Starting: $Title" -ForegroundColor Cyan
    
    $sb = [scriptblock]::Create($Command)
    
    # Запуск в отдельном процессе PowerShell
    $process = Start-Process PowerShell -ArgumentList @"
-NoExit
-Command { 
    Set-Location '$projectDir'
    Write-Host '[$Title] Starting...' -ForegroundColor Green
    Invoke-Command -ScriptBlock {$sb}
}
"@ -PassThru -WindowStyle Normal
    
    if ($Delay -gt 0) {
        Start-Sleep -Seconds $Delay
    }
    
    return $process
}

# Запуск всех серверов

Write-Host "Starting servers..." -ForegroundColor Yellow
Write-Host ""

# 1. PHP Dev Server
$phpProcess = Start-ServerWindow `
    -Title "PHP Dev Server (http://127.0.0.1:8000)" `
    -Command "php artisan serve --host=127.0.0.1 --port=8000" `
    -Delay 3

Write-Host "  ✓ PHP server started (PID: $($phpProcess.Id))" -ForegroundColor Green

# 2. Vite Dev Server
$viteProcess = Start-ServerWindow `
    -Title "Vite Dev Server (http://localhost:5173)" `
    -Command "npm run dev" `
    -Delay 4

Write-Host "  ✓ Vite server started (PID: $($viteProcess.Id))" -ForegroundColor Green

# 3. Reverb WebSocket
$reverbProcess = Start-ServerWindow `
    -Title "Reverb WebSocket (ws://localhost:8080)" `
    -Command "php artisan reverb:start" `
    -Delay 2

Write-Host "  ✓ Reverb server started (PID: $($reverbProcess.Id))" -ForegroundColor Green

Write-Host ""
Write-Host "==========================================" -ForegroundColor Cyan
Write-Host "Services started successfully!" -ForegroundColor Green
Write-Host "==========================================" -ForegroundColor Cyan

Write-Host @"

Web Application:
  → http://127.0.0.1:8000

Vite Dev Server:
  → http://localhost:5173

Analytics Dashboard:
  → http://127.0.0.1:8000/analytics/heatmaps

WebSocket (Reverb):
  → ws://localhost:8080

Real-Time Polling:
  → Enabled (30 second intervals)
  → Auto-refresh active

Monitor Logs:
  → Get-Content storage/logs/laravel.log -Tail 50 -Wait | Select-String -Pattern "polling"
  → Or use: tail -f storage/logs/laravel.log | grep -i polling

To Stop All Services:
  → Close all PowerShell windows
  → Or press CTRL+C in any window

=========================================

"@ -ForegroundColor Cyan

# Функция для мониторинга процессов
Write-Host "Process Monitor:" -ForegroundColor Yellow

while ($true) {
    $phpRunning = Get-Process | Where-Object { $_.Id -eq $phpProcess.Id -and $_.ProcessName -like "php*" }
    $viteRunning = Get-Process | Where-Object { $_.Id -eq $viteProcess.Id -and $_.ProcessName -like "node*" }
    $reverbRunning = Get-Process | Where-Object { $_.Id -eq $reverbProcess.Id -and $_.ProcessName -like "php*" }
    
    Write-Host "`rPHP: $(if($phpRunning) { '✓ Running' } else { '✗ Stopped' }) | Vite: $(if($viteRunning) { '✓ Running' } else { '✗ Stopped' }) | Reverb: $(if($reverbRunning) { '✓ Running' } else { '✗ Stopped' })" -NoNewline -ForegroundColor Cyan
    
    if (-not $phpRunning -or -not $viteRunning -or -not $reverbRunning) {
        Write-Host ""
        Write-Host "One or more services have stopped!" -ForegroundColor Red
        break
    }
    
    Start-Sleep -Seconds 5
}

Write-Host ""
Write-Host "All servers have been stopped." -ForegroundColor Yellow
