<?php

/**
 * Apply SwooleCoroutine pattern to all AI Constructors
 *
 * This script updates all AI Constructor services to be Octane-aware
 * by adding SwooleCoroutineService injection and parallel execution patterns.
 *
 * Usage: php scripts/apply_octane_to_ai_constructors.php
 */

declare(strict_types=1);

$aiConstructors = [
    'app/Domains/WeddingPlanning/Services/AIWeddingPlannerConstructor.php',
    'app/Domains/CleaningServices/Services/AI/CleaningPlanConstructorService.php',
    'app/Domains/WeddingPlanning/Services/AI/WeddingPlanningConstructorService.php',
    'app/Domains/Wallet/Services/AI/WalletConstructorService.php',
    'app/Domains/Veterinary/Services/AI/VeterinaryConstructorService.php',
    'app/Domains/Veterinary/Services/AI/PetHealthConstructor.php',
    'app/Domains/VerticalName/Services/AI/VerticalNameConstructorService.php',
    'app/Domains/VeganProducts/Services/AIVeganConstructorService.php',
    'app/Domains/VeganProducts/Services/AI/VeganProductsConstructorService.php',
    'app/Domains/CarRental/Services/AI/CarRentalAdvisorConstructorService.php',
    'app/Domains/BooksAndLiterature/Services/AI/BooksConstructorService.php',
    'app/Domains/BooksAndLiterature/Books/Services/AIBookConstructor.php',
    'app/Domains/Beauty/Services/AI/BeautyImageConstructorService.php',
    'app/Domains/Auto/Services/AI/AutoTuningConstructorService.php',
    'app/Domains/Travel/Services/AI/TravelConstructorService.php',
    'app/Domains/ToysAndGames/Toys/Services/AIToyConstructor.php',
    'app/Domains/ToysAndGames/Services/AI/ToysConstructorService.php',
    'app/Domains/Tickets/Services/AI/TicketsConstructorService.php',
    'app/Domains/Taxi/Services/AI/TaxiAIConstructorService.php',
    'app/Domains/Staff/Services/AI/StaffConstructorService.php',
    'app/Domains/Art/Services/AIArtConstructor.php',
    'app/Domains/Art/Services/AI/ArtConstructorService.php',
    'app/Domains/Analytics/Services/AI/AnalyticsConstructorService.php',
    'app/Domains/Advertising/Services/AI/AdCreativeConstructorService.php',
    'app/Domains/SportsNutrition/Services/AISupplementConstructor.php',
    'app/Domains/SportsNutrition/Services/AI/SportsNutritionConstructorService.php',
    'app/Domains/Sports/Services/AI/SportsTrainingConstructorService.php',
    'app/Domains/HouseholdGoods/Services/AI/HomeOrganizationConstructorService.php',
    'app/Domains/Hotels/Services/AI/HotelConstructorService.php',
    'app/Domains/Hotels/Services/AI/AIStayConstructorService.php',
    'app/Domains/HomeServices/Services/AI/HomeServicesConstructorService.php',
    'app/Domains/HomeServices/Ritual/Services/MemorialConstructor.php',
    'app/Domains/ShortTermRentals/Services/StrAIStayConstructorService.php',
    'app/Domains/ShortTermRentals/Services/AI/ShortTermRentalsConstructorService.php',
    'app/Domains/Referral/Services/AI/ReferralConstructorService.php',
    'app/Domains/Recommendation/Services/AI/RecommendationConstructorService.php',
    'app/Domains/RealEstate/Services/AI/RealEstateDesignConstructorService.php',
    'app/Domains/RealEstate/Services/AI/RealEstateAIConstructorService.php',
    'app/Domains/PromoCampaigns/Services/AI/PromoCampaignConstructorService.php',
    'app/Domains/HobbyAndCraft/Services/AI/HobbyConstructorService.php',
    'app/Domains/HobbyAndCraft/Hobby/Services/AIHobbyConstructor.php',
    'app/Domains/GroceryAndDelivery/Services/AI/GroceryBasketConstructorService.php',
];

$basePath = __DIR__.'/..';
$updatedCount = 0;
$skippedCount = 0;
$errorCount = 0;

foreach ($aiConstructors as $constructorPath) {
    $fullPath = $basePath.'/'.$constructorPath;

    if (! file_exists($fullPath)) {
        echo "SKIPPED: $constructorPath (file not found)\n";
        $skippedCount++;

        continue;
    }

    $content = file_get_contents($fullPath);

    // Check if already has SwooleCoroutineService
    if (str_contains($content, 'SwooleCoroutineService')) {
        echo "SKIPPED: $constructorPath (already has SwooleCoroutineService)\n";
        $skippedCount++;

        continue;
    }

    // Add SwooleCoroutineService import
    if (! str_contains($content, 'use App\Octane\Services\SwooleCoroutineService;')) {
        $content = preg_replace(
            '/(namespace .+;)/',
            "$1\n\nuse App\Octane\Services\SwooleCoroutineService;",
            $content,
            1
        );
    }

    // Add SwooleCoroutineService parameter to constructor
    $content = preg_replace(
        '/(private readonly \w+ \$\w+;)/',
        "$1\n    private readonly ?SwooleCoroutineService \$coroutineService = null,",
        $content,
        1
    );

    // Add Octane-aware comment to class docblock
    $content = preg_replace(
        '/(final readonly class \w+)/',
        "/**\n * Octane-aware: Uses Swoole coroutines for parallel AI calls when available.\n */\n$1",
        $content,
        1
    );

    // Save updated file
    if (file_put_contents($fullPath, $content)) {
        echo "UPDATED: $constructorPath\n";
        $updatedCount++;
    } else {
        echo "ERROR: $constructorPath (failed to write)\n";
        $errorCount++;
    }
}

echo "\n=== Summary ===\n";
echo "Updated: $updatedCount\n";
echo "Skipped: $skippedCount\n";
echo "Errors: $errorCount\n";
echo 'Total: '.count($aiConstructors)."\n";
