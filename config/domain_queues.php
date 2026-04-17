<?php

declare(strict_types=1);

/**
 * ═══════════════════════════════════════════════════════════════
 * CATVRF 2026 — ROADMAP ОЧЕРЕДЕЙ РЕАЛИЗАЦИИ
 * ═══════════════════════════════════════════════════════════════
 *
 * ПРАВИЛО: нельзя приступать к следующей очереди, пока предыдущая
 * не реализована и не покрыта тестами.
 *
 * Q3 домены закомментированы во всех конфигах, роутах и провайдерах
 * до момента завершения Q1 + Q2.
 *
 * ═══════════════════════════════════════════════════════════════
 * ОЧЕРЕДЬ 1 — Технические/инфраструктурные домены
 * ═══════════════════════════════════════════════════════════════
 *
 * Что входит: кошелёк, платёж, баланс, финансы, инвентарь,
 * аналитика, ML/Fraud, гео, рекомендации, маркетплейс (ядро),
 * реферальная программа, персонал, AI-инфраструктура.
 *
 * ═══════════════════════════════════════════════════════════════
 * ОЧЕРЕДЬ 2 — Основные бизнес-вертикали
 * ═══════════════════════════════════════════════════════════════
 *
 * Что входит: бьюти, гостиницы, цветы, фуд, одежда/обувь,
 * доставка, машины/СТО, аренда жилья и продажа, курьерская
 * служба, мебель, фитнес, медицина, аптеки, ремонт,
 * уборка, услуги на дому.
 *
 * ═══════════════════════════════════════════════════════════════
 * ОЧЕРЕДЬ 3 — Все остальные вертикали (ЗАБЛОКИРОВАНЫ)
 * ═══════════════════════════════════════════════════════════════
 *
 * Что входит: всё остальное. НЕ ТРОГАТЬ до завершения Q1+Q2.
 * Роуты закомментированы. active => false в verticals.php.
 *
 * ═══════════════════════════════════════════════════════════════
 */

