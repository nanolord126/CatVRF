<?php
// Анализ требуемых файлов миграций

$requiredTenant = [
    '0000_00_00_000000_create_users_table.php',
    '0000_00_00_000001_create_permission_tables.php',
    '0000_00_00_000002_create_jobs_table.php',
    '2018_11_06_222923_create_transactions_table.php',
    '2018_11_07_192923_create_transfers_table.php',
    '2018_11_15_124230_create_wallets_table.php',
    '2021_11_02_202021_update_wallets_uuid_table.php',
    '2023_12_30_113122_extra_columns_removed.php',
    '2023_12_30_204610_soft_delete.php',
    '2024_01_24_185401_add_extra_column_in_transfer.php',
    '2026_03_06_000001_create_tenant_data_tables.php',
    '2026_03_06_000002_create_payments_and_wallets_data.php',
    '2026_03_06_000003_create_specialized_modules_tables.php',
    '2026_03_06_100000_create_geo_hierarchy_tables.php',
    '2026_03_06_100001_create_ai_ml_infrastructure_tables.php',
    '2026_03_06_100002_create_inventory_module_tables.php',
    '2026_03_06_110000_add_operational_to_stock_movements.php',
    '2026_03_06_120000_create_staff_module_tables.php',
    '2026_03_06_140000_create_payouts_table.php',
    '2026_03_06_150000_create_ai_infrastructure_tables.php',
    '2026_03_06_150001_create_payroll_module_tables.php',
    '2026_03_06_160000_create_hr_module_tables.php',
    '2026_03_06_170000_create_b2b_module_tables.php',
    '2026_03_06_180000_create_marketplace_verticals_tables.php',
    '2026_03_06_189000_create_basic_verticals_tables.php',
    '2026_03_06_200001_create_adaptive_marketing_tables.php',
    '2026_03_06_200003_create_staff_prediction_tables.php',
    '2026_03_06_210000_create_taxi_fleet_management_tables.php',
    '2026_03_06_220000_add_tariffs_and_surge_to_taxi_tables.php',
    '2026_03_06_230000_split_food_into_restaurant_and_supermarket.php',
    '2026_03_06_233000_create_restaurant_floor_and_kds_tables.php',
    '2026_03_06_240000_create_clinics_vertical_tables.php',
    '2026_03_06_250000_create_health_recommendations_and_reminders_tables.php',
    '2026_03_06_300000_create_internal_hr_job_board_tables.php',
    '2026_03_06_300001_enhance_matching_for_ai.php',
    '2026_03_06_300002_create_b2b_supply_tables.php',
    '2026_03_06_400000_create_b2b_marketplace_tables.php',
    '2026_03_06_400001_create_hr_exchange_platform_tables.php',
    '2026_03_06_500000_create_hr_reputation_tables.php',
    '2026_03_06_600000_create_public_marketplace_facade_tables.php',
    '2026_03_06_700000_create_ai_consumer_analytics_tables.php',
    '2026_03_06_800000_create_ai_dynamic_pricing_tables.php',
    '2026_03_06_800001_create_full_taxi_vertical_tables.php',
    '2026_03_06_900000_create_ai_security_and_api_gateway_tables.php',
    '2026_03_06_900001_create_core_verticals_production_tables.php',
    '2026_03_06_990000_create_ai_notifications_and_logistics_tables.php',
    '2026_03_06_999000_create_digital_twin_tables.php',
    '2026_03_06_1000000_create_cross_vertical_loyalty_tables.php',
    '2026_03_07_000001_create_advertising_tables.php',
    '2026_03_07_000002_create_video_calls_table.php',
    '2026_03_08_120000_add_type_to_venues_table.php',
    '2026_03_08_200000_create_new_verticals_tables.php',
    '2026_03_08_210000_expand_crm_features_tenant.php',
    '2026_03_08_214004_create_ecosystem_catalog_tables.php',
    '2026_03_08_220000_create_crm_core_tables.php',
    '2026_03_09_132726_create_settlement_documents_table.php',
    '2026_03_09_132735_create_wishlists_and_items_table.php',
    '2026_03_09_132746_add_reporting_settings_to_users.php',
    '2026_03_09_163747_add_fields_to_tenant_tables.php',
    '2026_03_15_000101_create_vet_clinic_services_table.php',
    '2026_03_15_000102_create_animal_products_table.php',
    '2026_03_15_000103_create_sport_products_table.php',
    '2026_03_15_000104_create_sport_events_table.php',
    '2026_03_15_000105_create_sport_nutrition_table.php',
    '2026_03_15_000106_create_sport_coaches_table.php',
    '2026_03_15_000107_create_dance_events_table.php',
    '2026_03_15_000108_create_courses_table.php',
    '2026_03_15_000109_create_course_instructors_table.php',
    '2026_03_15_000110_create_student_enrollments_table.php',
    '2026_03_15_000111_create_taxi_drivers_table.php',
    '2026_03_15_000112_create_taxi_vehicles_table.php',
    '2026_03_15_000113_add_license_number_to_clinic_services.php',
    '2026_03_15_000114_expand_hotels_table_booking_style.php',
    '2026_03_15_000115_create_alternative_accommodation_verticals.php',
];

