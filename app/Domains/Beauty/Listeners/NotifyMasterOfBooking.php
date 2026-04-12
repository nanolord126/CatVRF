<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Listeners;
use Illuminate\Bus\Queueable;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Domains\Beauty\Events\AppointmentBooked;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Mail\Mailer;

final class NotifyMasterOfBooking implements ShouldQueue
{
    use InteractsWithQueue;

    public function __construct(
        private readonly Mailer $mailer
    ) {}

    public function handle(AppointmentBooked $event): void
    {
        $appointment = $event->appointment;
        $master = $appointment->master;
        
        // Notify master (dummy implementation for demonstration)
        // $this->mailer->to($master->user->email)->send(...);
    }
}
