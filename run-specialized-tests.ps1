#!/usr/bin/env pwsh
<#
.SYNOPSIS
    Запуск специализированных E2E тестов (тепловые карты, ОФД, ML, аналитика, безопасность и т.д.)
.DESCRIPTION
    Скрипт позволяет быстро запускать тесты по категориям и отслеживать результаты
#>

param(
    [string]$Category = "all",
    [switch]$Headless = $false,
    [switch]$Report = $false
)

$specPath = "cypress/e2e"
$timestamp = Get-Date -Format "yyyy-MM-dd-HHmmss"

Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "🧪 ЗАПУСК СПЕЦИАЛИЗИРОВАННЫХ E2E ТЕСТОВ" -ForegroundColor Cyan
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan

$specs = @{
    "heatmap" = "heatmap-analytics.cy.ts"
    "transactions" = "test-transactions.cy.ts"
    "cashback" = "cashback-rewards.cy.ts"
    "chargebacks" = "chargebacks-disputes.cy.ts"
    "ofd" = "ofd-fiscalization.cy.ts"
    "ml" = "ml-ai-services.cy.ts"
    "analytics" = "analytics-bigdata.cy.ts"
    "fraud" = "fraud-attacks.cy.ts"
    "security" = "security-threats.cy.ts"
}

$categories = @{
    "payments" = @("test-transactions.cy.ts", "cashback-rewards.cy.ts", "chargebacks-disputes.cy.ts")
    "ofd" = @("ofd-fiscalization.cy.ts")
    "ai" = @("ml-ai-services.cy.ts")
    "analytics" = @("analytics-bigdata.cy.ts", "heatmap-analytics.cy.ts")
    "security" = @("fraud-attacks.cy.ts", "security-threats.cy.ts")
}

function Run-Test {
    param(
        [string]$SpecFile,
        [string]$Label
    )
    
    Write-Host "`n✅ Запуск: $Label" -ForegroundColor Green
    Write-Host "   📁 $SpecFile" -ForegroundColor Gray
    
    $headlessArg = if ($Headless) { "--headless" } else { "" }
    
    npm run cypress:run -- --spec "cypress/e2e/$SpecFile" $headlessArg
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ Пройдено: $Label" -ForegroundColor Green
        return $true
    } else {
        Write-Host "❌ Ошибка: $Label" -ForegroundColor Red
        return $false
    }
}

function Run-Multiple {
    param(
        [string[]]$SpecFiles,
        [string]$CategoryLabel
    )
    
    Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Yellow
    Write-Host "📂 Категория: $CategoryLabel" -ForegroundColor Yellow
    Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Yellow
    
    $specList = ($SpecFiles | ForEach-Object { "cypress/e2e/$_" }) -join ","
    $headlessArg = if ($Headless) { "--headless" } else { "" }
    
    npm run cypress:run -- --spec $specList $headlessArg
    
    return $LASTEXITCODE -eq 0
}

# ============================================================================

