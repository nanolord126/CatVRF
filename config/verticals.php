<?php declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════
 * CATVRF 2026 — РЕЕСТР ВЕРТИКАЛЕЙ
 * ═══════════════════════════════════════════════════════════════
 *
 * queue: 1 — техническая инфраструктура (PRIORITY, разрабатывается)
 * queue: 2 — основные бизнес-вертикали (начинать после Q1)
 * queue: 3 — все остальные (ЗАБЛОКИРОВАНО, active => false)
 *
 * Подробный roadmap: config/domain_queues.php
 * ═══════════════════════════════════════════════════════════════
 */

return [
    'verticals' => [

        // ══════════════════════════════════════════════════════
        // ОЧЕРЕДЬ 1 — Технические инфраструктурные домены
        // ══════════════════════════════════════════════════════
        'a_i'            => ['domain' => 'AI',             'model' => 'AIModel',          'queue' => 1, 'active' => true],
        'advertising'    => ['domain' => 'Advertising',    'model' => 'AdCampaign',        'queue' => 1, 'active' => true],
        'analytics'      => ['domain' => 'Analytics',      'model' => 'AnalyticsEvent',    'queue' => 1, 'active' => true],
        'common'         => ['domain' => 'Common',         'model' => 'CommonEntity',      'queue' => 1, 'active' => true],
        'communication'  => ['domain' => 'Communication',  'model' => 'CommunicationLog',  'queue' => 1, 'active' => true],
        'demand_forecast'=> ['domain' => 'DemandForecast', 'model' => 'DemandForecast',    'queue' => 1, 'active' => true],
        'finances'       => ['domain' => 'Finances',       'model' => 'FinanceRecord',     'queue' => 1, 'active' => true],
        'fraud_m_l'      => ['domain' => 'FraudML',        'model' => 'FraudModel',        'queue' => 1, 'active' => true],
        'geo'            => ['domain' => 'Geo',            'model' => 'GeoLocation',       'queue' => 1, 'active' => true],
        'geo_logistics'  => ['domain' => 'GeoLogistics',   'model' => 'LogisticsRoute',    'queue' => 1, 'active' => true],
        'inventory'      => ['domain' => 'Inventory',      'model' => 'InventoryCheck',    'queue' => 1, 'active' => true],
        'marketplace'    => ['domain' => 'Marketplace',    'model' => 'MarketplaceListing','queue' => 1, 'active' => true],
        'payment'        => ['domain' => 'Payment',        'model' => 'PaymentRecord',     'queue' => 1, 'active' => true],
        'recommendation' => ['domain' => 'Recommendation', 'model' => 'Recommendation',    'queue' => 1, 'active' => true],
        'referral'       => ['domain' => 'Referral',       'model' => 'Referral',          'queue' => 1, 'active' => true],
        'staff'          => ['domain' => 'Staff',          'model' => 'StaffMember',       'queue' => 1, 'active' => true],
        'm_l'            => ['domain' => 'ML',              'model' => 'UserTasteProfile',  'queue' => 1, 'active' => true],
        'big_data'       => ['domain' => 'BigData',         'model' => 'AnalyticsEvent',    'queue' => 1, 'active' => true],
        'h_r'            => ['domain' => 'HR',              'model' => 'Employee',          'queue' => 1, 'active' => true],
        'search'         => ['domain' => 'Search',          'model' => 'SearchIndex',       'queue' => 1, 'active' => true],
        'c_r_m'          => ['domain' => 'CRM',             'model' => 'CrmLead',           'queue' => 1, 'active' => true],
        'bonuses'        => ['domain' => 'Bonuses',         'model' => 'BonusTransaction',  'queue' => 1, 'active' => true],
        'commissions'    => ['domain' => 'Commissions',     'model' => 'CommissionRule',    'queue' => 1, 'active' => true],
        'payout'         => ['domain' => 'Payout',          'model' => 'PayoutRecord',      'queue' => 1, 'active' => true],
        'audit'          => ['domain' => 'Audit',           'model' => 'AuditLog',          'queue' => 1, 'active' => true],
        'security'       => ['domain' => 'Security',        'model' => 'SecurityEvent',     'queue' => 1, 'active' => true],
        'notifications'  => ['domain' => 'Notifications',   'model' => 'Notification',      'queue' => 1, 'active' => true],
        'cart'           => ['domain' => 'Cart',            'model' => 'Cart',              'queue' => 1, 'active' => true],
        'b2b'            => ['domain' => 'B2B',             'model' => 'BusinessGroup',     'queue' => 1, 'active' => true],
        'webhooks'       => ['domain' => 'Webhooks',        'model' => 'WebhookEndpoint',   'queue' => 1, 'active' => true],
        'realtime'       => ['domain' => 'Realtime',        'model' => 'RealtimeChannel',   'queue' => 1, 'active' => true],
        'user_profile'   => ['domain' => 'UserProfile',     'model' => 'UserAddress',       'queue' => 1, 'active' => true],
        'compliance'     => ['domain' => 'Compliance',      'model' => 'ComplianceRecord',  'queue' => 1, 'active' => true],
        'wallet'         => ['domain' => 'Wallet',          'model' => 'Wallet',            'queue' => 1, 'active' => true],

        // ══════════════════════════════════════════════════════
        // ОЧЕРЕДЬ 2 — Основные бизнес-вертикали
        // ══════════════════════════════════════════════════════
        'auto'                    => ['domain' => 'Auto',                 'model' => 'AutoCatalogBrand',       'queue' => 2, 'active' => true],
        'beauty'                  => ['domain' => 'Beauty',               'model' => 'Appointment',            'queue' => 2, 'active' => true],
        'car_rental'              => ['domain' => 'CarRental',            'model' => 'RentalBooking',          'queue' => 2, 'active' => true],
        'cleaning_services'       => ['domain' => 'CleaningServices',     'model' => 'CleaningOrder',          'queue' => 2, 'active' => true],
        'confectionery'           => ['domain' => 'Confectionery',        'model' => 'BakeryOrder',            'queue' => 2, 'active' => true],
        'construction_and_repair' => ['domain' => 'ConstructionAndRepair','model' => 'ConstructionProject',    'queue' => 2, 'active' => true],
        'delivery'                => ['domain' => 'Delivery',             'model' => 'DeliveryOrder',          'queue' => 2, 'active' => true],
        'fashion'                 => ['domain' => 'Fashion',              'model' => 'B2BFashionOrder',        'queue' => 2, 'active' => true],
        'fitness'                 => ['domain' => 'Fitness',              'model' => 'Gym',                    'queue' => 2, 'active' => true],
        'flowers'                 => ['domain' => 'Flowers',              'model' => 'B2BFlowerOrder',         'queue' => 2, 'active' => true],
        'food'                    => ['domain' => 'Food',                 'model' => 'FoodItem',               'queue' => 2, 'active' => true],
        'furniture'               => ['domain' => 'Furniture',            'model' => 'FurnitureItem',          'queue' => 2, 'active' => true],
        'grocery_and_delivery'    => ['domain' => 'GroceryAndDelivery',   'model' => 'GroceryProduct',         'queue' => 2, 'active' => true],
        'home_services'           => ['domain' => 'HomeServices',         'model' => 'B2BHomeServiceOrder',    'queue' => 2, 'active' => true],
        'hotels'                  => ['domain' => 'Hotels',               'model' => 'Hotel',                  'queue' => 2, 'active' => true],
        'meat_shops'              => ['domain' => 'MeatShops',            'model' => 'MeatShop',               'queue' => 2, 'active' => true],
        'medical'                 => ['domain' => 'Medical',              'model' => 'Appointment',            'queue' => 2, 'active' => true],
        'pharmacy'                => ['domain' => 'Pharmacy',             'model' => 'Pharmacy',               'queue' => 2, 'active' => true],
        'promo_campaigns'         => ['domain' => 'PromoCampaigns',       'model' => 'PromoCampaign',          'queue' => 2, 'active' => true],
        'real_estate'             => ['domain' => 'RealEstate',           'model' => 'B2BDeal',                'queue' => 2, 'active' => true],
        'short_term_rentals'      => ['domain' => 'ShortTermRentals',     'model' => 'Apartment',              'queue' => 2, 'active' => true],
        'taxi'                    => ['domain' => 'Taxi',                 'model' => 'DeliveryOrder',          'queue' => 2, 'active' => true],

        // ══════════════════════════════════════════════════════
        // ОЧЕРЕДЬ 3 — Все остальные (ЗАБЛОКИРОВАНО)
        // active => false : не участвуют в регистрации, роутах
        // и динамических сервисах до завершения Q1 + Q2
        // ══════════════════════════════════════════════════════
        'art'                  => ['domain' => 'Art',               'model' => 'Artist',               'queue' => 3, 'active' => false],
        'books_and_literature' => ['domain' => 'BooksAndLiterature','model' => 'Book',                 'queue' => 3, 'active' => false],
        'collectibles'         => ['domain' => 'Collectibles',      'model' => 'CollectibleItem',      'queue' => 3, 'active' => false],
        'consulting'           => ['domain' => 'Consulting',        'model' => 'Consultant',           'queue' => 3, 'active' => false],
        'content'              => ['domain' => 'Content',           'model' => 'ContentItem',          'queue' => 3, 'active' => false],
        'education'            => ['domain' => 'Education',         'model' => 'CorporateContract',    'queue' => 3, 'active' => false],
        'electronics'          => ['domain' => 'Electronics',       'model' => 'ElectronicOrder',      'queue' => 3, 'active' => false],
        'event_planning'       => ['domain' => 'EventPlanning',     'model' => 'Event',                'queue' => 3, 'active' => false],
        'farm_direct'          => ['domain' => 'FarmDirect',        'model' => 'Farm',                 'queue' => 3, 'active' => false],
        'freelance'            => ['domain' => 'Freelance',         'model' => 'FreelanceContract',    'queue' => 3, 'active' => false],
        'gardening'            => ['domain' => 'Gardening',         'model' => 'GardeningModels',      'queue' => 3, 'active' => false],
        'hobby_and_craft'      => ['domain' => 'HobbyAndCraft',     'model' => 'CraftItem',            'queue' => 3, 'active' => false],
        'household_goods'      => ['domain' => 'HouseholdGoods',    'model' => 'HouseholdProduct',     'queue' => 3, 'active' => false],
        'insurance'            => ['domain' => 'Insurance',         'model' => 'InsuranceCompany',     'queue' => 3, 'active' => false],
        'legal'                => ['domain' => 'Legal',             'model' => 'Lawyer',               'queue' => 3, 'active' => false],
        'logistics'            => ['domain' => 'Logistics',         'model' => 'B2BLogisticsOrder',    'queue' => 3, 'active' => false],
        'luxury'               => ['domain' => 'Luxury',            'model' => 'LuxuryBrand',          'queue' => 3, 'active' => false],
        'music_and_instruments'=> ['domain' => 'MusicAndInstruments','model' => 'MusicInstrument',     'queue' => 3, 'active' => false],
        'office_catering'      => ['domain' => 'OfficeCatering',    'model' => 'CateringCompany',      'queue' => 3, 'active' => false],
        'party_supplies'       => ['domain' => 'PartySupplies',     'model' => 'PartyOrder',           'queue' => 3, 'active' => false],
        'personal_development' => ['domain' => 'PersonalDevelopment','model' => 'Coach',               'queue' => 3, 'active' => false],
        'pet'                  => ['domain' => 'Pet',               'model' => 'Pet',                  'queue' => 3, 'active' => false],
        'photography'          => ['domain' => 'Photography',       'model' => 'B2BPhotoOrder',        'queue' => 3, 'active' => false],
        'sports'               => ['domain' => 'Sports',            'model' => 'B2BModels',            'queue' => 3, 'active' => false],
        'sports_nutrition'     => ['domain' => 'SportsNutrition',   'model' => 'SportsNutritionModels','queue' => 3, 'active' => false],
        'tickets'              => ['domain' => 'Tickets',           'model' => 'Ticket',               'queue' => 3, 'active' => false],
        'toys_and_games'       => ['domain' => 'ToysAndGames',      'model' => 'ToyProduct',           'queue' => 3, 'active' => false],
        'travel'               => ['domain' => 'Travel',            'model' => 'B2BTravelOrder',       'queue' => 3, 'active' => false],
        'vegan_products'       => ['domain' => 'VeganProducts',     'model' => 'VeganModels',          'queue' => 3, 'active' => false],
        'veterinary'           => ['domain' => 'Veterinary',        'model' => 'MedicalRecord',        'queue' => 3, 'active' => false],
        'wedding_planning'     => ['domain' => 'WeddingPlanning',   'model' => 'WeddingBooking',       'queue' => 3, 'active' => false],
    ],
];
