<?php

declare(strict_types=1);

namespace App\Providers;

// ─── Auto / Taxi ─────────────────────────────────────────────────────
use App\Domains\Taxi\Events\RideCreated;
use App\Domains\Taxi\Events\RideCompleted;
use App\Domains\Taxi\Events\SurgeUpdated;
use App\Domains\Taxi\Listeners\NotifyDriverRideCreated;
use App\Domains\Taxi\Listeners\ProcessRideCompletedPayout;
use App\Domains\Auto\Events\AutoPartOrderCreated;
use App\Domains\Auto\Events\LowPartsStock;
use App\Domains\Auto\Events\RepairWorkCompleted;
use App\Domains\Auto\Listeners\DeductRepairPartsListener;
use App\Domains\Auto\Listeners\LowPartsStockAlertListener;

// ─── Beauty ──────────────────────────────────────────────────────────
use App\Domains\Beauty\Events\AppointmentScheduled;
use App\Domains\Beauty\Events\AppointmentCompleted;
use App\Domains\Beauty\Events\AppointmentCancelled;
use App\Domains\Beauty\Events\ConsumableDeducted;
use App\Domains\Beauty\Events\LowStockReached;
use App\Domains\Beauty\Listeners\SendAppointmentReminder;
use App\Domains\Beauty\Listeners\UpdateConsumableInventory;
use App\Domains\Beauty\Listeners\DeductAppointmentConsumablesListener;
use App\Domains\Beauty\Listeners\LowStockNotificationListener;

// ─── Channels ────────────────────────────────────────────────────────
use App\Domains\Content\Channels\Events\PostPublished;
use App\Domains\Content\Channels\Events\ChannelArchived;
use App\Domains\Content\Channels\Events\ChannelSubscribed;
use App\Domains\Content\Channels\Listeners\SendPostNotification;
use App\Domains\Content\Channels\Listeners\SendChannelArchivedNotification;

// ─── Confectionery ───────────────────────────────────────────────────
use App\Domains\Confectionery\Events\BakeryOrderCreated;
use App\Domains\Confectionery\Events\BakeryOrderReady;

// ─── Courses ─────────────────────────────────────────────────────────
use App\Domains\Education\Courses\Events\EnrollmentCreated;
use App\Domains\Education\Courses\Events\LessonCompleted;
use App\Domains\Education\Courses\Events\CertificateIssued;
use App\Domains\Education\Courses\Listeners\DeductEnrollmentCommissionListener;
use App\Domains\Education\Courses\Listeners\SendCertificateNotificationListener;

// ─── Electronics ─────────────────────────────────────────────────────
use App\Domains\Electronics\Events\WarrantyClaimSubmitted;

// ─── Entertainment ───────────────────────────────────────────────────
use App\Domains\EventPlanning\Entertainment\Events\BookingCreated as EntertainmentBookingCreated;
use App\Domains\EventPlanning\Entertainment\Events\EventCancelled;
use App\Domains\EventPlanning\Entertainment\Events\TicketSold;
use App\Domains\EventPlanning\Entertainment\Listeners\DeductBookingCommissionListener as EntertainmentDeductBookingCommission;
use App\Domains\EventPlanning\Entertainment\Listeners\RefundBookingCommissionListener as EntertainmentRefundBookingCommission;

// ─── FarmDirect ──────────────────────────────────────────────────────
use App\Domains\FarmDirect\Events\FarmOrderCreated;
use App\Domains\FarmDirect\Events\FarmOrderShipped;

// ─── Fashion ─────────────────────────────────────────────────────────
use App\Domains\Fashion\Events\OrderPlaced;
use App\Domains\Fashion\Events\OrderShipped;
use App\Domains\Fashion\Events\ReturnRequested;
use App\Domains\Fashion\Listeners\DeductOrderCommissionListener as FashionDeductCommission;
use App\Domains\Fashion\Listeners\RefundOrderCommissionListener as FashionRefundCommission;