return [

    // ──────────────────────────────────────────────────────────────
    // ОЧЕРЕДЬ 1 — Технические инфраструктурные домены
    // Статус: В РАЗРАБОТКЕ (PRIORITY 1)
    // ──────────────────────────────────────────────────────────────
    'q1' => [
        'label'       => 'Техническая инфраструктура (Q1)',
        'status'      => 'active',
        'description' => 'Wallet, Payment, Finances, Bonuses, Commissions, Payout, Inventory, Analytics, AI, ML, BigData, FraudML, Fraud, Geo, Recommendation, HR, Search, CRM, Audit, Security, Notifications, Cart, B2B, Webhooks, Realtime, UserProfile, Compliance, Marketplace core',
        'domains'     => [
            'Wallet'          => ['key' => 'wallet',         'desc' => 'Кошелёк и баланс'],
            'Payment'         => ['key' => 'payment',        'desc' => 'Платежи и шлюзы'],
            'Finances'        => ['key' => 'finances',       'desc' => 'Финансы, транзакции, комиссии'],
            'Inventory'       => ['key' => 'inventory',      'desc' => 'Инвентарь и остатки'],
            'Analytics'       => ['key' => 'analytics',      'desc' => 'Аналитика и статистика'],
            'AI'              => ['key' => 'a_i',            'desc' => 'AI-конструкторы (инфраструктура)'],
            'FraudML'         => ['key' => 'fraud_m_l',      'desc' => 'ML-антифрод'],
            'Geo'             => ['key' => 'geo',            'desc' => 'Геолокация'],
            'GeoLogistics'    => ['key' => 'geo_logistics',  'desc' => 'Гео-логистика и маршруты'],
            'Recommendation'  => ['key' => 'recommendation', 'desc' => 'Система рекомендаций'],
            'DemandForecast'  => ['key' => 'demand_forecast','desc' => 'Прогнозирование спроса'],
            'Referral'        => ['key' => 'referral',       'desc' => 'Реферальная программа'],
            'Staff'           => ['key' => 'staff',          'desc' => 'Персонал и кадровый учёт'],
            'ML'              => ['key' => 'm_l',            'desc' => 'ML-инфраструктура (UserTaste, Clustering, ColdStart)', 'status' => 'completed'],
            'BigData'         => ['key' => 'big_data',       'desc' => 'ClickHouse / Big Data агрегация', 'status' => 'completed'],
            'HR'              => ['key' => 'h_r',            'desc' => 'HR, найм, зарплаты, расписания'],
            'Search'          => ['key' => 'search',         'desc' => 'Поиск (LiveSearch, SearchRanking, Scout)', 'status' => 'completed'],
            'CRM'             => ['key' => 'c_r_m',          'desc' => 'CRM: клиенты, лиды, воронки'],
            'Bonuses'         => ['key' => 'bonuses',        'desc' => 'Бонусная система (AwardBonus, ConsumeBonus)', 'status' => 'completed'],
            'Commissions'     => ['key' => 'commissions',    'desc' => 'Комиссии платформы (CommissionRule, CommissionTransaction)', 'status' => 'completed'],
            'Payout'          => ['key' => 'payout',         'desc' => 'Выплаты (PayoutService, MassPayoutService)', 'status' => 'completed'],
            'Audit'           => ['key' => 'audit',          'desc' => 'Аудит всех мутаций (AuditService, AuditLog + ClickHouse)', 'status' => 'completed'],
            'Security'        => ['key' => 'security',       'desc' => 'Безопасность (RateLimiter, ApiKeys, Idempotency, SecurityMonitoring)', 'status' => 'completed'],
            'Notifications'   => ['key' => 'notifications',  'desc' => 'Уведомления (Email, Push, SMS, Telegram, In-app)', 'status' => 'completed'],
            'Cart'            => ['key' => 'cart',           'desc' => 'Корзина (CartService, резервы, 20 корзин, 20 мин)', 'status' => 'completed'],
            'B2B'             => ['key' => 'b2b',            'desc' => 'B2B-инфраструктура (ApiKey, BusinessGroup, кредит, отсрочка)', 'status' => 'completed'],
            'Webhooks'        => ['key' => 'webhooks',       'desc' => 'Webhooks (WebhookManagementService, SignatureValidator)'],
            'Realtime'        => ['key' => 'realtime',       'desc' => 'WebSocket / Realtime (Echo, RealtimeService, Chat)', 'status' => 'completed'],
            'UserProfile'     => ['key' => 'user_profile',   'desc' => 'Профиль пользователя (UserAddress, UserActivity, Wishlist)'],
            'Compliance'      => ['key' => 'compliance',     'desc' => 'Комплаенс (MDLP, Mercury, ComplianceRequirement)', 'status' => 'completed'],
            'Marketplace'     => ['key' => 'marketplace',    'desc' => 'Ядро маркетплейса'],
            'Advertising'     => ['key' => 'advertising',    'desc' => 'Рекламный движок (инфраструктура)'],
            'Common'          => ['key' => 'common',         'desc' => 'Общие утилиты'],
            'Communication'   => ['key' => 'communication',  'desc' => 'Коммуникации и уведомления'],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // ОЧЕРЕДЬ 2 — Основные бизнес-вертикали
    // Статус: НАЧИНАТЬ ПОСЛЕ ЗАВЕРШЕНИЯ Q1
    // ──────────────────────────────────────────────────────────────
    'q2' => [
        'label'       => 'Основные бизнес-вертикали (Q2)',
        'status'      => 'pending_q1',
        'description' => 'Beauty, Hotels, Flowers, Food, Fashion, Delivery, Auto, RealEstate, ShortTermRentals, Taxi и другие услуги',
        'domains'     => [
            'Beauty'              => ['key' => 'beauty',               'desc' => 'Бьюти-сервисы, салоны, мастера'],
            'Hotels'              => ['key' => 'hotels',               'desc' => 'Гостиницы и отели'],
            'Flowers'             => ['key' => 'flowers',              'desc' => 'Цветочные магазины и букеты'],
            'Food'                => ['key' => 'food',                 'desc' => 'Рестораны, кафе, доставка еды'],
            'Fashion'             => ['key' => 'fashion',              'desc' => 'Одежда, обувь, аксессуары'],
            'Delivery'            => ['key' => 'delivery',             'desc' => 'Доставка и курьерская служба'],
            'GroceryAndDelivery'  => ['key' => 'grocery_and_delivery', 'desc' => 'Доставка продуктов'],
            'Auto'                => ['key' => 'auto',                 'desc' => 'Автомобили, СТО, запчасти'],
            'CarRental'           => ['key' => 'car_rental',           'desc' => 'Аренда автомобилей'],
            'RealEstate'          => ['key' => 'real_estate',          'desc' => 'Аренда и продажа жилья'],
            'ShortTermRentals'    => ['key' => 'short_term_rentals',   'desc' => 'Краткосрочная аренда жилья'],
            'Taxi'                => ['key' => 'taxi',                 'desc' => 'Такси и курьерская служба'],
            'Furniture'           => ['key' => 'furniture',            'desc' => 'Мебель и интерьер'],
            'Fitness'             => ['key' => 'fitness',              'desc' => 'Фитнес и спортзалы'],
            'Medical'             => ['key' => 'medical',              'desc' => 'Медицина и здоровье'],
            'Pharmacy'            => ['key' => 'pharmacy',             'desc' => 'Аптеки'],
            'CleaningServices'    => ['key' => 'cleaning_services',    'desc' => 'Клининг и уборка'],
            'HomeServices'        => ['key' => 'home_services',        'desc' => 'Услуги на дому'],
            'ConstructionAndRepair' => ['key' => 'construction_and_repair', 'desc' => 'Ремонт и строительство'],
            'Confectionery'       => ['key' => 'confectionery',        'desc' => 'Кондитерские и выпечка'],
            'MeatShops'           => ['key' => 'meat_shops',           'desc' => 'Мясные магазины'],
            'PromoCampaigns'      => ['key' => 'promo_campaigns',      'desc' => 'Промо-акции и маркетинг'],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // ОЧЕРЕДЬ 3 — Все остальные вертикали
    // Статус: ЗАБЛОКИРОВАНО — ждёт завершения Q1 + Q2
    // ВАЖНО: роуты закомментированы, active => false
    // ──────────────────────────────────────────────────────────────
    'q3' => [
        'label'       => 'Расширенные вертикали (Q3)',
        'status'      => 'blocked',
        'description' => 'Все остальные вертикали. НЕ РАЗРАБАТЫВАТЬ до закрытия Q1+Q2',
        'domains'     => [
            'Art'               => ['key' => 'art',                 'desc' => 'Искусство и творчество'],
            'BooksAndLiterature'=> ['key' => 'books_and_literature','desc' => 'Книги и литература'],
            'Collectibles'      => ['key' => 'collectibles',        'desc' => 'Коллекционные товары'],
            'Consulting'        => ['key' => 'consulting',          'desc' => 'Консалтинг'],
            'Content'           => ['key' => 'content',             'desc' => 'Контент и медиа'],
            'Education'         => ['key' => 'education',           'desc' => 'Образование и курсы'],
            'Electronics'       => ['key' => 'electronics',         'desc' => 'Электроника'],
            'EventPlanning'     => ['key' => 'event_planning',      'desc' => 'Организация мероприятий'],
            'FarmDirect'        => ['key' => 'farm_direct',         'desc' => 'Фермерские продукты напрямую'],
            'Freelance'         => ['key' => 'freelance',           'desc' => 'Фриланс и удалённая работа'],
            'Gardening'         => ['key' => 'gardening',           'desc' => 'Садоводство'],
            'HobbyAndCraft'     => ['key' => 'hobby_and_craft',     'desc' => 'Хобби и рукоделие'],
            'HouseholdGoods'    => ['key' => 'household_goods',     'desc' => 'Товары для дома'],
            'Insurance'         => ['key' => 'insurance',           'desc' => 'Страхование'],
            'Legal'             => ['key' => 'legal',               'desc' => 'Юридические услуги'],
            'Logistics'         => ['key' => 'logistics',           'desc' => 'B2B логистика'],
            'Luxury'            => ['key' => 'luxury',              'desc' => 'Люкс-товары'],
            'MusicAndInstruments' => ['key' => 'music_and_instruments', 'desc' => 'Музыка и инструменты'],
            'OfficeCatering'    => ['key' => 'office_catering',     'desc' => 'Офисный кейтеринг'],
            'PartySupplies'     => ['key' => 'party_supplies',      'desc' => 'Товары для праздников'],
            'PersonalDevelopment' => ['key' => 'personal_development', 'desc' => 'Личностный рост'],
            'Pet'               => ['key' => 'pet',                 'desc' => 'Товары и услуги для животных'],
            'Photography'       => ['key' => 'photography',         'desc' => 'Фотография и видео'],
            'Sports'            => ['key' => 'sports',              'desc' => 'Спорт и активный отдых'],
            'SportsNutrition'   => ['key' => 'sports_nutrition',    'desc' => 'Спортивное питание'],
            'Tickets'           => ['key' => 'tickets',             'desc' => 'Билеты на мероприятия'],
            'ToysAndGames'      => ['key' => 'toys_and_games',      'desc' => 'Игрушки и игры'],
            'Travel'            => ['key' => 'travel',              'desc' => 'Туризм и путешествия'],
            'VeganProducts'     => ['key' => 'vegan_products',      'desc' => 'Веганские продукты'],
            'Veterinary'        => ['key' => 'veterinary',          'desc' => 'Ветеринария'],
            'WeddingPlanning'   => ['key' => 'wedding_planning',    'desc' => 'Организация свадеб'],
        ],
    ],

    // ──────────────────────────────────────────────────────────────
    // Вспомогательные методы (helper для use в коде)
    // ──────────────────────────────────────────────────────────────

    /**
     * Использование в коде:
     *
     *   // Проверить, активна ли вертикаль:
     *   $active = config('domain_queues.active_queues');
     *   // Активны только Q1 и Q2:
     *   // 'active_queues' => [1, 2]
     */
    'active_queues' => [1, 2], // Q3 заблокированы

    // Итого Q1: 34 домена (была 22, добавлено 12 технических)
    // Итого Q2: 22 домена
    // Итого Q3: 31 домен

    /**
     * Для хелперов в сервисах:
     *
     *   if (!in_array($vertical, config('domain_queues.q3_domains'))) { ... }
     */
    'q3_domains' => [
        'art', 'books_and_literature', 'collectibles', 'consulting', 'content',
        'education', 'electronics', 'event_planning', 'farm_direct', 'freelance',
        'gardening', 'hobby_and_craft', 'household_goods', 'insurance', 'legal',
        'logistics', 'luxury', 'music_and_instruments', 'office_catering',
        'party_supplies', 'personal_development', 'pet', 'photography',
        'sports', 'sports_nutrition', 'tickets', 'toys_and_games', 'travel',
        'vegan_products', 'veterinary', 'wedding_planning',
    ],
];