$requiredRoot = [
    '0001_01_01_000000_create_users_table.php',
    '0001_01_01_000001_create_cache_table.php',
    '0001_01_01_000002_create_jobs_table.php',
    '2019_09_15_000010_create_tenants_table.php',
    '2019_09_15_000020_create_domains_table.php',
    '2024_01_12_000001_create_inventories_table.php',
    '2024_01_12_000002_create_payrolls_table.php',
    '2024_01_12_000003_create_employees_table.php',
    '2024_01_12_000004_create_newsletters_table.php',
    '2026_03_05_231830_create_permission_tables.php',
    '2026_03_06_000000_create_agency_referrals_table.php',
    '2026_03_06_000001_create_marketplace_verticals_tables.php',
    '2026_03_06_100000_create_geo_hierarchy_tables.php',
    '2026_03_06_110000_create_finance_tables.php',
    '2026_03_06_120000_update_finance_for_sbp.php',
    '2026_03_06_130000_add_commission_uplift_to_tenants.php',
    '2026_03_06_130000_create_recurring_tables.php',
    '2026_03_06_140000_create_fraud_payout_tables.php',
    '2026_03_06_150000_create_ai_infrastructure_tables.php',
    '2026_03_06_400000_create_b2b_marketplace_tables.php',
    '2026_03_06_500000_create_b2b_recommendation_tables.php',
    '2026_03_06_600000_create_fraud_infrastructure_tables.php',
    '2026_03_06_700000_create_support_and_chat_tables.php',
    '2026_03_08_000000_create_real_estate_and_audit_tables.php',
    '2026_03_08_000001_update_venues_table_for_hospitality.php',
    '2026_03_08_123744_create_push_subscriptions_table.php',
    '2026_03_08_132919_create_business_groups_table.php',
    '2026_03_08_132932_add_parent_id_to_tenants_table.php',
    '2026_03_08_133002_add_two_factor_auth_to_users_table.php',
    '2026_03_08_210000_expand_crm_features.php',
    '2026_03_08_212705_add_geo_radius_to_service_entities.php',
    '2026_03_08_212718_add_geo_radius_to_properties.php',
    '2026_03_08_212732_add_is_platform_partner_to_b2b_manufacturers.php',
    '2026_03_08_220000_create_crm_tables.php',
    '2026_03_08_230000_create_expansion_features_tables.php',
    '2026_03_09_160000_create_ai_designer_sessions_table.php',
    '2026_03_09_213628_add_b2b_services_to_tenant_schema.php',
    '2026_03_10_150934_create_inventory_items_table.php',
    '2026_03_10_151540_create_food_orders_table.php',
    '2026_03_10_151554_create_hotel_bookings_table.php',
    '2026_03_10_151617_create_sports_memberships_table.php',
    '2026_03_10_151634_create_medical_cards_table.php',
    '2026_03_10_151655_create_delivery_orders_table.php',
    '2026_03_10_151933_create_ad_campaigns_table.php',
    '2026_03_10_152000_create_geo_zones_table.php',
    '2026_03_10_160000_create_filters_table.php',
    '2026_03_10_221820_create_transition_confirmations_table.php',
    '2026_03_10_222258_add_transition_id_to_tenants_table.php',
    '2026_03_11_022651_create_ai_constructors_table.php',
    '2026_03_11_120000_create_beauty_services_table.php',
    '2026_03_11_120100_create_beauty_bookings_table.php',
    '2026_03_11_120200_create_beauty_payments_table.php',
    '2026_03_14_000000_create_automotive_table.php',
    '2026_03_14_000001_create_furniture_table.php',
    '2026_03_14_000002_create_constructions_table.php',
    '2026_03_14_000003_create_repairs_table.php',
    '2026_03_14_000004_create_garden_products_table.php',
    '2026_03_14_000005_create_flowers_table.php',
    '2026_03_14_000006_create_restaurants_table.php',
    '2026_03_14_000007_create_taxi_services_table.php',
    '2026_03_15_000001_create_marketplace_products_table.php',
    '2026_03_15_000002_create_marketplace_services_table.php',
    '2026_03_15_000003_create_customer_accounts_table.php',
    '2026_03_15_000004_create_customer_reviews_table.php',
    '2026_03_15_000005_create_customer_wishlists_table.php',
    '2026_03_15_000006_create_customer_addresses_table.php',
    '2026_03_15_create_metrics_log_table.php',
    '2026_03_16_000001_create_courses_table.php',
    '2026_03_16_000002_create_messages_table.php',
    '2026_03_16_000003_create_payment_transactions_table.php',
    '2026_03_16_000004_create_salons_table.php',
    '2026_03_16_000005_create_taxi_rides_table.php',
];