// ─── Fitness ─────────────────────────────────────────────────────────
use App\Domains\Sports\Fitness\Events\AttendanceRecorded;
use App\Domains\Sports\Fitness\Events\MembershipCreated;
use App\Domains\Sports\Fitness\Events\MembershipExpired;
use App\Domains\Sports\Fitness\Listeners\DeductMembershipCommissionListener;
use App\Domains\Sports\Fitness\Listeners\RefundMembershipCommissionListener;

// ─── Flowers ─────────────────────────────────────────────────────────
use App\Domains\Flowers\Events\FlowerOrderPlaced;
use App\Domains\Flowers\Events\FlowerOrderCreated;
use App\Domains\Flowers\Events\FlowerDeliveryCompleted;
use App\Domains\Flowers\Events\B2BFlowerOrderPlaced;
use App\Domains\Flowers\Listeners\DeductFlowerOrderCommission;
use App\Domains\Flowers\Listeners\DeductFlowerConsumables;
use App\Domains\Flowers\Listeners\UpdateFlowerShopRating;

// ─── Food ────────────────────────────────────────────────────────────
use App\Domains\Food\Events\OrderCreated;
use App\Domains\Food\Events\OrderDelivered;
use App\Domains\Food\Events\OrderCompleted;
use App\Domains\Food\Events\DeliveryStarted;
use App\Domains\Food\Events\LowConsumableStock;
use App\Domains\Food\Listeners\NotifyRestaurantNewOrder;
use App\Domains\Food\Listeners\ProcessOrderDeliveredCommission;
use App\Domains\Food\Listeners\DeductOrderConsumablesListener;
use App\Domains\Food\Listeners\LowConsumableStockAlertListener;

// ─── Freelance ───────────────────────────────────────────────────────
use App\Domains\Freelance\Events\ProposalAccepted;
use App\Domains\Freelance\Events\PaymentMilestoneReleased;
use App\Domains\Freelance\Events\DeliverableSubmitted;
use App\Domains\Freelance\Listeners\DeductProposalCommissionListener;
use App\Domains\Freelance\Listeners\ReleaseFreelancerPaymentListener;

// ─── FreshProduce ────────────────────────────────────────────────────
use App\Domains\FarmDirect\FreshProduce\Events\ProduceOrderCreated;
use App\Domains\FarmDirect\FreshProduce\Events\BoxDelivered;
use App\Domains\FarmDirect\FreshProduce\Events\QualityIssueDetected;

// ─── Furniture ───────────────────────────────────────────────────────
use App\Domains\Furniture\Events\FurnitureOrderCreated;
use App\Domains\Furniture\Events\FurnitureDelivered;

// ─── HealthyFood ─────────────────────────────────────────────────────
use App\Domains\Food\HealthyFood\Events\MealOrderCreated;
use App\Domains\Food\HealthyFood\Events\MealDelivered;

// ─── HomeServices ────────────────────────────────────────────────────
use App\Domains\HomeServices\Events\ServiceJobCreated;
use App\Domains\HomeServices\Events\ServiceJobCompleted;
use App\Domains\HomeServices\Events\ReviewSubmitted as HomeServicesReviewSubmitted;
use App\Domains\HomeServices\Listeners\DeductJobCommissionListener;
use App\Domains\HomeServices\Listeners\RefundJobCommissionListener;

// ─── Hotels ──────────────────────────────────────────────────────────
use App\Domains\Hotels\Events\BookingCreated as HotelsBookingCreated;
use App\Domains\Hotels\Events\BookingCancelled;
use App\Domains\Hotels\Events\CheckoutCompleted;
use App\Domains\Hotels\Events\ReviewSubmitted as HotelsReviewSubmitted;
use App\Domains\Hotels\Listeners\DeductBookingCommissionListener as HotelsDeductBookingCommission;
use App\Domains\Hotels\Listeners\RefundBookingCommissionListener as HotelsRefundBookingCommission;
use App\Domains\Hotels\Listeners\ScheduleHotelPayout;

