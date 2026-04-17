<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;
use App\Domains\Advertising\Domain\Events\AdImpressionRegistered;
use App\Domains\Beauty\Events\LoyaltyPointsEarnedEvent;
use App\Domains\Beauty\Events\MasterMatchedEvent;
use App\Domains\Beauty\Events\PriceUpdatedEvent;
use App\Domains\Beauty\Events\VideoCallEndedEvent;
use App\Domains\Beauty\Events\VideoCallInitiatedEvent;
use App\Domains\Beauty\Listeners\LoyaltyPointsEarnedListener;
use App\Domains\Beauty\Listeners\MasterMatchedListener;
use App\Domains\Beauty\Listeners\PriceUpdatedListener;
use App\Domains\Beauty\Listeners\VideoCallEndedListener;
use App\Domains\Auto\Events\AIDiagnosticsCompletedEvent;
use App\Domains\Auto\Events\VideoInspectionInitiatedEvent;
use App\Domains\Auto\Events\ServiceOrderCreatedEvent;
use App\Domains\Auto\Events\CarImportCalculatedEvent;
use App\Domains\Auto\Events\CarImportInitiatedEvent;
use App\Domains\Auto\Events\CarImportDutiesPaidEvent;
use App\Domains\Auto\Listeners\SendDiagnosticsNotificationListener;
use App\Domains\Auto\Listeners\UpdateVehicleConditionListener;
use App\Domains\Auto\Listeners\NotifyServiceCentersListener;
use App\Domains\Auto\Listeners\SendImportCalculationNotificationListener;
use App\Domains\Auto\Listeners\NotifyCustomsDepartmentListener;
use App\Domains\Auto\Listeners\UpdateImportStatusListener;
use App\Domains\Taxi\Events\DriverAssigned;
use App\Domains\Taxi\Events\RideCreated;
use App\Domains\Taxi\Events\RideCompleted;
use App\Domains\Taxi\Events\RideStarted;
use App\Domains\Taxi\Events\SurgeUpdated;
use App\Domains\Taxi\Listeners\NotifyDriverRideCreated;
use App\Domains\Taxi\Listeners\NotifyPassengerDriverAssigned;
use App\Domains\Taxi\Listeners\NotifyRideStarted;
use App\Domains\Taxi\Listeners\ProcessRideCompletedPayout;
use App\Domains\Education\Events\LearningPathGeneratedEvent;
use App\Domains\Education\Listeners\LearningPathGeneratedListener;
use App\Domains\Education\Events\SlotBookedEvent;
use App\Domains\Education\Listeners\SlotBookedListener;
use App\Domains\Education\Events\FraudDetectedEvent;
use App\Domains\Education\Listeners\FraudDetectedListener;
use App\Domains\Sports\Events\AdaptiveWorkoutGeneratedEvent;
use App\Domains\Sports\Events\BookingConfirmedEvent;
use App\Domains\Sports\Events\LiveStreamStartedEvent;
use App\Domains\Sports\Events\FraudDetectedEvent as SportsFraudDetectedEvent;
use App\Domains\Sports\Listeners\SyncAdaptiveWorkoutToCRMListener;
use App\Domains\Sports\Listeners\SendBookingConfirmationNotificationListener;
use App\Domains\Sports\Listeners\NotifyLiveStreamStartedListener;
use App\Domains\Sports\Listeners\HandleFraudDetectedListener;
use App\Listeners\DebitAdCampaignBudget;

