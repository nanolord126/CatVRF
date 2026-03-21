#!/usr/bin/env powershell
# CANON 2026 — Batch Seeder Modernization Script
# Автоматизирует обновление оставшихся сидеров

$seedersDir = "database/seeders"
$pattern = "*.php"

# Получи список уже обновленных сидеров
$alreadyUpdated = @(
    "DatabaseSeeder",
    "UserSeeder",
    "TenantMasterSeeder",
    "TaxiRideSeeder",
    "FoodOrderSeeder",
    "SalonSeeder",
    "EventSeeder",
    "CourseSeeder",
    "HotelBookingSeeder",
    "SportsMembershipSeeder",
    "PropertySeeder",
    "InventoryItemSeeder",
    "DeliveryOrderSeeder",
    "MedicalCardSeeder",
    "AdCampaignSeeder",
    "GeoZoneSeeder",
    "InsurancePolicySeeder",
    "MessageSeeder",
    "AdPlacementSeeder",
    "AIConstructorSeeder",
    "AiRecommendationsSeeder",
    "AnimalProductSeeder",
    "AutoFilterSeeder",
    "AutomotiveSeeder",
    "AutoVerticalSeeder",
    "BeautyBrands",
    "BeautyFilterSeeder",
    "BeautyShopSeeder",
    "BusinessBranchSeeder",
    "BusinessGroupSeeder",
    "CategoriesAndBrandsSeeder",
    "CategorySystemSeeder",
    "ClinicSeeder",
    "ConcertSeeder",
    "CosmeticsSeeder",
    "CourseInstructorSeeder",
    "CustomerAccountSeeder"
)

# Получи все сидеры
$allSeeders = @(Get-ChildItem -Path $seedersDir -Filter $pattern -File | Select-Object -ExpandProperty BaseName)

# Фильтруй только нужные
$toUpdate = @($allSeeders | Where-Object { $_ -notin $alreadyUpdated })

Write-Host "✅ Уже обновлено: $($alreadyUpdated.Count) сидеров"
Write-Host "📋 К обновлению: $($toUpdate.Count) сидеров"
Write-Host "📊 Всего сидеров: $($allSeeders.Count)"

Write-Host "`n📝 Сидеры к обновлению:"
$toUpdate | ForEach-Object { Write-Host "  - $_" }