// ─── Logistics ───────────────────────────────────────────────────────
use App\Domains\Logistics\Events\ShipmentCreated;
use App\Domains\Logistics\Events\ShipmentDelivered;
use App\Domains\Logistics\Events\CourierAssigned;
use App\Domains\Logistics\Listeners\DeductShipmentCommissionListener;
use App\Domains\Logistics\Listeners\RefundShipmentCommissionListener;

// ─── MeatShops ───────────────────────────────────────────────────────
use App\Domains\MeatShops\Events\MeatOrderCreated;

// ─── Medical ─────────────────────────────────────────────────────────
use App\Domains\Medical\Events\AppointmentBooked;
use App\Domains\Medical\Events\AppointmentCompleted as MedicalAppointmentCompleted;
use App\Domains\Medical\Events\TestOrderCreated;
use App\Domains\Medical\Listeners\DeductAppointmentCommissionListener as MedicalDeductAppointmentCommission;
use App\Domains\Medical\Listeners\DeductTestOrderCommissionListener;

// ─── OfficeCatering ──────────────────────────────────────────────────
use App\Domains\OfficeCatering\Events\CorporateOrderCreated;

// ─── Pet ─────────────────────────────────────────────────────────────
use App\Domains\Pet\Events\AppointmentBooked as PetAppointmentBooked;
use App\Domains\Pet\Events\BoardingReservationCreated;
use App\Domains\Pet\Events\ReviewCreated as PetReviewCreated;
use App\Domains\Pet\Listeners\DeductAppointmentCommissionListener as PetDeductAppointmentCommission;
use App\Domains\Pet\Listeners\DeductBoardingCommissionListener;

// ─── Pharmacy ────────────────────────────────────────────────────────
use App\Domains\Pharmacy\Events\PharmacyOrderCreated;
use App\Domains\Pharmacy\Events\PrescriptionVerified;

// ─── Photography ─────────────────────────────────────────────────────
use App\Domains\Photography\Events\SessionCreated;
use App\Domains\Photography\Events\SessionCompleted;
use App\Domains\Photography\Events\ReviewSubmitted as PhotoReviewSubmitted;
use App\Domains\Photography\Listeners\DeductSessionCommissionListener;
use App\Domains\Photography\Listeners\UpdateRatingsListener;

// ─── RealEstate ──────────────────────────────────────────────────────
use App\Domains\RealEstate\Events\PropertyListed;
use App\Domains\RealEstate\Events\PropertyViewed;
use App\Domains\RealEstate\Events\PropertySold;
use App\Domains\RealEstate\Listeners\DeductCommissionListener as RealEstateDeductCommission;
use App\Domains\RealEstate\Listeners\UpdatePropertyStatsListener;

// ─── Sports ──────────────────────────────────────────────────────────
use App\Domains\Sports\Events\PurchaseCreated;
use App\Domains\Sports\Events\PurchaseRefunded;
use App\Domains\Sports\Events\ReviewSubmitted as SportsReviewSubmitted;
use App\Domains\Sports\Listeners\DeductPurchaseCommissionListener;
use App\Domains\Sports\Listeners\RefundPurchaseCommissionListener;

// ─── Tickets ─────────────────────────────────────────────────────────
use App\Domains\Tickets\Events\EventReviewSubmitted;
use App\Domains\Tickets\Events\TicketSaleRefunded;
use App\Domains\Tickets\Listeners\DeductTicketSaleCommissionListener;
use App\Domains\Tickets\Listeners\RefundTicketSaleCommissionListener;

// ─── ToysKids ────────────────────────────────────────────────────────
use App\Domains\ToysAndGames\ToysAndGames\ToysKids\Events\ToyOrderCreated;

// ─── Travel ──────────────────────────────────────────────────────────
use App\Domains\Travel\Events\TourBooked;
use App\Domains\Travel\Events\FlightBooked;
use App\Domains\Travel\Events\TransportationBooked;
use App\Domains\Travel\Listeners\DeductTourBookingCommissionListener;
use App\Domains\Travel\Listeners\DeductTransportationCommissionListener;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

final class EventServiceProvider extends ServiceProvider
{
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
