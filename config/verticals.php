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
        // 28 ТОП-ВЕРТИКАЛЕЙ (TOP-LEVEL VERTICALS)
        // ══════════════════════════════════════════════════════
        
        'supermarket' => [
            'domain' => 'Supermarket',
            'model' => 'SupermarketOrder',
            'active' => true,
            'sub_verticals' => ['MeatShops', 'FarmDirect', 'VeganProducts', 'Confectionery', 'GroceryAndDelivery', 'Food', 'OfficeCatering'],
            'geo' => ['enabled' => true, 'cold_chain' => true, 'delivery_priority' => 'high'],
            'realtime' => ['enabled' => true, 'update_interval' => 5, 'priority' => 'high'],
            'min_order_amount' => 500,
            'b2b' => [
                'enabled' => true,
                'min_quantity' => 1,
                'min_amount' => 400000,
            ],
            'delivery_slots' => [
                'enabled' => true,
                'slot_duration_minutes' => 20,
                'advance_booking_hours' => 48,
                'max_slots_per_day' => 24,
            ],
            'cold_chain' => [
                'enabled' => true,
                'categories' => ['meat', 'dairy', 'fish', 'frozen', 'fresh_vegetables', 'fresh_fruits'],
                'max_temp' => 5,
                'min_temp' => -18,
                'monitoring_interval_minutes' => 5,
                'alert_threshold_violations' => 2,
            ],
            'inventory' => [
                'b2c_reservation_minutes' => 20,
                'b2b_reservation_days' => 7,
                'auto_release_expired' => true,
                'expiry_check_interval_minutes' => 15,
            ],
            'pricing' => [
                'delivery_base_cost' => 200,
                'delivery_per_km_cost' => 50,
                'free_delivery_threshold' => 2000,
            ],
            'notifications' => [
                'telegram' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'ready_for_delivery', 'in_delivery', 'delivered', 'cancelled'],
                    'interactive' => true,
                ],
                'whatsapp' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'viber' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'kakaotalk' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'signal' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'wechat' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'vk' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'odnoklassniki' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                    'interactive' => true,
                ],
                'email' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'in_delivery', 'delivered', 'cancelled'],
                ],
                'push' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'in_delivery', 'delivered'],
                ],
                'imessage' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'in_delivery', 'delivered'],
                ],
                'viber' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                ],
                'kakaotalk' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                ],
                'signal' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                ],
                'wechat' => [
                    'enabled' => true,
                    'events' => ['created', 'confirmed', 'in_delivery', 'delivered'],
                ],
                'vk' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                ],
                'odnoklassniki' => [
                    'enabled' => true,
                    'events' => ['created', 'ready_for_delivery', 'in_delivery', 'delivered'],
                ],
            ],
        ],
        
        'restaurant' => [
            'domain' => 'Restaurant',
            'model' => 'RestaurantOrder',
            'active' => true,
            'sub_verticals' => ['Food', 'Catering'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'high'],
            'realtime' => ['enabled' => true, 'update_interval' => 5, 'priority' => 'high'],
        ],
        
        'beauty' => [
            'domain' => 'Beauty',
            'model' => 'BeautyAppointment',
            'active' => true,
            'sub_verticals' => ['BeautyMasters'],
            'geo' => ['enabled' => true, 'precision' => 'house'],
            'realtime' => ['enabled' => true, 'update_interval' => 30, 'priority' => 'low'],
        ],
        
        'cosmetics' => [
            'domain' => 'Cosmetics',
            'model' => 'CosmeticsOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'pharmacy' => [
            'domain' => 'Pharmacy',
            'model' => 'PharmacyOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'cold_chain' => true, 'delivery_priority' => 'urgent'],
            'realtime' => ['enabled' => true, 'update_interval' => 3, 'priority' => 'critical'],
        ],
        
        'fashion' => [
            'domain' => 'Fashion',
            'model' => 'FashionOrder',
            'active' => true,
            'sub_verticals' => ['Footwear'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'medium'],
        ],
        
        'luxury' => [
            'domain' => 'Luxury',
            'model' => 'LuxuryOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'premium', 'secure_delivery' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'high'],
        ],
        
        'children' => [
            'domain' => 'Children',
            'model' => 'ChildrenOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'furniture' => [
            'domain' => 'Furniture',
            'model' => 'FurnitureOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'heavy_cargo' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'medium'],
        ],
        
        'garden' => [
            'domain' => 'Garden',
            'model' => 'GardenOrder',
            'active' => true,
            'sub_verticals' => ['Gardening'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'heavy_cargo' => true],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'construction' => [
            'domain' => 'Construction',
            'model' => 'ConstructionOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'heavy_cargo' => true],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'home_services' => [
            'domain' => 'HomeServices',
            'model' => 'HomeServiceOrder',
            'active' => true,
            'sub_verticals' => ['CleaningServices'],
            'geo' => ['enabled' => true, 'precision' => 'house'],
            'realtime' => ['enabled' => true, 'update_interval' => 30, 'priority' => 'low'],
        ],
        
        'services' => [
            'domain' => 'Services',
            'model' => 'ServiceOrder',
            'active' => true,
            'sub_verticals' => ['Consulting'],
            'geo' => ['enabled' => false, 'reason' => 'remote_work'],
            'realtime' => ['enabled' => false, 'reason' => 'remote_work'],
        ],
        
        'electronics' => [
            'domain' => 'Electronics',
            'model' => 'ElectronicsOrder',
            'active' => true,
            'sub_verticals' => ['Photography'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium', 'fragile' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'medium'],
        ],
        
        'home_appliances' => [
            'domain' => 'HomeAppliances',
            'model' => 'HomeAppliancesOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'heavy_cargo' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'medium'],
        ],
        
        'auto' => [
            'domain' => 'Auto',
            'model' => 'AutoOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'medium'],
        ],
        
        'taxi' => [
            'domain' => 'Taxi',
            'model' => 'TaxiRide',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'realtime_tracking' => true, 'delivery_priority' => 'urgent'],
            'realtime' => ['enabled' => true, 'update_interval' => 2, 'priority' => 'critical'],
        ],
        
        'health_and_sports' => [
            'domain' => 'HealthAndSports',
            'model' => 'HealthAndSportsOrder',
            'active' => true,
            'sub_verticals' => ['Fitness', 'SportsNutrition'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'booking' => [
            'domain' => 'Booking',
            'model' => 'Booking',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => false, 'reason' => 'booking_only'],
            'realtime' => ['enabled' => false, 'reason' => 'booking_only'],
        ],
        
        'travel' => [
            'domain' => 'Travel',
            'model' => 'TravelOrder',
            'active' => true,
            'sub_verticals' => ['Hotels', 'ShortTermRentals'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'long_distance' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 30, 'priority' => 'low'],
        ],
        
        'leisure' => [
            'domain' => 'Leisure',
            'model' => 'LeisureOrder',
            'active' => true,
            'sub_verticals' => ['EventPlanning', 'Tickets', 'PartySupplies', 'WeddingPlanning', 'ToysAndGames', 'HobbyAndCraft', 'Flowers'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium', 'mass_addresses' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 30, 'priority' => 'low'],
        ],
        
        'real_estate' => [
            'domain' => 'RealEstate',
            'model' => 'RealEstateOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'property_viewing' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 30, 'priority' => 'low'],
        ],
        
        'freelance' => [
            'domain' => 'Freelance',
            'model' => 'FreelanceOrder',
            'active' => true,
            'sub_verticals' => [],
            'geo' => ['enabled' => false, 'reason' => 'remote_work'],
            'realtime' => ['enabled' => false, 'reason' => 'remote_work'],
        ],
        
        'art' => [
            'domain' => 'Art',
            'model' => 'ArtOrder',
            'active' => true,
            'sub_verticals' => ['Collectibles'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'premium', 'secure_delivery' => true],
            'realtime' => ['enabled' => true, 'update_interval' => 10, 'priority' => 'high'],
        ],
        
        'pet_supplies' => [
            'domain' => 'PetSupplies',
            'model' => 'PetSuppliesOrder',
            'active' => true,
            'sub_verticals' => ['pet_food', 'pet_accessories', 'pet_care'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'sports_equipment' => [
            'domain' => 'SportsEquipment',
            'model' => 'SportsEquipmentOrder',
            'active' => true,
            'sub_verticals' => ['fitness_equipment', 'sports_gear', 'outdoor_sports'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium', 'heavy_cargo' => true],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'office_supplies' => [
            'domain' => 'OfficeSupplies',
            'model' => 'OfficeSuppliesOrder',
            'active' => true,
            'sub_verticals' => ['stationery', 'office_equipment', 'paper_products'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'medium'],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
        
        'tools_and_hardware' => [
            'domain' => 'ToolsAndHardware',
            'model' => 'ToolsAndHardwareOrder',
            'active' => true,
            'sub_verticals' => ['power_tools', 'hand_tools', 'hardware'],
            'geo' => ['enabled' => true, 'delivery_priority' => 'low', 'heavy_cargo' => true],
            'realtime' => ['enabled' => false, 'reason' => 'no_tracking'],
        ],
    ],
];
