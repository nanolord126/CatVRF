<?php

namespace App\Filament\Tenant\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Modules\Hotels\Models\Booking;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Model;

class HotelCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Booking::class;

    /**
     * Fetch events for the calendar.
     */
    public function fetchEvents(array $fetchInfo): array
    {
        return Booking::query()
            ->with('room')
            ->where('check_in', '>=', $fetchInfo['start'])
            ->where('check_out', '<=', $fetchInfo['end'])
            ->get()
            ->map(function (Booking $booking) {
                return [
                    'id'    => $booking->id,
                    'title' => ($booking->room ? 'Room ' . $booking->room->number : 'Booking') . ' - ' . ucfirst($booking->status),
                    'start' => $booking->check_in,
                    'end'   => $booking->check_out,
                    'color' => match($booking->status) {
                        'confirmed' => '#10b981', // green
                        'pending'   => '#f59e0b', // orange
                        'cancelled' => '#ef4444', // red
                        default     => '#6366f1', // indigo
                    },
                ];
            })
            ->all();
    }

    /**
     * Schema for event creation/edit (modal).
     */
    public function getFormSchema(): array
    {
        return [
            Components\Select::make('room_id')
                ->relationship('room', 'number')
                ->searchable()
                ->preload()
                ->required(),
            Components\DateTimePicker::make('check_in')
                ->required(),
            Components\DateTimePicker::make('check_out')
                ->required(),
            Components\TextInput::make('total_price')
                ->numeric()
                ->prefix('₽')
                ->required(),
            Components\Select::make('status')
                ->options([
                    'pending'   => 'Pending',
                    'confirmed' => 'Confirmed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('pending')
                ->required(),
        ];
    }
}
