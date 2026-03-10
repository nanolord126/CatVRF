<?php

namespace App\Filament\Tenant\Widgets;

use Saade\FilamentFullCalendar\Widgets\FullCalendarWidget;
use Modules\BeautyMasters\Models\Appointment;
use Filament\Forms\Components;
use Illuminate\Database\Eloquent\Model;

class MasterAppointmentCalendarWidget extends FullCalendarWidget
{
    public Model | string | null $model = Appointment::class;

    public function fetchEvents(array $fetchInfo): array
    {
        return Appointment::query()
            ->with('master')
            ->where('start_time', '>=', $fetchInfo['start'])
            ->where('end_time', '<=', $fetchInfo['end'])
            ->get()
            ->map(function (Appointment $appointment) {
                return [
                    'id'    => $appointment->id,
                    'title' => ($appointment->master ? $appointment->master->name : 'Appointment') . ' - ' . $appointment->service_name,
                    'start' => $appointment->start_time,
                    'end'   => $appointment->end_time,
                    'color' => match($appointment->status) {
                        'confirmed' => '#10b981',
                        'completed' => '#6366f1',
                        'cancelled' => '#ef4444',
                        default     => '#f59e0b',
                    },
                ];
            })
            ->all();
    }

    public function getFormSchema(): array
    {
        return [
            Components\Select::make('master_id')
                ->relationship('master', 'name')
                ->required(),
            Components\TextInput::make('service_name')
                ->required(),
            Components\TextInput::make('client_name')
                ->required(),
            Components\DateTimePicker::make('start_time')
                ->required(),
            Components\DateTimePicker::make('end_time')
                ->required(),
            Components\Select::make('status')
                ->options([
                    'pending' => 'Pending',
                    'confirmed' => 'Confirmed',
                    'completed' => 'Completed',
                    'cancelled' => 'Cancelled',
                ])
                ->default('pending'),
        ];
    }
}
