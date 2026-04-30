<?php

declare(strict_types=1);

use Laravel\Pennant\Feature;
use App\Features\AIConstructorGPT4;
use App\Features\AIConstructorMultimodal;
use App\Features\AIConstructorOptimization;
use App\Features\AdAITargeting;
use App\Features\AdBidOptimization;
use App\Features\AdCreativeGeneration;
use App\Features\AnalyticsAIInsights;
use App\Features\AnalyticsPredictive;
use App\Features\AnalyticsRealTime;
use App\Features\ArtAIValuation;
use App\Features\ArtArtistMatching;
use App\Features\ArtStyleTransfer;
use App\Features\AutoAIDiagnostics;
use App\Features\AutoPredictiveMaintenance;
use App\Features\AutoSmartBooking;
use App\Features\BeautyAIStylist;
use App\Features\BeautyBookingOptimization;
use App\Features\BeautyVirtualTryon;
use App\Features\BooksAIRecommendations;
use App\Features\BooksContentAnalysis;
use App\Features\BooksReadingProgress;
use App\Features\CRMAIInsights;
use App\Features\CRMAutomation;
use App\Features\CRMPredictiveAnalytics;
use App\Features\CarRentalAIRecommendations;
use App\Features\CarRentalDynamicPricing;
use App\Features\CarRentalSmartCheckin;
use App\Features\CleaningAIScheduling;
use App\Features\CleaningPriceOptimization;
use App\Features\CleaningQualityMonitoring;
use App\Features\CollectiblesAIValuation;
use App\Features\CollectiblesAuthentication;
use App\Features\CollectiblesMarketAnalysis;
use App\Features\CommunicationAITranslation;
use App\Features\CommunicationRealTimeTranscription;
use App\Features\CommunicationVoiceAI;
use App\Features\ConfectioneryAIRecommendations;
use App\Features\ConfectioneryCustomization;
use App\Features\ConfectionerySeasonalOptimization;
use App\Features\ConstructionAIEstimation;
use App\Features\ConstructionScheduleOptimization;
use App\Features\ConstructionWorkerMatching;
use App\Features\ConsultingAIMatching;
use App\Features\ConsultingExpertRecommendations;
use App\Features\ConsultingKnowledgeBase;
use App\Features\ContentAIGeneration;
use App\Features\ContentDistribution;
use App\Features\ContentOptimization;
use App\Features\DeliveryAIScheduling;
use App\Features\DeliveryRealTimeTracking;
use App\Features\DeliveryRouteOptimization;
use App\Features\DynamicPricingML;
use App\Features\EducationAITutor;
use App\Features\EducationAdaptiveLearning;
use App\Features\EducationContentGeneration;
use App\Features\ElectronicsAIComparisons;
use App\Features\ElectronicsPriceTracking;
use App\Features\ElectronicsReviewsAnalysis;
use App\Features\EventBudgetOptimization;
use App\Features\EventPlanningAIAssistant;
use App\Features\EventVendorMatching;
use App\Features\FarmDirectAIMatching;
use App\Features\FarmDirectQualityControl;
use App\Features\FarmDirectSupplyOptimization;
use App\Features\FashionAIStylist;
use App\Features\FashionTrendPrediction;
use App\Features\FashionVirtualFitting;
use App\Features\FinancesAIAdvisor;
use App\Features\FinancesBudgetOptimization;
use App\Features\FinancesInvestmentRecommendations;
use App\Features\FitnessAICoach;
use App\Features\FitnessProgressTracking;
use App\Features\FitnessWorkoutGeneration;
use App\Features\FlowersAIArrangements;
use App\Features\FlowersDeliveryOptimization;
use App\Features\FlowersGiftRecommendations;
use App\Features\FoodDeliveryOptimization;
use App\Features\FoodMenuAIRecommendations;
use App\Features\FoodRealTimeTracking;
use App\Features\FraudBehavioralAnalysis;
use App\Features\FraudMLModelV2;
use App\Features\FraudRealtimeScoring;
use App\Features\FreelanceAIMatching;
use App\Features\FreelancePriceRecommendations;
use App\Features\FreelanceSkillAssessment;
use App\Features\FurnitureAIDesigner;
use App\Features\FurnitureARVisualization;
use App\Features\FurnitureSmartMatching;
use App\Features\GardeningAIAssistant;
use App\Features\GardeningPlantCare;
use App\Features\GardeningSeasonalPlanning;
use App\Features\GeoGeofencing;
use App\Features\GeoLocationIntelligence;
use App\Features\GeoLogisticsDemandForecasting;
use App\Features\GeoLogisticsFleetOptimization;
use App\Features\GeoLogisticsWarehouseManagement;
use App\Features\GeoRouteOptimization;
use App\Features\GroceryAIRecommendations;
use App\Features\GroceryDeliveryScheduling;
use App\Features\GroceryInventoryOptimization;
use App\Features\HobbyCraftAITutorials;
use App\Features\HobbyCraftMaterialOptimization;
use App\Features\HobbyCraftProjectRecommendations;
use App\Features\HomeServicesAIMatching;
use App\Features\HomeServicesBookingOptimization;
use App\Features\HomeServicesPriceEstimation;
use App\Features\HotelsAIRecommendations;
use App\Features\HotelsSmartCheckin;
use App\Features\HouseholdAIRecommendations;
use App\Features\HouseholdInventoryOptimization;
use App\Features\HouseholdPriceComparison;
use App\Features\InsuranceAIUnderwriting;
use App\Features\InsuranceClaimsAutomation;
use App\Features\InsuranceRiskScoring;
use App\Features\InventoryAutoReorder;
use App\Features\InventoryOptimization;
use App\Features\InventoryPrediction;
use App\Features\LegalAIDocumentAnalysis;
use App\Features\LegalAIResearch;
use App\Features\LegalContractReview;
use App\Features\LogisticsAIScheduling;
use App\Features\LogisticsRealTimeTracking;
use App\Features\LogisticsRouteOptimization;
use App\Features\LuxuryAICuration;
use App\Features\LuxuryPersonalStylist;
use App\Features\LuxuryVIPServices;
use App\Features\MarketplaceAIMatching;
use App\Features\MarketplaceDynamicPricing;
use App\Features\MarketplaceFraudDetection;
use App\Features\MeatShopsDeliveryOptimization;
use App\Features\MeatShopsInventoryOptimization;
use App\Features\MeatShopsQualityAI;
use App\Features\MedicalAIDiagnosis;
use App\Features\MedicalEmergencyFlow;
use App\Features\MedicalSlotsOptimization;
use App\Features\MedicalVideoConsultation;
use App\Features\MusicAIRecommendations;
use App\Features\MusicInstrumentMatching;
use App\Features\MusicLessonScheduling;
use App\Features\OctaneCoroutines;
use App\Features\OctaneGracefulShutdown;
use App\Features\OctaneWebsockets;
use App\Features\OfficeCateringAIMenu;
use App\Features\OfficeCateringBudgetOptimization;
use App\Features\OfficeCateringScheduling;
use App\Features\PartySuppliesAIRecommendations;
use App\Features\PartySuppliesBundleCreation;
use App\Features\PartySuppliesInventoryOptimization;
use App\Features\PaymentCrypto;
use App\Features\PaymentInstallments;
use App\Features\PaymentRecurring;
use App\Features\PaymentYookassaV3;
use App\Features\PersonalDevAICoach;
use App\Features\PersonalDevContentRecommendations;
use App\Features\PersonalDevGoalTracking;
use App\Features\PetAIVetConsultation;
use App\Features\PetHealthTracking;
use App\Features\PetServiceMatching;
use App\Features\PharmacyAIInteractions;
use App\Features\PharmacyDeliveryScheduling;
use App\Features\PharmacyInventoryOptimization;
use App\Features\PhotographyAIEditing;
use App\Features\PhotographyPortfolioMatching;
use App\Features\PhotographyStyleTransfer;
use App\Features\RealEstateAIValuation;
use App\Features\RealEstateSmartMatching;
use App\Features\RealEstateVirtualTours;
use App\Features\ShortTermAIReviews;
use App\Features\ShortTermDynamicPricing;
use App\Features\ShortTermGuestScreening;
use App\Features\SportsAIPredictions;
use App\Features\SportsLiveBetting;
use App\Features\SportsNutritionAIRecommendations;
use App\Features\SportsNutritionMealPlanning;
use App\Features\SportsNutritionTracking;
use App\Features\SportsRealTimeStats;
use App\Features\StaffAIRecruiting;
use App\Features\StaffScheduleOptimization;
use App\Features\StaffSkillMatching;
use App\Features\TaxiAIDispatch;
use App\Features\TaxiRouteOptimization;
use App\Features\TaxiSurgeOptimization;
use App\Features\TicketsDynamicPricing;
use App\Features\TicketsFraudDetection;
use App\Features\TicketsSeatRecommendations;
use App\Features\ToysAICurated;
use App\Features\ToysAgeRecommendations;
use App\Features\ToysSafetyCheck;
use App\Features\TravelAIItinerary;
use App\Features\TravelDynamicPricing;
use App\Features\TravelRealTimeAlerts;
use App\Features\UIDarkMode;
use App\Features\UIMobileOptimizations;
use App\Features\UINewDashboard;
use App\Features\VeganAIRecommendations;
use App\Features\VeganNutritionAnalysis;
use App\Features\VeganRecipeGeneration;
use App\Features\VeterinaryAIDiagnosis;
use App\Features\VeterinaryAppointmentOptimization;
use App\Features\VeterinaryTelemedicine;
use App\Features\WalletAIInsights;
use App\Features\WalletCryptoIntegration;
use App\Features\WalletMultiCurrency;
use App\Features\WeddingAIPlanner;
use App\Features\WeddingBudgetTracking;
use App\Features\WeddingVendorRecommendations;

