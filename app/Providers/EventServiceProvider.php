<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
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
use Modules\GeoLogistics\Events\OrderCreated as LogisticsOrderCreated;
use Modules\GeoLogistics\Events\OrderStatusChanged as LogisticsOrderStatusChanged;
use Modules\GeoLogistics\Events\CourierLocationUpdated;
use Modules\GeoLogistics\Listeners\WriteToClickHouseListener;
use App\Domains\Supermarket\Events\InventoryUpdated;
use App\Domains\Supermarket\Events\ReturnApproved;
use App\Domains\Supermarket\Events\ReturnCreated;
use App\Domains\Supermarket\Events\ReturnRejected;
use App\Domains\Supermarket\Listeners\InvalidateProductCache;
use App\Domains\Supermarket\Listeners\SendReturnApprovedNotification;
use App\Domains\Supermarket\Listeners\SendReturnNotification;
use App\Domains\Supermarket\Listeners\SendReturnRejectedNotification;
use App\Domains\Supermarket\Listeners\UpdateSearchIndex;
use Modules\CatCRM\Infrastructure\Listeners\Supermarket\OrderCreatedListener;
use Modules\CatCRM\Infrastructure\Listeners\Supermarket\OrderStatusUpdatedListener;
use Modules\CatCRM\Infrastructure\Listeners\Supermarket\SubscriptionCreatedListener;
use Modules\CatCRM\Infrastructure\Listeners\Supermarket\ReturnCreatedListener;
use App\Domain\Audit\Events\AuditEvent;
use App\Infrastructure\Listeners\AuditEventListener;

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
            // TODO: AppointmentCancelled event not implemented yet
            // AppointmentCancelled::class  => [],
            MasterMatchedEvent::class    => [MasterMatchedListener::class],
            PriceUpdatedEvent::class     => [PriceUpdatedListener::class],
            LoyaltyPointsEarnedEvent::class => [LoyaltyPointsEarnedListener::class],
            FraudDetectedEvent::class    => [FraudDetectedListener::class],
            // TODO: ConsumableDeducted, UpdateConsumableInventory not implemented yet
            // ConsumableDeducted::class    => [UpdateConsumableInventory::class],
            // TODO: LowStockReached, LowStockNotificationListener not implemented yet
            // LowStockReached::class       => [LowStockNotificationListener::class],

            // ── Channels ────────────────────────────────────────────────
            // TODO: PostPublished, SendPostNotification not implemented yet
            // PostPublished::class    => [SendPostNotification::class],
            // TODO: ChannelArchived, SendChannelArchivedNotification not implemented yet
            // ChannelArchived::class  => [SendChannelArchivedNotification::class],
            // TODO: ChannelSubscribed not implemented yet
            // ChannelSubscribed::class => [],

            // ── Confectionery ───────────────────────────────────────────
            // TODO: BakeryOrderCreated not implemented yet
            // BakeryOrderCreated::class => [],
            // TODO: BakeryOrderReady not implemented yet
            // BakeryOrderReady::class   => [],

            // ── Courses ─────────────────────────────────────────────────
            // TODO: EnrollmentCreated, DeductEnrollmentCommissionListener not implemented yet
            // EnrollmentCreated::class => [DeductEnrollmentCommissionListener::class],
            // TODO: LessonCompleted not implemented yet
            // LessonCompleted::class   => [],
            // TODO: CertificateIssued, SendCertificateNotificationListener not implemented yet
            // CertificateIssued::class => [SendCertificateNotificationListener::class],

            // ── Electronics ─────────────────────────────────────────────
            // TODO: WarrantyClaimSubmitted not implemented yet
            // WarrantyClaimSubmitted::class => [],

            // ── Entertainment ───────────────────────────────────────────
            // TODO: EntertainmentBookingCreated, EntertainmentDeductBookingCommission not implemented yet
            // EntertainmentBookingCreated::class => [EntertainmentDeductBookingCommission::class],
            // TODO: EventCancelled, EntertainmentRefundBookingCommission not implemented yet
            // EventCancelled::class              => [EntertainmentRefundBookingCommission::class],
            // TODO: TicketSold not implemented yet
            // TicketSold::class                  => [],

            // ── FarmDirect ──────────────────────────────────────────────
            // TODO: FarmOrderCreated not implemented yet
            // FarmOrderCreated::class => [],
            // TODO: FarmOrderShipped not implemented yet
            // FarmOrderShipped::class => [],

            // ── Fashion ─────────────────────────────────────────────────
            // TODO: OrderPlaced, FashionDeductCommission not implemented yet
            // OrderPlaced::class     => [FashionDeductCommission::class],
            // TODO: OrderShipped not implemented yet
            // OrderShipped::class    => [],
            // TODO: ReturnRequested, FashionRefundCommission not implemented yet
            // ReturnRequested::class => [FashionRefundCommission::class],

            // ── Fitness ─────────────────────────────────────────────────
            // TODO: AttendanceRecorded not implemented yet
            // AttendanceRecorded::class => [],
            // TODO: MembershipCreated, DeductMembershipCommissionListener not implemented yet
            // MembershipCreated::class  => [DeductMembershipCommissionListener::class],
            // TODO: MembershipExpired, RefundMembershipCommissionListener not implemented yet
            // MembershipExpired::class  => [RefundMembershipCommissionListener::class],

            // ── Flowers ─────────────────────────────────────────────────
            // TODO: FlowerOrderPlaced, DeductFlowerOrderCommission, DeductFlowerConsumables not implemented yet
            // FlowerOrderPlaced::class       => [
            //     DeductFlowerOrderCommission::class,
            //     DeductFlowerConsumables::class,
            // ],
            // TODO: FlowerOrderCreated, DeductFlowerConsumables not implemented yet
            // FlowerOrderCreated::class      => [DeductFlowerConsumables::class],
            // TODO: FlowerDeliveryCompleted, UpdateFlowerShopRating not implemented yet
            // FlowerDeliveryCompleted::class => [UpdateFlowerShopRating::class],
            // TODO: B2BFlowerOrderPlaced not implemented yet
            // B2BFlowerOrderPlaced::class    => [DeductFlowerConsumables::class],

            // ── Food ────────────────────────────────────────────────────
            // TODO: OrderCreated, NotifyRestaurantNewOrder not implemented yet
            // OrderCreated::class       => [NotifyRestaurantNewOrder::class],
            // TODO: OrderDelivered, ProcessOrderDeliveredCommission not implemented yet
            // OrderDelivered::class     => [ProcessOrderDeliveredCommission::class],
            // TODO: OrderCompleted, DeductOrderConsumablesListener not implemented yet
            // OrderCompleted::class     => [DeductOrderConsumablesListener::class],
            // TODO: DeliveryStarted not implemented yet
            // DeliveryStarted::class    => [],
            // TODO: LowConsumableStock, LowConsumableStockAlertListener not implemented yet
            // LowConsumableStock::class => [LowConsumableStockAlertListener::class],

            // ── Freelance ───────────────────────────────────────────────
            // TODO: ProposalAccepted, DeductProposalCommissionListener not implemented yet
            // ProposalAccepted::class         => [DeductProposalCommissionListener::class],
            // TODO: PaymentMilestoneReleased, ReleaseFreelancerPaymentListener not implemented yet
            // PaymentMilestoneReleased::class => [ReleaseFreelancerPaymentListener::class],
            // TODO: DeliverableSubmitted not implemented yet
            // DeliverableSubmitted::class     => [],

            // ── FreshProduce ────────────────────────────────────────────
            // TODO: FreshProduce events not implemented yet
            // ProduceOrderCreated::class  => [],
            // BoxDelivered::class         => [],
            // QualityIssueDetected::class => [],

            // ── Furniture ───────────────────────────────────────────────
            // TODO: Furniture events not implemented yet
            // FurnitureOrderCreated::class => [],
            // FurnitureDelivered::class    => [],

            // ── HealthyFood ─────────────────────────────────────────────
            // TODO: HealthyFood events not implemented yet
            // MealOrderCreated::class => [],
            // MealDelivered::class    => [],

            // ── HomeServices ────────────────────────────────────────────
            // TODO: HomeServices events not implemented yet
            // ServiceJobCreated::class          => [DeductJobCommissionListener::class],
            // ServiceJobCompleted::class        => [],
            // HomeServicesReviewSubmitted::class => [],

            // ── Hotels ──────────────────────────────────────────────────
            // TODO: Hotels events not implemented yet
            // HotelsBookingCreated::class  => [HotelsDeductBookingCommission::class],
            // BookingCancelled::class      => [HotelsRefundBookingCommission::class],
            // CheckoutCompleted::class     => [ScheduleHotelPayout::class],
            // HotelsReviewSubmitted::class => [],

            // ── Logistics ───────────────────────────────────────────────
            // TODO: Logistics commission events not implemented yet
            // ShipmentCreated::class   => [DeductShipmentCommissionListener::class],
            // ShipmentDelivered::class => [RefundShipmentCommissionListener::class],
            // CourierAssigned::class   => [],
            
            // ── Logistics AI (ClickHouse Pipeline) ─────────────────────
            LogisticsOrderCreated::class => [WriteToClickHouseListener::class],
            LogisticsOrderStatusChanged::class => [WriteToClickHouseListener::class],
            CourierLocationUpdated::class => [WriteToClickHouseListener::class],

            // ── MeatShops ───────────────────────────────────────────────
            // TODO: MeatShops events not implemented yet
            // MeatOrderCreated::class => [],

            // ── Medical ─────────────────────────────────────────────────
            // TODO: Medical commission events not implemented yet
            // AppointmentBooked::class           => [MedicalDeductAppointmentCommission::class],
            // MedicalAppointmentCompleted::class => [],
            // TestOrderCreated::class            => [DeductTestOrderCommissionListener::class],

            // ── OfficeCatering ──────────────────────────────────────────
            // TODO: OfficeCatering events not implemented yet
            // CorporateOrderCreated::class => [],

            // ── Pet ─────────────────────────────────────────────────────
            // TODO: Pet commission events not implemented yet
            // PetAppointmentBooked::class       => [PetDeductAppointmentCommission::class],
            // BoardingReservationCreated::class => [DeductBoardingCommissionListener::class],
            // PetReviewCreated::class           => [],

            // ── Pharmacy ────────────────────────────────────────────────
            // TODO: Pharmacy events not implemented yet
            // PharmacyOrderCreated::class => [],
            // PrescriptionVerified::class => [],

            // ── Photography ─────────────────────────────────────────────
            // TODO: Photography events not implemented yet
            // SessionCreated::class   => [DeductSessionCommissionListener::class],
            // SessionCompleted::class => [UpdateRatingsListener::class],
            // PhotoReviewSubmitted::class => [UpdateRatingsListener::class],

            // ── RealEstate (Clean Architecture 2026) ────────────────────
            \App\Domains\RealEstate\Domain\Events\ContractSigned::class => [
                \App\Domains\RealEstate\Application\Listeners\UpdatePropertyStatusOnContractSigned::class,
            ],
            \App\Domains\RealEstate\Domain\Events\PropertyListed::class  => [],
            \App\Domains\RealEstate\Domain\Events\ViewingCancelled::class => [],

            // ── Sports ──────────────────────────────────────────────────
            // Sports AI & Live Stream Events
            AdaptiveWorkoutGeneratedEvent::class => [SyncAdaptiveWorkoutToCRMListener::class],
            BookingConfirmedEvent::class => [SendBookingConfirmationNotificationListener::class],
            LiveStreamStartedEvent::class => [NotifyLiveStreamStartedListener::class],
            SportsFraudDetectedEvent::class => [HandleFraudDetectedListener::class],
            // TODO: Sports commission events not implemented yet
            // PurchaseCreated::class   => [DeductPurchaseCommissionListener::class],
            // PurchaseRefunded::class  => [RefundPurchaseCommissionListener::class],
            // SportsReviewSubmitted::class => [],

            // ── Tickets ─────────────────────────────────────────────────
            // TODO: Tickets events not implemented yet
            // EventReviewSubmitted::class => [],
            // TicketSaleRefunded::class   => [RefundTicketSaleCommissionListener::class],

            // ── ToysKids ────────────────────────────────────────────────
            // TODO: ToysKids events not implemented yet
            // ToyOrderCreated::class => [],

            // ── Travel ──────────────────────────────────────────────────
            // TODO: Travel commission events not implemented yet
            // TourBooked::class           => [DeductTourBookingCommissionListener::class],
            // FlightBooked::class         => [],
            // TransportationBooked::class => [DeductTransportationCommissionListener::class],

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

            // ── Security & Account Protection ─────────────────────────────
            \App\Events\Security\AccountLocked::class => [
                \App\Listeners\Security\SendAccountLockedNotification::class,
            ],
            \App\Events\Security\RecoveryInitiated::class => [
                \App\Listeners\Security\LogRecoveryInitiated::class,
            ],
            \App\Events\Security\PasskeyRevoked::class => [
                \App\Listeners\Security\HandlePasskeyRevoked::class,
            ],

            // ── Zero-Trust Security Events ────────────────────────────────
            \App\Events\Security\BruteForceDetected::class => [
                \App\Listeners\Security\SendBruteForceAlert::class,
            ],
            \App\Events\Security\EmployeeRevoked::class => [
                \App\Listeners\Security\NotifyEmployeeRevoked::class,
            ],
            \App\Events\Security\InsiderAnomalyDetected::class => [
                \App\Listeners\Security\NotifyInsiderAnomaly::class,
            ],

            // ── Cooldown System Events ────────────────────────────────────
            \App\Events\Security\CooldownStarted::class => [
                \App\Listeners\CooldownNotificationListener::class,
                \App\Listeners\Security\NotifyStakeholdersOnVpnBlockListener::class,
            ],
            \App\Events\Security\PasswordChanged::class => [
                \App\Listeners\Security\PasswordChangedCooldownListener::class,
            ],
            \App\Events\Security\TwoFactorChanged::class => [
                \App\Listeners\Security\TwoFactorChangedCooldownListener::class,
            ],
            \App\Events\Security\NewDeviceLogin::class => [
                \App\Listeners\Security\NewDeviceLoginCooldownListener::class,
            ],
            \App\Events\Security\BankDetailsChanged::class => [
                \App\Listeners\Security\BankDetailsChangedCooldownListener::class,
            ],
            \App\Events\Security\StaffInvited::class => [
                \App\Listeners\Security\StaffInvitedCooldownListener::class,
            ],

            // ── Split Key Security Events ───────────────────────────────────
            \App\Events\Security\SplitKeyGenerated::class => [
                \App\Listeners\Security\LogSplitKeyGenerated::class,
            ],
            \App\Events\Security\SplitKeyInvalidated::class => [
                \App\Listeners\Security\InvalidateSplitKeyOnHighRisk::class,
                \App\Listeners\Security\SendSplitKeyInvalidatedNotification::class,
            ],
            \App\Events\Security\SplitKeyRotated::class => [
                \App\Listeners\Security\LogSplitKeyRotated::class,
            ],

            // ── Payment Integration Events ───────────────────────────────────
            \App\Domains\Payment\Events\PaymentSucceeded::class => [
                \App\Domains\Payment\Listeners\UpdateBookingOnPaymentSuccess::class,
                \App\Domains\Payment\Listeners\ProcessLoyaltyOnPaymentSuccess::class,
            ],
            \App\Domains\Payment\Events\PaymentFailed::class => [
                \App\Domains\Payment\Listeners\UpdateBookingOnPaymentFailure::class,
            ],

            // ── Supermarket Inventory Events ─────────────────────────────
            InventoryUpdated::class => [
                InvalidateProductCache::class,
                ReturnCreatedListener::class,
                UpdateSearchIndex::class,
            ],
            ReturnCreated::class => [
                SendReturnNotification::class,
            ],
            ReturnApproved::class => [
                SendReturnApprovedNotification::class,
            ],
            ReturnRejected::class => [
                SendReturnRejectedNotification::class,
            ],

            // ── Supermarket CRM Integration Events ─────────────────────
            \App\Domains\Supermarket\Events\OrderCreated::class => [
                OrderCreatedListener::class,
            ],
            \App\Domains\Supermarket\Events\OrderStatusUpdated::class => [
                OrderStatusUpdatedListener::class,
            ],
            \App\Domains\Supermarket\Events\SubscriptionCreated::class => [
                SubscriptionCreatedListener::class,
            ],

            // ── Manager Bonus Events ─────────────────────────────
            \App\Events\B2BSaleBonusCreated::class => [
                \App\Listeners\B2BSaleBonusListener::class,
            ],

            // ── CRM Task & KPI Events ─────────────────────────────
            \Modules\CatCRM\Domain\Events\TaskCompleted::class => [
                \Modules\CatCRM\Infrastructure\Listeners\UpdateKPIOnTaskCompletionListener::class,
            ],
            \Modules\CatCRM\Domain\Events\TaskAssigned::class => [
                \Modules\CatCRM\Infrastructure\Listeners\UpdateKPIOnTaskAssignmentListener::class,
            ],
            \Modules\CatCRM\Domain\Events\KPICalculated::class => [
                \Modules\CatCRM\Infrastructure\Listeners\LogKPICalculatedListener::class,
            ],

            // ── Audit Events (Domain-Driven Architecture 2026) ─────
            AuditEvent::class => [
                AuditEventListener::class,
            ],
        ];

        public function boot(): void
        {
            // Register Cache Observers for automatic invalidation
            \App\Domains\Shared\Medical\Models\MedicalRecord::observe(\App\Observers\MedicalRecordObserver::class);
            \App\Domains\Shared\Medical\Models\Doctor::observe(\App\Observers\DoctorObserver::class);
            \App\Domains\Shared\Medical\Models\Clinic::observe(\App\Observers\ClinicObserver::class);
            \App\Domains\Shared\Medical\Models\Appointment::observe(\App\Observers\AppointmentObserver::class);
        }

        public function shouldDiscoverEvents(): bool
        {
            return true;
        }
}