final class EventServiceProvider extends ServiceProvider
{
    /** @var array<class-string, list<class-string>> */
        protected $listen = [
            // ── Auto / Taxi ─────────────────────────────────────────────
            // TODO: Auto event listeners not implemented yet
            // RideCreated::class           => [NotifyDriverRideCreated::class],
            // RideCompleted::class         => [ProcessRideCompletedPayout::class],
            // SurgeUpdated::class          => [],
            // AutoPartOrderCreated::class  => [],
            // RepairWorkCompleted::class   => [DeductRepairPartsListener::class],
            // LowPartsStock::class        => [LowPartsStockAlertListener::class],
            CarImportCalculatedEvent::class => [SendImportCalculationNotificationListener::class],
            CarImportInitiatedEvent::class => [NotifyCustomsDepartmentListener::class],
            CarImportDutiesPaidEvent::class => [UpdateImportStatusListener::class],
            // TODO: AutoPartOrderCreated not implemented yet
            // AutoPartOrderCreated::class  => [],
            // TODO: RepairWorkCompleted, DeductRepairPartsListener not implemented yet
            // RepairWorkCompleted::class   => [DeductRepairPartsListener::class],
            // TODO: LowPartsStock, LowPartsStockAlertListener not implemented yet
            // LowPartsStock::class         => [LowPartsStockAlertListener::class],

            // ── Beauty ──────────────────────────────────────────────────
            // TODO: AppointmentScheduled, SendAppointmentReminder not implemented yet
            // AppointmentScheduled::class  => [SendAppointmentReminder::class],
            // TODO: AppointmentCompleted, DeductAppointmentConsumablesListener not implemented yet
            // AppointmentCompleted::class  => [DeductAppointmentConsumablesListener::class],
            VideoCallInitiatedEvent::class => [],
            VideoCallEndedEvent::class    => [VideoCallEndedListener::class],
            AppointmentCancelled::class  => [],
            MasterMatchedEvent::class    => [MasterMatchedListener::class],
            PriceUpdatedEvent::class     => [PriceUpdatedListener::class],
            LoyaltyPointsEarnedEvent::class => [LoyaltyPointsEarnedListener::class],
            FraudDetectedEvent::class    => [FraudDetectedListener::class],
            ConsumableDeducted::class    => [UpdateConsumableInventory::class],
            LowStockReached::class       => [LowStockNotificationListener::class],

            // ── Channels ────────────────────────────────────────────────
            PostPublished::class    => [SendPostNotification::class],
            ChannelArchived::class  => [SendChannelArchivedNotification::class],
            ChannelSubscribed::class => [],

            // ── Confectionery ───────────────────────────────────────────
            BakeryOrderCreated::class => [],
            BakeryOrderReady::class   => [],

            // ── Courses ─────────────────────────────────────────────────
            EnrollmentCreated::class => [DeductEnrollmentCommissionListener::class],
            LessonCompleted::class   => [],
            CertificateIssued::class => [SendCertificateNotificationListener::class],

            // ── Electronics ─────────────────────────────────────────────
            WarrantyClaimSubmitted::class => [],

            // ── Entertainment ───────────────────────────────────────────
            EntertainmentBookingCreated::class => [EntertainmentDeductBookingCommission::class],
            EventCancelled::class              => [EntertainmentRefundBookingCommission::class],
            TicketSold::class                  => [],

            // ── FarmDirect ──────────────────────────────────────────────
            FarmOrderCreated::class => [],
            FarmOrderShipped::class => [],

            // ── Fashion ─────────────────────────────────────────────────
            OrderPlaced::class     => [FashionDeductCommission::class],
            OrderShipped::class    => [],
            ReturnRequested::class => [FashionRefundCommission::class],

            // ── Fitness ─────────────────────────────────────────────────
            AttendanceRecorded::class => [],
            MembershipCreated::class  => [DeductMembershipCommissionListener::class],
            MembershipExpired::class  => [RefundMembershipCommissionListener::class],

            // ── Flowers ─────────────────────────────────────────────────
            FlowerOrderPlaced::class       => [
                DeductFlowerOrderCommission::class,
                DeductFlowerConsumables::class,
            ],
            FlowerOrderCreated::class      => [DeductFlowerConsumables::class],
            FlowerDeliveryCompleted::class => [UpdateFlowerShopRating::class],
            B2BFlowerOrderPlaced::class    => [DeductFlowerConsumables::class],

            // ── Food ────────────────────────────────────────────────────
            OrderCreated::class       => [NotifyRestaurantNewOrder::class],
            OrderDelivered::class     => [ProcessOrderDeliveredCommission::class],
            OrderCompleted::class     => [DeductOrderConsumablesListener::class],
            DeliveryStarted::class    => [],
            LowConsumableStock::class => [LowConsumableStockAlertListener::class],

            // ── Freelance ───────────────────────────────────────────────
            ProposalAccepted::class         => [DeductProposalCommissionListener::class],
            PaymentMilestoneReleased::class => [ReleaseFreelancerPaymentListener::class],
            DeliverableSubmitted::class     => [],

            // ── FreshProduce ────────────────────────────────────────────
            ProduceOrderCreated::class  => [],
            BoxDelivered::class         => [],
            QualityIssueDetected::class => [],

            // ── Furniture ───────────────────────────────────────────────
            FurnitureOrderCreated::class => [],
            FurnitureDelivered::class    => [],

            // ── HealthyFood ─────────────────────────────────────────────
            MealOrderCreated::class => [],
            MealDelivered::class    => [],

            // ── HomeServices ────────────────────────────────────────────
            ServiceJobCreated::class          => [DeductJobCommissionListener::class],
            ServiceJobCompleted::class        => [],
            HomeServicesReviewSubmitted::class => [],

            // ── Hotels ──────────────────────────────────────────────────
            HotelsBookingCreated::class  => [HotelsDeductBookingCommission::class],
            BookingCancelled::class      => [HotelsRefundBookingCommission::class],
            CheckoutCompleted::class     => [ScheduleHotelPayout::class],
            HotelsReviewSubmitted::class => [],

            // ── Logistics ───────────────────────────────────────────────
            ShipmentCreated::class   => [DeductShipmentCommissionListener::class],
            ShipmentDelivered::class => [RefundShipmentCommissionListener::class],
            CourierAssigned::class   => [],

            // ── MeatShops ───────────────────────────────────────────────
            MeatOrderCreated::class => [],

            // ── Medical ─────────────────────────────────────────────────
            AppointmentBooked::class           => [MedicalDeductAppointmentCommission::class],
            MedicalAppointmentCompleted::class => [],
            TestOrderCreated::class            => [DeductTestOrderCommissionListener::class],

            // ── OfficeCatering ──────────────────────────────────────────
            CorporateOrderCreated::class => [],

            // ── Pet ─────────────────────────────────────────────────────
            PetAppointmentBooked::class       => [PetDeductAppointmentCommission::class],
            BoardingReservationCreated::class => [DeductBoardingCommissionListener::class],
            PetReviewCreated::class           => [],

            // ── Pharmacy ────────────────────────────────────────────────
            PharmacyOrderCreated::class => [],
            PrescriptionVerified::class => [],

            // ── Photography ─────────────────────────────────────────────
            SessionCreated::class   => [DeductSessionCommissionListener::class],
            SessionCompleted::class => [UpdateRatingsListener::class],
            PhotoReviewSubmitted::class => [UpdateRatingsListener::class],

            // ── RealEstate (Clean Architecture 2026) ────────────────────
            \App\Domains\RealEstate\Domain\Events\ViewingConfirmed::class => [
                \App\Domains\RealEstate\Application\Listeners\NotifyClientOnViewingConfirmed::class,
            ],
            \App\Domains\RealEstate\Domain\Events\ContractSigned::class => [
                \App\Domains\RealEstate\Application\Listeners\UpdatePropertyStatusOnContractSigned::class,
            // Sports AI & Live Stream Events
            AdaptiveWorkoutGeneratedEvent::class => [SyncAdaptiveWorkoutToCRMListener::class],
            BookingConfirmedEvent::class => [SendBookingConfirmationNotificationListener::class],
            LiveStreamStartedEvent::class => [NotifyLiveStreamStartedListener::class],
            SportsFraudDetectedEvent::class => [HandleFraudDetectedListener::class],
            ],
            \App\Domains\RealEstate\Domain\Events\PropertyListed::class  => [],
            \App\Domains\RealEstate\Domain\Events\ViewingCancelled::class => [],

            // ── Sports ──────────────────────────────────────────────────
            PurchaseCreated::class   => [DeductPurchaseCommissionListener::class],
            PurchaseRefunded::class  => [RefundPurchaseCommissionListener::class],
            SportsReviewSubmitted::class => [],

            // ── Tickets ─────────────────────────────────────────────────
            EventReviewSubmitted::class => [],
            TicketSaleRefunded::class   => [RefundTicketSaleCommissionListener::class],

            // ── ToysKids ────────────────────────────────────────────────
            ToyOrderCreated::class => [],

            // ── Travel ──────────────────────────────────────────────────
            TourBooked::class           => [DeductTourBookingCommissionListener::class],
            FlightBooked::class         => [],
            TransportationBooked::class => [DeductTransportationCommissionListener::class],

            // ── Ad Campaigns ─────────────────────────────────────────────
            AdImpressionRegistered::class => [
                DebitAdCampaignBudget::class,
            ],

            // ── Education ────────────────────────────────────────────────
            LearningPathGeneratedEvent::class => [
                LearningPathGeneratedListener::class,
            ],
            PriceUpdatedEvent::class => [
                PriceUpdatedListener::class,
            ],
            SlotBookedEvent::class => [
                SlotBookedListener::class,
            ],
            FraudDetectedEvent::class => [
                FraudDetectedListener::class,
            ],

            // ── FraudML ───────────────────────────────────────────────────
            \App\Domains\FraudML\Events\ModelVersionUpdated::class => [
                \App\Domains\FraudML\Listeners\ModelVersionUpdatedListener::class,
            ],
            \App\Domains\FraudML\Events\SignificantFeatureDriftDetected::class => [
                \App\Domains\FraudML\Listeners\HandleSignificantFeatureDrift::class,
            ],
        ];

        public function boot(): void {}

        public function shouldDiscoverEvents(): bool
        {
            return true;
        }
}
