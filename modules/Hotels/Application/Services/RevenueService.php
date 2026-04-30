<?php

declare(strict_types=1);

namespace Modules\Hotels\Application\Services;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Hotels\Infrastructure\Models\BookingModel;
use Modules\Hotels\Infrastructure\Models\VenueModel;

final class RevenueService
{
    public function getOccupancyRate(int $venueId, ?CarbonImmutable $date = null): float
    {
        $date = $date ?? CarbonImmutable::now();
        $venue = VenueModel::findOrFail($venueId);

        $occupiedRooms = BookingModel::where('venue_id', $venueId)
            ->where('status', '!=', 'cancelled')
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->join('hotels_booking_items', 'hotels_bookings.id', '=', 'hotels_booking_items.booking_id')
            ->distinct()
            ->count('hotels_booking_items.room_id');

        $totalRooms = $venue->total_rooms;

        if ($totalRooms === 0) {
            return 0.0;
        }

        return round(($occupiedRooms / $totalRooms) * 100, 2);
    }

    public function getOccupancyRateRange(
        int $venueId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $venue = VenueModel::findOrFail($venueId);
        $totalRooms = $venue->total_rooms;

        if ($totalRooms === 0) {
            return [];
        }

        $dates = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $occupiedRooms = BookingModel::where('venue_id', $venueId)
                ->where('status', '!=', 'cancelled')
                ->where('check_in_date', '<=', $currentDate)
                ->where('check_out_date', '>', $currentDate)
                ->join('hotels_booking_items', 'hotels_bookings.id', '=', 'hotels_booking_items.booking_id')
                ->distinct()
                ->count('hotels_booking_items.room_id');

            $dates[] = [
                'date' => $currentDate->toDateString(),
                'occupancy_rate' => round(($occupiedRooms / $totalRooms) * 100, 2),
                'occupied_rooms' => $occupiedRooms,
                'total_rooms' => $totalRooms,
            ];

            $currentDate = $currentDate->addDay();
        }

        return $dates;
    }

    public function getRevenueForPeriod(
        int $venueId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $revenue = BookingModel::where('venue_id', $venueId)
            ->whereBetween('check_out_date', [$startDate, $endDate])
            ->where('status', '!=', 'cancelled')
            ->selectRaw('
                DATE(check_out_date) as date,
                COUNT(*) as bookings,
                SUM(total_amount) as total_revenue,
                SUM(paid_amount) as paid_revenue,
                AVG(total_amount) as avg_booking_value
            ')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();

        return $revenue;
    }

    public function getAdr(int $venueId, CarbonImmutable $date): float
    {
        // ADR = Room Revenue / Number of Sold Rooms
        $revenue = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_out_date', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $soldRooms = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_out_date', $date)
            ->where('status', '!=', 'cancelled')
            ->join('hotels_booking_items', 'hotels_bookings.id', '=', 'hotels_booking_items.booking_id')
            ->count();

        if ($soldRooms === 0) {
            return 0.0;
        }

        return round($revenue / $soldRooms, 2);
    }

    public function getRevPar(int $venueId, CarbonImmutable $date): float
    {
        // RevPAR = Room Revenue / Total Available Rooms
        $revenue = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_out_date', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $venue = VenueModel::findOrFail($venueId);
        $totalRooms = $venue->total_rooms;

        if ($totalRooms === 0) {
            return 0.0;
        }

        return round($revenue / $totalRooms, 2);
    }

    public function getDashboardStats(int $venueId, CarbonImmutable $date): array
    {
        $venue = VenueModel::findOrFail($venueId);

        $todayOccupancy = $this->getOccupancyRate($venueId, $date);
        $adr = $this->getAdr($venueId, $date);
        $revPar = $this->getRevPar($venueId, $date);

        $todayRevenue = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_out_date', $date)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $monthRevenue = BookingModel::where('venue_id', $venueId)
            ->whereYear('check_out_date', $date->year)
            ->whereMonth('check_out_date', $date->month)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        $checkInsToday = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_in_date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();

        $checkOutsToday = BookingModel::where('venue_id', $venueId)
            ->whereDate('check_out_date', $date)
            ->where('status', '!=', 'cancelled')
            ->count();

        $stayingGuests = BookingModel::where('venue_id', $venueId)
            ->where('status', 'checked_in')
            ->count();

        return [
            'occupancy_rate' => $todayOccupancy,
            'adr' => $adr,
            'revpar' => $revPar,
            'today_revenue' => $todayRevenue,
            'month_revenue' => $monthRevenue,
            'check_ins_today' => $checkInsToday,
            'check_outs_today' => $checkOutsToday,
            'staying_guests' => $stayingGuests,
            'total_rooms' => $venue->total_rooms,
            'available_rooms' => $venue->total_rooms - $stayingGuests,
            'dirty_rooms' => $this->getDirtyRoomsCount($venueId),
            'maintenance_rooms' => $this->getMaintenanceRoomsCount($venueId),
        ];
    }

    public function getForecast(
        int $venueId,
        CarbonImmutable $startDate,
        CarbonImmutable $endDate,
    ): array {
        $venue = VenueModel::findOrFail($venueId);
        $totalRooms = $venue->total_rooms;

        $forecast = [];
        $currentDate = $startDate;

        while ($currentDate <= $endDate) {
            $confirmedBookings = BookingModel::where('venue_id', $venueId)
                ->where('status', '!=', 'cancelled')
                ->where('check_in_date', '<=', $currentDate)
                ->where('check_out_date', '>', $currentDate)
                ->join('hotels_booking_items', 'hotels_bookings.id', '=', 'hotels_booking_items.booking_id')
                ->distinct()
                ->count('hotels_booking_items.room_id');

            $pendingBookings = BookingModel::where('venue_id', $venueId)
                ->where('status', 'pending')
                ->where('check_in_date', '<=', $currentDate)
                ->where('check_out_date', '>', $currentDate)
                ->join('hotels_booking_items', 'hotels_bookings.id', '=', 'hotels_booking_items.booking_id')
                ->distinct()
                ->count('hotels_booking_items.room_id');

            $forecastedRevenue = BookingModel::where('venue_id', $venueId)
                ->whereDate('check_out_date', $currentDate)
                ->where('status', '!=', 'cancelled')
                ->sum('total_amount');

            $forecast[] = [
                'date' => $currentDate->toDateString(),
                'confirmed_occupancy' => round(($confirmedBookings / $totalRooms) * 100, 2),
                'pending_occupancy' => round((($confirmedBookings + $pendingBookings) / $totalRooms) * 100, 2),
                'confirmed_rooms' => $confirmedBookings,
                'pending_rooms' => $pendingBookings,
                'available_rooms' => $totalRooms - $confirmedBookings - $pendingBookings,
                'forecasted_revenue' => $forecastedRevenue,
            ];

            $currentDate = $currentDate->addDay();
        }

        return $forecast;
    }

    private function getDirtyRoomsCount(int $venueId): int
    {
        return DB::table('hotels_rooms')
            ->where('venue_id', $venueId)
            ->where('clean_status', 'dirty')
            ->count();
    }

    private function getMaintenanceRoomsCount(int $venueId): int
    {
        return DB::table('hotels_rooms')
            ->where('venue_id', $venueId)
            ->where('status', 'maintenance')
            ->count();
    }
}
