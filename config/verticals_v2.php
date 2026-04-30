<?php

declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════
 * CATVRF 2026 — СУПЕР-ВЕРТИКАЛИ (15 продуктовых доменов)
 * ═══════════════════════════════════════════════════════════════
 *
 * Архитектура:
 * - 15 супер-вертикалей для продуктовых доменов (B2C)
 * - Технические домены (Delivery, GeoLogistics, Cart, Inventory) остаются отдельными
 * - Каждая супер-вертикаль содержит подвертикали как под-домены
 * - Common домен для shared product logic
 *
 * Структура app/Domains/:
 * ├── Supermarket/
 * ├── BeautyAndPersonalCare/
 * ├── Pharmacy/
 * ├── FashionAndLuxury/
 * ├── LeisureAndEntertainment/
 * ├── Common/
 * └── ...
 * ═══════════════════════════════════════════════════════════════
 */

return [
    'super_verticals' => [
        // ══════════════════════════════════════════════════════
        // 1. SUPERMARKET & GROCERY
        // ══════════════════════════════════════════════════════
        'supermarket' => [
            'name' => 'Supermarket & Grocery',
            'type' => 'product',
            'active' => true,
            'description' => 'Продуктовые магазины, фермерские продукты, полуфабрикаты',
            'sub_verticals' => [
                'meat_shops' => ['name' => 'Meat Shops', 'model' => 'MeatShop', 'active' => false],
                'farm_direct' => ['name' => 'Farm Direct', 'model' => 'Farm', 'active' => false],
                'vegan_products' => ['name' => 'Vegan Products', 'model' => 'VeganProduct', 'active' => false],
                'confectionery' => ['name' => 'Confectionery', 'model' => 'BakeryOrder', 'active' => true],
                'grocery_and_delivery' => ['name' => 'Grocery & Delivery', 'model' => 'GroceryProduct', 'active' => true],
                'food' => ['name' => 'Food (semi-finished)', 'model' => 'FoodItem', 'active' => true],
                'office_catering' => ['name' => 'Office Catering', 'model' => 'CateringCompany', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 2. BEAUTY & PERSONAL CARE
        // ══════════════════════════════════════════════════════
        'beauty_and_personal_care' => [
            'name' => 'Beauty & Personal Care',
            'type' => 'service',
            'active' => true,
            'description' => 'Салоны красоты, косметология',
            'sub_verticals' => [
                'beauty' => ['name' => 'Beauty', 'model' => 'Appointment', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 3. PHARMACY (отдельная вертикаль - медицинский compliance)
        // ══════════════════════════════════════════════════════
        'pharmacy' => [
            'name' => 'Pharmacy',
            'type' => 'product',
            'active' => true,
            'description' => 'Аптеки, лекарственные средства, медтовары',
            'sub_verticals' => [
                'pharmacy' => ['name' => 'Pharmacy', 'model' => 'Pharmacy', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
            'compliance' => ['152-ФЗ', 'ФЗ-323', 'Лицензирование'],
        ],

        // ══════════════════════════════════════════════════════
        // 4. FASHION & LUXURY
        // ══════════════════════════════════════════════════════
        'fashion_and_luxury' => [
            'name' => 'Fashion & Luxury',
            'type' => 'product',
            'active' => true,
            'description' => 'Одежда, аксессуары, люкс, ювелирка, искусство',
            'sub_verticals' => [
                'fashion' => ['name' => 'Fashion', 'model' => 'B2BFashionOrder', 'active' => true],
                'luxury' => ['name' => 'Luxury', 'model' => 'LuxuryBrand', 'active' => false],
                'jewelry' => ['name' => 'Jewelry & Collectibles', 'model' => 'CollectibleItem', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 5. HOME & LIVING
        // ══════════════════════════════════════════════════════
        'home_and_living' => [
            'name' => 'Home & Living',
            'type' => 'mixed',
            'active' => true,
            'description' => 'Мебель, товары для дома, сад, бытовые услуги',
            'sub_verticals' => [
                'furniture' => ['name' => 'Furniture', 'model' => 'FurnitureItem', 'active' => true],
                'household_goods' => ['name' => 'Household Goods', 'model' => 'HouseholdProduct', 'active' => false],
                'gardening' => ['name' => 'Gardening', 'model' => 'GardeningModel', 'active' => false],
                'home_services' => ['name' => 'Home Services (B2C)', 'model' => 'B2BHomeServiceOrder', 'active' => true],
                'cleaning_services' => ['name' => 'Cleaning Services (B2C)', 'model' => 'CleaningOrder', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 6. ELECTRONICS & GADGETS
        // ══════════════════════════════════════════════════════
        'electronics_and_gadgets' => [
            'name' => 'Electronics & Gadgets',
            'type' => 'product',
            'active' => true,
            'description' => 'Электроника, фото',
            'sub_verticals' => [
                'electronics' => ['name' => 'Electronics', 'model' => 'ElectronicOrder', 'active' => false],
                'photography' => ['name' => 'Photography', 'model' => 'B2BPhotoOrder', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 7. AUTO & MOBILITY
        // ══════════════════════════════════════════════════════
        'auto_and_mobility' => [
            'name' => 'Auto & Mobility',
            'type' => 'mixed',
            'active' => true,
            'description' => 'Автомобили, аренда, такси',
            'sub_verticals' => [
                'auto' => ['name' => 'Auto', 'model' => 'AutoCatalogBrand', 'active' => true],
                'car_rental' => ['name' => 'Car Rental', 'model' => 'RentalBooking', 'active' => true],
                'taxi' => ['name' => 'Taxi', 'model' => 'DeliveryOrder', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 8. HEALTH & SPORTS
        // ══════════════════════════════════════════════════════
        'health_and_sports' => [
            'name' => 'Health & Sports',
            'type' => 'service',
            'active' => true,
            'description' => 'Фитнес, спортивное питание, спорт',
            'sub_verticals' => [
                'fitness' => ['name' => 'Fitness', 'model' => 'Gym', 'active' => true],
                'sports_nutrition' => ['name' => 'Sports Nutrition', 'model' => 'SportsNutritionModel', 'active' => false],
                'sports' => ['name' => 'Sports', 'model' => 'B2BModel', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 9. FOOD SERVICES & CATERING
        // ══════════════════════════════════════════════════════
        'food_services_and_catering' => [
            'name' => 'Food Services & Catering',
            'type' => 'service',
            'active' => true,
            'description' => 'Рестораны, готовка на вынос, кейтеринг',
            'sub_verticals' => [
                'restaurants' => ['name' => 'Restaurants', 'model' => 'RestaurantOrder', 'active' => true],
                'catering' => ['name' => 'Catering', 'model' => 'CateringOrder', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 10. TRAVEL & HOSPITALITY
        // ══════════════════════════════════════════════════════
        'travel_and_hospitality' => [
            'name' => 'Travel & Hospitality',
            'type' => 'service',
            'active' => true,
            'description' => 'Отели, краткосрочная аренда, путешествия',
            'sub_verticals' => [
                'hotels' => ['name' => 'Hotels', 'model' => 'Hotel', 'active' => true],
                'short_term_rentals' => ['name' => 'Short Term Rentals', 'model' => 'Apartment', 'active' => true],
                'travel' => ['name' => 'Travel', 'model' => 'B2BTravelOrder', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 11. LEISURE & ENTERTAINMENT (Досуг и Развлечения)
        // ══════════════════════════════════════════════════════
        'leisure_and_entertainment' => [
            'name' => 'Leisure & Entertainment',
            'type' => 'service',
            'active' => true,
            'description' => 'Билетная касса, мероприятия, концерты, кино, выставки, тусовки',
            'sub_verticals' => [
                'tickets' => ['name' => 'Tickets (билетная касса)', 'model' => 'Ticket', 'active' => true],
                'concerts' => ['name' => 'Concerts', 'model' => 'Concert', 'active' => false],
                'cinema' => ['name' => 'Cinema', 'model' => 'CinemaTicket', 'active' => false],
                'exhibitions' => ['name' => 'Exhibitions', 'model' => 'Exhibition', 'active' => false],
                'parties' => ['name' => 'Parties & Тусовки', 'model' => 'PartyEvent', 'active' => false],
                'leisure' => ['name' => 'Leisure Activities', 'model' => 'LeisureActivity', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 12. REAL ESTATE & PROPERTY
        // ══════════════════════════════════════════════════════
        'real_estate_and_property' => [
            'name' => 'Real Estate & Property',
            'type' => 'service',
            'active' => true,
            'description' => 'Недвижимость, строительство, ремонт',
            'sub_verticals' => [
                'real_estate' => ['name' => 'Real Estate', 'model' => 'B2BDeal', 'active' => true],
                'construction_and_repair' => ['name' => 'Construction & Repair', 'model' => 'ConstructionProject', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 13. SERVICES & FREELANCE (B2C)
        // ══════════════════════════════════════════════════════
        'services_and_freelance' => [
            'name' => 'Services & Freelance (B2C)',
            'type' => 'service',
            'active' => true,
            'description' => 'Фриланс, услуги для физлиц',
            'sub_verticals' => [
                'freelance' => ['name' => 'Freelance', 'model' => 'FreelanceContract', 'active' => false],
                'consulting' => ['name' => 'Consulting', 'model' => 'Consultant', 'active' => false],
            ],
            'uses_common' => ['Fraud'],
        ],

        // ══════════════════════════════════════════════════════
        // 14. ART & COLLECTIBLES
        // ══════════════════════════════════════════════════════
        'art_and_collectibles' => [
            'name' => 'Art & Collectibles',
            'type' => 'product',
            'active' => true,
            'description' => 'Искусство, коллекционные предметы',
            'sub_verticals' => [
                'art' => ['name' => 'Art', 'model' => 'Artist', 'active' => false],
                'collectibles' => ['name' => 'Collectibles', 'model' => 'CollectibleItem', 'active' => false],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],

        // ══════════════════════════════════════════════════════
        // 15. FLOWERS & GIFTS
        // ══════════════════════════════════════════════════════
        'flowers_and_gifts' => [
            'name' => 'Flowers & Gifts',
            'type' => 'product',
            'active' => true,
            'description' => 'Цветы, сезонные кластеры, подарки',
            'sub_verticals' => [
                'flowers' => ['name' => 'Flowers', 'model' => 'B2BFlowerOrder', 'active' => true],
            ],
            'uses_common' => ['Inventory', 'Fraud', 'Cart'],
        ],
    ],

    // ══════════════════════════════════════════════════════
    // ТЕХНИЧЕСКИЕ ДОМЕНЫ (отдельно от продуктовых)
    // ══════════════════════════════════════════════════════
    'technical_domains' => [
        'delivery' => ['domain' => 'Delivery', 'model' => 'DeliveryOrder', 'active' => true],
        'geo_logistics' => ['domain' => 'GeoLogistics', 'model' => 'LogisticsRoute', 'active' => true],
        'cart' => ['domain' => 'Cart', 'model' => 'Cart', 'active' => true],
        'inventory' => ['domain' => 'Inventory', 'model' => 'InventoryCheck', 'active' => true],
        'payment' => ['domain' => 'Payment', 'model' => 'PaymentRecord', 'active' => true],
        'wallet' => ['domain' => 'Wallet', 'model' => 'Wallet', 'active' => true],
        'fraud_detection' => ['domain' => 'FraudDetection', 'model' => 'FraudCase', 'active' => true],
        'analytics' => ['domain' => 'Analytics', 'model' => 'AnalyticsEvent', 'active' => true],
        'recommendation' => ['domain' => 'Recommendation', 'model' => 'Recommendation', 'active' => true],
        'ai_constructor' => ['domain' => 'AIConstructor', 'model' => 'AIConstructor', 'active' => true],
        'communication' => ['domain' => 'Communication', 'model' => 'CommunicationLog', 'active' => true],
        'notifications' => ['domain' => 'Notifications', 'model' => 'Notification', 'active' => true],
        'audit' => ['domain' => 'Audit', 'model' => 'AuditLog', 'active' => true],
        'security' => ['domain' => 'Security', 'model' => 'SecurityEvent', 'active' => true],
        'compliance' => ['domain' => 'Compliance', 'model' => 'ComplianceRecord', 'active' => true],
        'marketplace' => ['domain' => 'Marketplace', 'model' => 'MarketplaceListing', 'active' => true],
        'b2b' => ['domain' => 'B2B', 'model' => 'BusinessGroup', 'active' => true],
        'crm' => ['domain' => 'CRM', 'model' => 'CrmLead', 'active' => true],
        'loyalty' => ['domain' => 'Loyalty', 'model' => 'LoyaltyProgram', 'active' => true],
        'bonuses' => ['domain' => 'Bonuses', 'model' => 'BonusTransaction', 'active' => true],
        'commissions' => ['domain' => 'Commissions', 'model' => 'CommissionRule', 'active' => true],
        'demand_forecast' => ['domain' => 'DemandForecast', 'model' => 'DemandForecast', 'active' => true],
        'promo_campaigns' => ['domain' => 'PromoCampaigns', 'model' => 'PromoCampaign', 'active' => true],
        'media' => ['domain' => 'Media', 'model' => 'MediaUpload', 'active' => true],
        'video' => ['domain' => 'Video', 'model' => 'VideoRoom', 'active' => true],
        'geo' => ['domain' => 'Geo', 'model' => 'GeoLocation', 'active' => true],
        'search' => ['domain' => 'Search', 'model' => 'SearchIndex', 'active' => true],
        'realtime' => ['domain' => 'Realtime', 'model' => 'RealtimeChannel', 'active' => true],
        'webhooks' => ['domain' => 'Webhooks', 'model' => 'WebhookEndpoint', 'active' => true],
        'user_profile' => ['domain' => 'UserProfile', 'model' => 'UserAddress', 'active' => true],
        'staff' => ['domain' => 'Staff', 'model' => 'StaffMember', 'active' => true],
        'hr' => ['domain' => 'HR', 'model' => 'Employee', 'active' => true],
        'finances' => ['domain' => 'Finances', 'model' => 'FinanceRecord', 'active' => true],
        'payout' => ['domain' => 'Payout', 'model' => 'PayoutRecord', 'active' => true],
        'referral' => ['domain' => 'Referral', 'model' => 'Referral', 'active' => true],
        'ml' => ['domain' => 'ML', 'model' => 'UserTasteProfile', 'active' => true],
        'big_data' => ['domain' => 'BigData', 'model' => 'AnalyticsEvent', 'active' => true],
        'advertising' => ['domain' => 'Advertising', 'model' => 'AdCampaign', 'active' => true],
    ],

    // ══════════════════════════════════════════════════════
    // ИСКЛЮЧЁННЫЕ ВЕРТИКАЛИ
    // ══════════════════════════════════════════════════════
    'excluded_verticals' => [
        'medical' => ['reason' => 'Medical compliance (152-ФЗ, ФЗ-323) - separate domain'],
        'veterinary' => ['reason' => 'Veterinary compliance - separate domain'],
        'dental' => ['reason' => 'Dental compliance - part of Medical domain'],
        'pet' => ['reason' => 'Pet services - part of Veterinary domain'],
        'vet_grooming' => ['reason' => 'Vet grooming - part of Veterinary domain'],
        'education_and_development' => ['reason' => 'Removed from system - not in scope'],
        'books_and_literature' => ['reason' => 'Removed from system - not in scope'],
        'music_and_instruments' => ['reason' => 'Removed from system - not in scope'],
    ],
];