$tenantPath = 'C:\\opt\\kotvrf\\CatVRF\\database\\migrations\\tenant';
$rootPath = 'C:\\opt\\kotvrf\\CatVRF\\database\\migrations';

echo "=== TENANT MIGRATIONS ===\n";
$tenantExisting = array_map('basename', glob($tenantPath . '/*.php'));
$tenantMissing = [];
foreach ($requiredTenant as $file) {
    if (!in_array($file, $tenantExisting)) {
        $tenantMissing[] = $file;
    }
}

echo "Required: " . count($requiredTenant) . "\n";
echo "Existing: " . count($tenantExisting) . "\n";
echo "Missing: " . count($tenantMissing) . "\n";

if (!empty($tenantMissing)) {
    echo "\nMissing tenant files:\n";
    foreach ($tenantMissing as $file) {
        echo "  - $file\n";
    }
}

echo "\n=== ROOT MIGRATIONS ===\n";
$rootExisting = array_map('basename', glob($rootPath . '/*.php'));
$rootMissing = [];
foreach ($requiredRoot as $file) {
    if (!in_array($file, $rootExisting)) {
        $rootMissing[] = $file;
    }
}

echo "Required: " . count($requiredRoot) . "\n";
echo "Existing: " . count($rootExisting) . "\n";
echo "Missing: " . count($rootMissing) . "\n";

if (!empty($rootMissing)) {
    echo "\nMissing root files:\n";
    foreach ($rootMissing as $file) {
        echo "  - $file\n";
    }
}

echo "\n=== DUPLICATES ===\n";
$tenantDupes = array_diff_assoc($tenantExisting, array_unique($tenantExisting));
if (empty($tenantDupes)) {
    echo "Tenant: No duplicates\n";
} else {
    echo "Tenant: " . count($tenantDupes) . " duplicates\n";
}

$rootDupes = array_diff_assoc($rootExisting, array_unique($rootExisting));
if (empty($rootDupes)) {
    echo "Root: No duplicates\n";
} else {
    echo "Root: " . count($rootDupes) . " duplicates\n";
}
