<?php
declare(strict_types=1);

$files_to_fix = [
    'app\Filament\Tenant\Resources\Marketplace\EducationCourseResource\Pages\EditEducationCourse.php' => 'EducationCourseResource',
    'app\Filament\Tenant\Resources\Marketplace\EducationCourseResource\Pages\ListEducationCourses.php' => 'EducationCourseResource',
    'app\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages\EditEventBooking.php' => 'EventBookingResource',
    'app\Filament\Tenant\Resources\Marketplace\EventBookingResource\Pages\ListEventBookings.php' => 'EventBookingResource',
    'app\Filament\Tenant\Resources\Marketplace\FlowersItemResource\Pages\EditFlowersItem.php' => 'FlowersItemResource',
    'app\Filament\Tenant\Resources\Marketplace\FlowersItemResource\Pages\ListFlowersItems.php' => 'FlowersItemResource',
    'app\Filament\Tenant\Resources\Marketplace\FlowersProductResource\Pages\CreateFlowersProduct.php' => 'FlowersProductResource',
    'app\Filament\Tenant\Resources\Marketplace\FlowersProductResource\Pages\EditFlowersProduct.php' => 'FlowersProductResource',
    'app\Filament\Tenant\Resources\Marketplace\FlowersProductResource\Pages\ListFlowersProducts.php' => 'FlowersProductResource',
    'app\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource\Pages\CreateHRExchangeOffer.php' => 'HRExchangeOfferResource',
    'app\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource\Pages\EditHRExchangeOffer.php' => 'HRExchangeOfferResource',
    'app\Filament\Tenant\Resources\Marketplace\HRExchangeOfferResource\Pages\ListHRExchangeOffers.php' => 'HRExchangeOfferResource',
    'app\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource\Pages\CreateMedicalAppointment.php' => 'MedicalAppointmentResource',
    'app\Filament\Tenant\Resources\Marketplace\MedicalAppointmentResource\Pages\EditMedicalAppointment.php' => 'MedicalAppointmentResource',
];

$fixed = 0;
foreach ($files_to_fix as $file => $resource) {
    $path = __DIR__ . '/' . str_replace('\\', '/', $file);
    if (file_exists($path)) {
        $content = file_get_contents($path);
        $original = $content;
        
        $content = str_replace('B2BSupplyOfferResource::class', "{$resource}::class", $content);
        
        if ($content !== $original) {
            file_put_contents($path, $content);
            echo "✓ Fixed: {$resource}\n";
            $fixed++;
        }
    } else {
        echo "✗ Not found: {$path}\n";
    }
}

echo "\nTotal fixed: {$fixed} files\n";