return [
    /*
    |--------------------------------------------------------------------------
    | Default Store
    |--------------------------------------------------------------------------
    |
    | This option controls the default feature flag storage that will be used.
    | You may configure additional stores below.
    |
    */
    'default' => env('PENNANT_STORE', 'database'),

    /*
    |--------------------------------------------------------------------------
    | Feature Stores
    |--------------------------------------------------------------------------
    |
    | Here you may configure the various feature flag stores used by the
    | application. Each driver has its own configuration options.
    |
    */
    'stores' => [
        'database' => [
            'driver' => 'database',
            'connection' => env('DB_CONNECTION', 'pgsql'),
            'table' => 'features',
        ],

        'redis' => [
            'driver' => 'redis',
            'connection' => env('PENNANT_REDIS_CONNECTION', 'default'),
            'prefix' => 'pennant:',
        ],

        'array' => [
            'driver' => 'array',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Classes
    |--------------------------------------------------------------------------
    |
    | Here you may define the feature classes that are available in the
    | application. Features are resolved via the container and may be
    | any callable or class that implements the Feature contract.
    |
    */
    'features' => [
        // Medical Vertical Features
        'medical-ai-diagnosis' => MedicalAIDiagnosis::class,
        'medical-emergency-flow' => MedicalEmergencyFlow::class,
        'medical-video-consultation' => MedicalVideoConsultation::class,
        'medical-slots-optimization' => MedicalSlotsOptimization::class,

        // Payment Vertical Features
        'payment-yookassa-v3' => PaymentYookassaV3::class,
        'payment-crypto' => PaymentCrypto::class,
        'payment-installments' => PaymentInstallments::class,
        'payment-recurring' => PaymentRecurring::class,

        // Fraud ML Features
        'fraud-ml-model-v2' => FraudMLModelV2::class,
        'fraud-realtime-scoring' => FraudRealtimeScoring::class,
        'fraud-behavioral-analysis' => FraudBehavioralAnalysis::class,

        // AI Constructor Features
        'ai-constructor-gpt4' => AIConstructorGPT4::class,
        'ai-constructor-multimodal' => AIConstructorMultimodal::class,
        'ai-constructor-optimization' => AIConstructorOptimization::class,

        // Octane/Swoole Features
        'octane-coroutines' => OctaneCoroutines::class,
        'octane-websockets' => OctaneWebsockets::class,
        'octane-graceful-shutdown' => OctaneGracefulShutdown::class,

        // Beauty Vertical Features
        'beauty-ai-stylist' => BeautyAIStylist::class,
        'beauty-virtual-tryon' => BeautyVirtualTryon::class,
        'beauty-booking-optimization' => BeautyBookingOptimization::class,

        // Food Vertical Features
        'food-delivery-optimization' => FoodDeliveryOptimization::class,
        'food-menu-ai-recommendations' => FoodMenuAIRecommendations::class,
        'food-real-time-tracking' => FoodRealTimeTracking::class,

        // Real Estate Vertical Features
        'realestate-ai-valuation' => RealEstateAIValuation::class,
        'realestate-virtual-tours' => RealEstateVirtualTours::class,
        'realestate-smart-matching' => RealEstateSmartMatching::class,

        // Fashion Vertical Features
        'fashion-ai-stylist' => FashionAIStylist::class,
        'fashion-virtual-fitting' => FashionVirtualFitting::class,
        'fashion-trend-prediction' => FashionTrendPrediction::class,

        // Travel Vertical Features
        'travel-ai-itinerary' => TravelAIItinerary::class,
        'travel-dynamic-pricing' => TravelDynamicPricing::class,
        'travel-real-time-alerts' => TravelRealTimeAlerts::class,

        // Auto Vertical Features
        'auto-ai-diagnostics' => AutoAIDiagnostics::class,
        'auto-predictive-maintenance' => AutoPredictiveMaintenance::class,
        'auto-smart-booking' => AutoSmartBooking::class,

        // Hotels Vertical Features
        'dynamic-pricing-ml' => DynamicPricingML::class,
        'hotels-ai-recommendations' => HotelsAIRecommendations::class,
        'hotels-smart-checkin' => HotelsSmartCheckin::class,

        // Electronics Vertical Features
        'electronics-ai-comparisons' => ElectronicsAIComparisons::class,
        'electronics-price-tracking' => ElectronicsPriceTracking::class,
        'electronics-reviews-analysis' => ElectronicsReviewsAnalysis::class,

        // Fitness Vertical Features
        'fitness-ai-coach' => FitnessAICoach::class,
        'fitness-workout-generation' => FitnessWorkoutGeneration::class,
        'fitness-progress-tracking' => FitnessProgressTracking::class,

        // Sports Vertical Features
        'sports-live-betting' => SportsLiveBetting::class,
        'sports-ai-predictions' => SportsAIPredictions::class,
        'sports-real-time-stats' => SportsRealTimeStats::class,

        // Luxury Vertical Features
        'luxury-ai-curation' => LuxuryAICuration::class,
        'luxury-vip-services' => LuxuryVIPServices::class,
        'luxury-personal-stylist' => LuxuryPersonalStylist::class,

        // Insurance Vertical Features
        'insurance-ai-underwriting' => InsuranceAIUnderwriting::class,
        'insurance-claims-automation' => InsuranceClaimsAutomation::class,
        'insurance-risk-scoring' => InsuranceRiskScoring::class,

        // Legal Vertical Features
        'legal-ai-document-analysis' => LegalAIDocumentAnalysis::class,
        'legal-contract-review' => LegalContractReview::class,
        'legal-ai-research' => LegalAIResearch::class,

        // Logistics Vertical Features
        'logistics-route-optimization' => LogisticsRouteOptimization::class,
        'logistics-ai-scheduling' => LogisticsAIScheduling::class,
        'logistics-real-time-tracking' => LogisticsRealTimeTracking::class,

        // Education Vertical Features
        'education-ai-tutor' => EducationAITutor::class,
        'education-adaptive-learning' => EducationAdaptiveLearning::class,
        'education-content-generation' => EducationContentGeneration::class,

        // CRM Vertical Features
        'crm-ai-insights' => CRMAIInsights::class,
        'crm-predictive-analytics' => CRMPredictiveAnalytics::class,
        'crm-automation' => CRMAutomation::class,

        // Delivery Vertical Features
        'delivery-route-optimization' => DeliveryRouteOptimization::class,
        'delivery-real-time-tracking' => DeliveryRealTimeTracking::class,
        'delivery-ai-scheduling' => DeliveryAIScheduling::class,

        // Analytics Vertical Features
        'analytics-real-time' => AnalyticsRealTime::class,
        'analytics-ai-insights' => AnalyticsAIInsights::class,
        'analytics-predictive' => AnalyticsPredictive::class,

        // Consulting Vertical Features
        'consulting-ai-matching' => ConsultingAIMatching::class,
        'consulting-expert-recommendations' => ConsultingExpertRecommendations::class,
        'consulting-knowledge-base' => ConsultingKnowledgeBase::class,

        // Content Vertical Features
        'content-ai-generation' => ContentAIGeneration::class,
        'content-optimization' => ContentOptimization::class,
        'content-distribution' => ContentDistribution::class,

        // Freelance Vertical Features
        'freelance-ai-matching' => FreelanceAIMatching::class,
        'freelance-skill-assessment' => FreelanceSkillAssessment::class,
        'freelance-price-recommendations' => FreelancePriceRecommendations::class,

        // Event Planning Vertical Features
        'event-planning-ai-assistant' => EventPlanningAIAssistant::class,
        'event-vendor-matching' => EventVendorMatching::class,
        'event-budget-optimization' => EventBudgetOptimization::class,

        // Staff Vertical Features
        'staff-ai-recruiting' => StaffAIRecruiting::class,
        'staff-skill-matching' => StaffSkillMatching::class,
        'staff-schedule-optimization' => StaffScheduleOptimization::class,

        // Inventory Vertical Features
        'inventory-prediction' => InventoryPrediction::class,
        'inventory-auto-reorder' => InventoryAutoReorder::class,
        'inventory-optimization' => InventoryOptimization::class,

        // Taxi Vertical Features
        'taxi-surge-optimization' => TaxiSurgeOptimization::class,
        'taxi-route-optimization' => TaxiRouteOptimization::class,
        'taxi-ai-dispatch' => TaxiAIDispatch::class,

        // Tickets Vertical Features
        'tickets-dynamic-pricing' => TicketsDynamicPricing::class,
        'tickets-seat-recommendations' => TicketsSeatRecommendations::class,
        'tickets-fraud-detection' => TicketsFraudDetection::class,

        // Wallet Vertical Features
        'wallet-crypto-integration' => WalletCryptoIntegration::class,
        'wallet-multi-currency' => WalletMultiCurrency::class,
        'wallet-ai-insights' => WalletAIInsights::class,

        // Pet Vertical Features
        'pet-ai-vet-consultation' => PetAIVetConsultation::class,
        'pet-health-tracking' => PetHealthTracking::class,
        'pet-service-matching' => PetServiceMatching::class,

        // Wedding Planning Vertical Features
        'wedding-ai-planner' => WeddingAIPlanner::class,
        'wedding-vendor-recommendations' => WeddingVendorRecommendations::class,
        'wedding-budget-tracking' => WeddingBudgetTracking::class,

        // Veterinary Vertical Features
        'veterinary-ai-diagnosis' => VeterinaryAIDiagnosis::class,
        'veterinary-telemedicine' => VeterinaryTelemedicine::class,
        'veterinary-appointment-optimization' => VeterinaryAppointmentOptimization::class,

        // Toys & Games Vertical Features
        'toys-age-recommendations' => ToysAgeRecommendations::class,
        'toys-ai-curated' => ToysAICurated::class,
        'toys-safety-check' => ToysSafetyCheck::class,

        // Advertising Vertical Features
        'ad-ai-targeting' => AdAITargeting::class,
        'ad-bid-optimization' => AdBidOptimization::class,
        'ad-creative-generation' => AdCreativeGeneration::class,

        // Car Rental Vertical Features
        'carrental-dynamic-pricing' => CarRentalDynamicPricing::class,
        'carrental-ai-recommendations' => CarRentalAIRecommendations::class,
        'carrental-smart-checkin' => CarRentalSmartCheckin::class,

        // Finances Vertical Features
        'finances-ai-advisor' => FinancesAIAdvisor::class,
        'finances-budget-optimization' => FinancesBudgetOptimization::class,
        'finances-investment-recommendations' => FinancesInvestmentRecommendations::class,

        // Flowers Vertical Features
        'flowers-ai-arrangements' => FlowersAIArrangements::class,
        'flowers-delivery-optimization' => FlowersDeliveryOptimization::class,
        'flowers-gift-recommendations' => FlowersGiftRecommendations::class,

        // Furniture Vertical Features
        'furniture-ai-designer' => FurnitureAIDesigner::class,
        'furniture-ar-visualization' => FurnitureARVisualization::class,
        'furniture-smart-matching' => FurnitureSmartMatching::class,

        // Pharmacy Vertical Features
        'pharmacy-ai-interactions' => PharmacyAIInteractions::class,
        'pharmacy-inventory-optimization' => PharmacyInventoryOptimization::class,
        'pharmacy-delivery-scheduling' => PharmacyDeliveryScheduling::class,

        // Photography Vertical Features
        'photography-ai-editing' => PhotographyAIEditing::class,
        'photography-style-transfer' => PhotographyStyleTransfer::class,
        'photography-portfolio-matching' => PhotographyPortfolioMatching::class,

        // Short Term Rentals Vertical Features
        'shortterm-dynamic-pricing' => ShortTermDynamicPricing::class,
        'shortterm-ai-reviews' => ShortTermAIReviews::class,
        'shortterm-guest-screening' => ShortTermGuestScreening::class,

        // Sports Nutrition Vertical Features
        'sportsnutrition-ai-recommendations' => SportsNutritionAIRecommendations::class,
        'sportsnutrition-meal-planning' => SportsNutritionMealPlanning::class,
        'sportsnutrition-tracking' => SportsNutritionTracking::class,

        // Personal Development Vertical Features
        'personaldev-ai-coach' => PersonalDevAICoach::class,
        'personaldev-goal-tracking' => PersonalDevGoalTracking::class,
        'personaldev-content-recommendations' => PersonalDevContentRecommendations::class,

        // Home Services Vertical Features
        'homeservices-ai-matching' => HomeServicesAIMatching::class,
        'homeservices-booking-optimization' => HomeServicesBookingOptimization::class,
        'homeservices-price-estimation' => HomeServicesPriceEstimation::class,

        // Gardening Vertical Features
        'gardening-ai-assistant' => GardeningAIAssistant::class,
        'gardening-plant-care' => GardeningPlantCare::class,
        'gardening-seasonal-planning' => GardeningSeasonalPlanning::class,

        // Geo Vertical Features
        'geo-location-intelligence' => GeoLocationIntelligence::class,
        'geo-geofencing' => GeoGeofencing::class,
        'geo-route-optimization' => GeoRouteOptimization::class,

        // Geo Logistics Vertical Features
        'geologistics-fleet-optimization' => GeoLogisticsFleetOptimization::class,
        'geologistics-warehouse-management' => GeoLogisticsWarehouseManagement::class,
        'geologistics-demand-forecasting' => GeoLogisticsDemandForecasting::class,

        // Grocery & Delivery Vertical Features
        'grocery-ai-recommendations' => GroceryAIRecommendations::class,
        'grocery-inventory-optimization' => GroceryInventoryOptimization::class,
        'grocery-delivery-scheduling' => GroceryDeliveryScheduling::class,

        // Farm Direct Vertical Features
        'farmdirect-ai-matching' => FarmDirectAIMatching::class,
        'farmdirect-quality-control' => FarmDirectQualityControl::class,
        'farmdirect-supply-optimization' => FarmDirectSupplyOptimization::class,

        // Meat Shops Vertical Features
        'meatshops-quality-ai' => MeatShopsQualityAI::class,
        'meatshops-inventory-optimization' => MeatShopsInventoryOptimization::class,
        'meatshops-delivery-optimization' => MeatShopsDeliveryOptimization::class,

        // Office Catering Vertical Features
        'officecatering-ai-menu' => OfficeCateringAIMenu::class,
        'officecatering-budget-optimization' => OfficeCateringBudgetOptimization::class,
        'officecatering-scheduling' => OfficeCateringScheduling::class,

        // Party Supplies Vertical Features
        'partysupplies-ai-recommendations' => PartySuppliesAIRecommendations::class,
        'partysupplies-inventory-optimization' => PartySuppliesInventoryOptimization::class,
        'partysupplies-bundle-creation' => PartySuppliesBundleCreation::class,

        // Confectionery Vertical Features
        'confectionery-ai-recommendations' => ConfectioneryAIRecommendations::class,
        'confectionery-customization' => ConfectioneryCustomization::class,
        'confectionery-seasonal-optimization' => ConfectionerySeasonalOptimization::class,

        // Construction & Repair Vertical Features
        'construction-ai-estimation' => ConstructionAIEstimation::class,
        'construction-worker-matching' => ConstructionWorkerMatching::class,
        'construction-schedule-optimization' => ConstructionScheduleOptimization::class,

        // Cleaning Services Vertical Features
        'cleaning-ai-scheduling' => CleaningAIScheduling::class,
        'cleaning-price-optimization' => CleaningPriceOptimization::class,
        'cleaning-quality-monitoring' => CleaningQualityMonitoring::class,

        // Communication Vertical Features
        'communication-ai-translation' => CommunicationAITranslation::class,
        'communication-voice-ai' => CommunicationVoiceAI::class,
        'communication-real-time-transcription' => CommunicationRealTimeTranscription::class,

        // Books & Literature Vertical Features
        'books-ai-recommendations' => BooksAIRecommendations::class,
        'books-content-analysis' => BooksContentAnalysis::class,
        'books-reading-progress' => BooksReadingProgress::class,

        // Collectibles Vertical Features
        'collectibles-ai-valuation' => CollectiblesAIValuation::class,
        'collectibles-market-analysis' => CollectiblesMarketAnalysis::class,
        'collectibles-authentication' => CollectiblesAuthentication::class,

        // Hobby & Craft Vertical Features
        'hobbycraft-ai-tutorials' => HobbyCraftAITutorials::class,
        'hobbycraft-project-recommendations' => HobbyCraftProjectRecommendations::class,
        'hobbycraft-material-optimization' => HobbyCraftMaterialOptimization::class,

        // Household Goods Vertical Features
        'household-ai-recommendations' => HouseholdAIRecommendations::class,
        'household-inventory-optimization' => HouseholdInventoryOptimization::class,
        'household-price-comparison' => HouseholdPriceComparison::class,

        // Marketplace Vertical Features
        'marketplace-ai-matching' => MarketplaceAIMatching::class,
        'marketplace-dynamic-pricing' => MarketplaceDynamicPricing::class,
        'marketplace-fraud-detection' => MarketplaceFraudDetection::class,

        // Music & Instruments Vertical Features
        'music-ai-recommendations' => MusicAIRecommendations::class,
        'music-instrument-matching' => MusicInstrumentMatching::class,
        'music-lesson-scheduling' => MusicLessonScheduling::class,

        // Vegan Products Vertical Features
        'vegan-ai-recommendations' => VeganAIRecommendations::class,
        'vegan-nutrition-analysis' => VeganNutritionAnalysis::class,
        'vegan-recipe-generation' => VeganRecipeGeneration::class,

        // Art Vertical Features
        'art-ai-valuation' => ArtAIValuation::class,
        'art-style-transfer' => ArtStyleTransfer::class,
        'art-artist-matching' => ArtArtistMatching::class,

        // UI/UX Features
        'ui-dark-mode' => UIDarkMode::class,
        'ui-new-dashboard' => UINewDashboard::class,
        'ui-mobile-optimizations' => UIMobileOptimizations::class,
    ],
];
