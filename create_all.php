<?php
// Batch Creator - создает все недостающие файлы
const BASE = __DIR__;

$resources = [
    'AiAssistantChat', 'AIConstructor', 'AIReport', 'AnalyticsDash', 'Animal', 'Appointment', 'Attendance',
    'Auto', 'BeautyProduct', 'BeautySalon', 'BehavioralEvent', 'Brand', 'Campaign', 'Category', 'Clinic',
    'Construction', 'Course', 'Deal', 'DeliveryOrder', 'DeliveryZone', 'EducationCourse', 'Employee',
    'EmployeeDeduction', 'Event', 'EventBooking', 'Filter', 'FlowersItem', 'FlowersOrder', 'FlowersProduct',
    'FoodSubVertical', 'GeoEvent', 'GeoZone', 'GiftCard', 'Goods', 'Gym', 'HelpdeskTicket', 'Hotel',
    'HotelBooking', 'HRExchangeOffer', 'HRExchangeTask', 'HRJobVacancy', 'Insurance', 'InternalPayments',
    'InventoryCheck', 'LeaveRequest', 'Master', 'MedicalAppointment', 'MedicalCard', 'Payout', 'PayrollRun',
    'Product', 'PromoCampaign', 'Property', 'PurchaseOrder', 'RestaurantDish', 'RestaurantMenu',
    'RestaurantOrder', 'RestaurantTable', 'Room', 'SalarySlip', 'SettlementDocument', 'StaffSchedule',
    'StaffTask', 'StockMovement', 'SupermarketProduct', 'Supplier', 'Task', 'TaxiCar', 'TaxiDriver',
    'TaxiFleet', 'TaxiTrip', 'Venue', 'Wallet', 'Wishlist',
];

$count = 0;
foreach ($resources as $res) {
    $count++;
    echo "[$count] $res";
    
    // Event Created
    if (!file_exists(BASE . "/app/Events/{$res}Created.php")) {
        file_put_contents(BASE . "/app/Events/{$res}Created.php", "<?php\ndeclare(strict_types=1);\nnamespace App\Events;\nfinal class {$res}Created { public function __construct(public \$model) {} }\n");
    }
    
    // Event Updated
    if (!file_exists(BASE . "/app/Events/{$res}Updated.php")) {
        file_put_contents(BASE . "/app/Events/{$res}Updated.php", "<?php\ndeclare(strict_types=1);\nnamespace App\Events;\nfinal class {$res}Updated { public function __construct(public \$model, public array \$changes = []) {} }\n");
    }
    
    // Event Deleted
    if (!file_exists(BASE . "/app/Events/{$res}Deleted.php")) {
        file_put_contents(BASE . "/app/Events/{$res}Deleted.php", "<?php\ndeclare(strict_types=1);\nnamespace App\Events;\nfinal class {$res}Deleted { public function __construct(public \$model) {} }\n");
    }
    
    // Contract
    if (!file_exists(BASE . "/app/Contracts/{$res}Contract.php")) {
        file_put_contents(BASE . "/app/Contracts/{$res}Contract.php", "<?php\ndeclare(strict_types=1);\nnamespace App\Contracts;\ninterface {$res}Contract { public function list(string \$tenantId, array \$filters = []): array; }\n");
    }
    
    // Policy
    if (!file_exists(BASE . "/app/Policies/{$res}Policy.php")) {
        file_put_contents(BASE . "/app/Policies/{$res}Policy.php", "<?php\ndeclare(strict_types=1);\nnamespace App\Policies;\nfinal class {$res}Policy { }\n");
    }
    
    echo " ✅\n";
}

echo "\n✅ Created all files!\n";
