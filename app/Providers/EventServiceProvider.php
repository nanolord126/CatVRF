<?php declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class EventServiceProvider extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    /** @var array<class-string, list<class-string>> */
        protected $listen = [
            // ── Auto / Taxi ─────────────────────────────────────────────
            RideCreated::class           => [NotifyDriverRideCreated::class],
            RideCompleted::class         => [ProcessRideCompletedPayout::class],
            SurgeUpdated::class          => [],
            AutoPartOrderCreated::class  => [],
            RepairWorkCompleted::class   => [DeductRepairPartsListener::class],
            LowPartsStock::class         => [LowPartsStockAlertListener::class],

            // ── Beauty ──────────────────────────────────────────────────
            AppointmentScheduled::class  => [SendAppointmentReminder::class],
            AppointmentCompleted::class  => [DeductAppointmentConsumablesListener::class],
            AppointmentCancelled::class  => [],
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

            // ── RealEstate ──────────────────────────────────────────────
            PropertyListed::class => [],
            PropertyViewed::class => [UpdatePropertyStatsListener::class],
            PropertySold::class   => [RealEstateDeductCommission::class],

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
        ];

        public function boot(): void {}

        public function shouldDiscoverEvents(): bool
        {
            return true;
        }
}
