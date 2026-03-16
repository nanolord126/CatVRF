<?php
$files = [
    'ConstructionResource\Pages\CreateConstruction.php',
    'StaffTaskResource\Pages\CreateStaffTask.php',
    'StaffScheduleResource\Pages\CreateStaffSchedule.php',
    'RoomResource\Pages\CreateRoom.php',
    'PromoCampaignResource\Pages\CreatePromoCampaign.php',
    'PayrollRunResource\Pages\CreatePayrollRun.php',
    'HRJobVacancyResource\Pages\CreateHRJobVacancy.php',
    'HotelResource\Pages\CreateHotel.php',
    'GymResource\Pages\CreateGym.php',
    'DeliveryZoneResource\Pages\CreateDeliveryZone.php',
    'DeliveryOrderResource\Pages\CreateDeliveryOrder.php',
    'CourseResource\Pages\CreateCourse.php',
    'EmployeeDeductionResource\Pages\CreateEmployeeDeduction.php'
];

$base = __DIR__ . '/app/Filament/Tenant/Resources/';

foreach ($files as $file) {
    $path = $base . $file;
    if (!file_exists($path)) { echo "Not found: $path\n"; continue; }
    
    $content = file_get_contents($path);
    
    $imports = [
        'use Illuminate\Log\LogManager;',
        'use Illuminate\Database\DatabaseManager;',
        'use Illuminate\Http\Request;',
        'use Illuminate\Contracts\Auth\Access\Gate;',
        'use Throwable;'
    ];
    
    foreach ($imports as $import) {
        if (!str_contains($content, $import)) {
            $content = preg_replace('/(namespace .*?;)/', "$1\n\n$import", $content, 1);
        }
    }
    
    file_put_contents($path, $content);
    echo "Fixed Imports for: $file\n";
}
echo "DONE\n";