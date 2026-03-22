Set-Location $PSScriptRoot

$useStatement = "use App\Services\FraudControlService;"
$fixed = 0
$skipped = 0

$targets = @(
    "app\Domains\Auto\Services\AutoInventoryService.php",
    "app\Domains\Auto\Services\AutoPartsInventoryService.php",
    "app\Domains\Auto\Services\TaxiService.php",
    "app\Domains\Beauty\Services\AppointmentService.php",
    "app\Domains\Beauty\Services\BeautySalonService.php",
    "app\Domains\Beauty\Services\ConsumableDeductionService.php",
    "app\Domains\Beauty\Services\InventoryManagementService.php",
    "app\Domains\Books\Services\BookService.php",
    "app\Domains\ConstructionMaterials\Services\MaterialService.php",
    "app\Domains\Cosmetics\Services\CosmeticService.php",
    "app\Domains\Courses\Services\CertificateService.php",
    "app\Domains\Courses\Services\EnrollmentService.php",
    "app\Domains\Courses\Services\ProgressTrackingService.php",
    "app\Domains\Entertainment\Services\BookingService.php",
    "app\Domains\Entertainment\Services\EventService.php",
    "app\Domains\Entertainment\Services\TicketingService.php",
    "app\Domains\Entertainment\Services\VenueBookingService.php",
    "app\Domains\Fashion\Services\FashionBrandService.php",
    "app\Domains\Fashion\Services\OrderService.php",
    "app\Domains\Fashion\Services\ProductService.php",
    "app\Domains\Fashion\Services\ReturnService.php",
    "app\Domains\FashionRetail\Services\OrderService.php",
    "app\Domains\FashionRetail\Services\ProductService.php",
    "app\Domains\FashionRetail\Services\ReviewService.php",
    "app\Domains\FashionRetail\Services\ShopService.php",
    "app\Domains\Fitness\Services\AttendanceService.php",
    "app\Domains\Fitness\Services\ClassBookingService.php",
    "app\Domains\Fitness\Services\ClassService.php",
    "app\Domains\Fitness\Services\FitnessGymService.php",
    "app\Domains\Fitness\Services\MembershipService.php",
    "app\Domains\Flowers\Services\FlowerDeliveryService.php",
    "app\Domains\Food\Services\DeliveryService.php",
    "app\Domains\Food\Services\DishConsumableService.php",
    "app\Domains\Food\Services\KitchenDisplayService.php",
    "app\Domains\Food\Services\RestaurantOrderService.php",
    "app\Domains\Food\Services\RestaurantService.php",
    "app\Domains\Freelance\Services\ContractService.php",
    "app\Domains\Freelance\Services\FreelanceProjectService.php",
    "app\Domains\Freelance\Services\FreelanceService.php",
    "app\Domains\Freelance\Services\ProjectService.php",
    "app\Domains\Freelance\Services\ProposalService.php",
    "app\Domains\HomeServices\Services\HomeServicesService.php",
    "app\Domains\HomeServices\Services\JobService.php",
    "app\Domains\HomeServices\Services\ListingService.php",
    "app\Domains\HomeServices\Services\ReviewService.php",
    "app\Domains\Hotels\Services\BookingService.php",
    "app\Domains\Hotels\Services\HotelPropertyService.php",
    "app\Domains\Hotels\Services\PayoutScheduleService.php",
    "app\Domains\Hotels\Services\ReviewService.php",
    "app\Domains\Jewelry\Services\Jewelry3DService.php",
    "app\Domains\Jewelry\Services\JewelryService.php",
    "app\Domains\Logistics\Services\CourierService.php",
    "app\Domains\Logistics\Services\CourierServiceService.php",
    "app\Domains\Logistics\Services\LogisticsService.php",
    "app\Domains\Logistics\Services\ShipmentService.php",
    "app\Domains\Logistics\Services\TrackingService.php",
    "app\Domains\Pet\Services\PetServicesService.php",
    "app\Domains\Pet\Services\ProductService.php",
    "app\Domains\Pet\Services\VetAppointmentService.php",
    "app\Domains\Photography\Services\B2BService.php",
    "app\Domains\Photography\Services\GalleryService.php",
    "app\Domains\Photography\Services\PhotographyService.php",
    "app\Domains\Photography\Services\PhotoSessionService.php",
    "app\Domains\Photography\Services\SessionService.php",
    "app\Domains\Sports\Services\BookingService.php",
    "app\Domains\Sports\Services\PurchaseService.php",
    "app\Domains\Sports\Services\ReviewService.php",
    "app\Domains\Sports\Services\SportVenueService.php",
    "app\Domains\Tickets\Services\EventReviewService.php",
    "app\Domains\Tickets\Services\EventTicketService.php",
    "app\Domains\Tickets\Services\TicketGenerationService.php",
    "app\Domains\Tickets\Services\TicketSalesService.php",
    "app\Domains\Tickets\Services\TicketService.php",
    "app\Domains\Travel\Services\BookingService.php",
    "app\Domains\Travel\Services\FlightService.php",
    "app\Domains\Travel\Services\TransportationService.php",
    "app\Domains\Travel\Services\TravelService.php",
    "app\Domains\Travel\Services\TravelTourismService.php",
    "app\Domains\TravelTourism\Services\TourService.php",
    "app\Domains\TravelTourism\Services\TravelBookingService.php",
    "app\Domains\Cosmetics\Services\BeautyTryOnService.php"
)

foreach ($path in $targets) {
    if (-not (Test-Path $path)) {
        Write-Host "NOT FOUND: $path"
        continue
    }

    $content = [System.IO.File]::ReadAllText($path)
    $original = $content

    # 1. Добавить use statement если нет
    $hasUse = $content -match 'use App\\Services\\FraudControlService;'
    if (-not $hasUse) {
        # Добавить после последнего use
        $content = $content -replace '(use [^\n]+;\r?\n)(?!use )', "`$1use App\Services\FraudControlService;`r`n"
    }

    # 2. Inject в конструктор или создать конструктор
    $hasCtor = $content -match 'public function __construct\s*\('
    $hasInjection = $content -match 'FraudControlService \$fraudControlService'
    
    if (-not $hasInjection) {
        if ($hasCtor) {
            # Добавить параметр в существующий конструктор
            $content = $content -replace '(public function __construct\s*\()', "`$1`r`n        private readonly FraudControlService \$fraudControlService,"
        } else {
            # Добавить конструктор после объявления класса
            $content = $content -replace '(final class \w+[^\{]*\{)', "`$1`r`n    public function __construct(`r`n        private readonly FraudControlService \`$fraudControlService,`r`n    ) {}`r`n"
        }
    }

    if ($content -ne $original) {
        [System.IO.File]::WriteAllText($path, $content, [System.Text.Encoding]::UTF8)
        $fixed++
        Write-Host "FIXED: $path"
    } else {
        $skipped++
        Write-Host "SKIP (already OK or no change): $path"
    }
}

Write-Host ""
Write-Host "=== DONE: fixed=$fixed skipped=$skipped ==="
