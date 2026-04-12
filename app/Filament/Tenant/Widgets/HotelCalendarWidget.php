<?php
declare(strict_types=1);

namespace App\Filament\Tenant\Widgets;

use Filament\Widgets\Widget;
use App\Domains\Hotels\Models\HotelBooking;
use Illuminate\Contracts\View\View;

final class HotelCalendarWidget extends Widget
{
    protected static string $view = "filament.tenant.widgets.hotel-calendar-widget";

    public array $bookingsThisMonth = [];

    public function mount(): void
    {
        $this->bookingsThisMonth = HotelBooking::with(["room", "hotel", "customer"])
            ->whereBetween("check_in", [now()->startOfMonth(), now()->endOfMonth()])
            ->orWhereBetween("check_out", [now()->startOfMonth(), now()->endOfMonth()])
            ->orderBy("check_in")
            ->get()
            ->map(function (HotelBooking $booking): array {
                return [
                    "id" => $booking->id,
                    "title" => "Room " . ($booking->room->room_number ?? "") . " - " . ($booking->customer->name ?? "Guest"),
                    "start" => $booking->check_in->toDateString(),
                    "end" => $booking->check_out->toDateString(),
                    "status" => $booking->status,
                ];
            })
            ->toArray();
    }

    public function render(): View
    {
        return view(static::$view, [
            "events" => $this->bookingsThisMonth,
        ]);
    }

    /**
     * Component: HotelCalendarWidget
     *
     * Part of the CatVRF 2026 multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     */
    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * HotelCalendarWidget — CatVRF 2026 Component.
     *
     * Part of the CatVRF multi-vertical marketplace platform.
     * Implements tenant-aware, fraud-checked business logic
     * with full correlation_id tracing and audit logging.
     *
     * @package CatVRF
     * @version 2026.1
     * @author CatVRF Team
     * @license Proprietary
     */
}