switch ($Category) {
    "heatmap" {
        Run-Test "heatmap-analytics.cy.ts" "🔥 Тепловые карты"
    }
    
    "transactions" {
        Run-Test "test-transactions.cy.ts" "💳 Тестовые транзакции"
    }
    
    "cashback" {
        Run-Test "cashback-rewards.cy.ts" "💰 Кешбек и награды"
    }
    
    "chargebacks" {
        Run-Test "chargebacks-disputes.cy.ts" "🔄 Чарджбеки и споры"
    }
    
    "ofd" {
        Run-Test "ofd-fiscalization.cy.ts" "📄 ОФД и чеки"
    }
    
    "ml" {
        Run-Test "ml-ai-services.cy.ts" "🤖 ML & AI сервисы"
    }
    
    "analytics" {
        Run-Test "analytics-bigdata.cy.ts" "📊 Аналитика и BigData"
    }
    
    "fraud" {
        Run-Test "fraud-attacks.cy.ts" "🔓 Фрауд-атаки"
    }
    
    "security" {
        Run-Test "security-threats.cy.ts" "🔐 Вирусы, скам, DDoS"
    }
    
    "payments" {
        $success = $true
        foreach ($spec in $categories["payments"]) {
            $label = @{"test-transactions.cy.ts"="💳 Тестовые транзакции"; "cashback-rewards.cy.ts"="💰 Кешбек"; "chargebacks-disputes.cy.ts"="🔄 Чарджбеки"}[$spec]
            $success = (Run-Test $spec $label) -and $success
        }
        if ($success) {
            Write-Host "`n✅ Все платёжные тесты пройдены!" -ForegroundColor Green
        } else {
            Write-Host "`n❌ Некоторые тесты не прошли!" -ForegroundColor Red
        }
    }
    
    "ofd" {
        Run-Test "ofd-fiscalization.cy.ts" "📄 ОФД и чеки"
    }
    
    "ai" {
        Run-Test "ml-ai-services.cy.ts" "🤖 ML & AI сервисы"
    }
    
    "analytics" {
        $success = $true
        foreach ($spec in $categories["analytics"]) {
            $label = @{"analytics-bigdata.cy.ts"="📊 Аналитика"; "heatmap-analytics.cy.ts"="🔥 Тепловые карты"}[$spec]
            $success = (Run-Test $spec $label) -and $success
        }
        if ($success) {
            Write-Host "`n✅ Все аналитические тесты пройдены!" -ForegroundColor Green
        } else {
            Write-Host "`n❌ Некоторые тесты не прошли!" -ForegroundColor Red
        }
    }
    
    "security" {
        $success = $true
        foreach ($spec in $categories["security"]) {
            $label = @{"fraud-attacks.cy.ts"="🔓 Фрауд-атаки"; "security-threats.cy.ts"="🔐 Угрозы безопасности"}[$spec]
            $success = (Run-Test $spec $label) -and $success
        }
        if ($success) {
            Write-Host "`n✅ Все тесты безопасности пройдены!" -ForegroundColor Green
        } else {
            Write-Host "`n❌ Некоторые тесты не прошли!" -ForegroundColor Red
        }
    }
    
    "all" {
        $allSpecs = @()
        foreach ($spec in $specs.Values) {
            $allSpecs += $spec
        }
        $specList = ($allSpecs | ForEach-Object { "cypress/e2e/$_" }) -join ","
        
        Write-Host "`n🚀 Запуск всех 9 категорий специализированных тестов..." -ForegroundColor Green
        Write-Host "   📊 Всего тестов: 358+" -ForegroundColor Gray
        Write-Host "   📁 Всего файлов: 9" -ForegroundColor Gray
        
        $headlessArg = if ($Headless) { "--headless" } else { "" }
        npm run cypress:run -- --spec $specList $headlessArg
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host "`n✅ ВСЕ ТЕСТЫ ПРОЙДЕНЫ УСПЕШНО!" -ForegroundColor Green
        } else {
            Write-Host "`n❌ НЕКОТОРЫЕ ТЕСТЫ НЕ ПРОШЛИ!" -ForegroundColor Red
        }
    }
    
    default {
        Write-Host "`n❌ Неизвестная категория: $Category" -ForegroundColor Red
        Write-Host "`nДоступные категории:" -ForegroundColor Yellow
        Write-Host "  • heatmap      - 🔥 Тепловые карты (30+ тестов)" -ForegroundColor White
        Write-Host "  • transactions - 💳 Тестовые транзакции (25+ тестов)" -ForegroundColor White
        Write-Host "  • cashback     - 💰 Кешбек и награды (35+ тестов)" -ForegroundColor White
        Write-Host "  • chargebacks  - 🔄 Чарджбеки и споры (28+ тестов)" -ForegroundColor White
        Write-Host "  • ofd          - 📄 ОФД и чеки (40+ тестов)" -ForegroundColor White
        Write-Host "  • ml           - 🤖 ML & AI сервисы (45+ тестов)" -ForegroundColor White
        Write-Host "  • analytics    - 📊 Аналитика и BigData (55+ тестов)" -ForegroundColor White
        Write-Host "  • fraud        - 🔓 Фрауд-атаки (50+ тестов)" -ForegroundColor White
        Write-Host "  • security     - 🔐 Вирусы, скам, DDoS (50+ тестов)" -ForegroundColor White
        Write-Host "`nПолные категории:" -ForegroundColor Yellow
        Write-Host "  • payments     - Все платёжные тесты (88+ тестов)" -ForegroundColor White
        Write-Host "  • analytics    - Все аналитические тесты (85+ тестов)" -ForegroundColor White
        Write-Host "  • security     - Все тесты безопасности (100+ тестов)" -ForegroundColor White
        Write-Host "  • all          - ВСЕ тесты (358+ тестов) 🚀" -ForegroundColor Green
        Write-Host "`nПримеры:" -ForegroundColor Yellow
        Write-Host "  ./run-specialized-tests.ps1 -Category all" -ForegroundColor Cyan
        Write-Host "  ./run-specialized-tests.ps1 -Category security -Headless" -ForegroundColor Cyan
        Write-Host "  ./run-specialized-tests.ps1 -Category payments -Report" -ForegroundColor Cyan
    }
}

Write-Host "`n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
Write-Host "✅ Выполнено!" -ForegroundColor Green
Write-Host "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━" -ForegroundColor Cyan
