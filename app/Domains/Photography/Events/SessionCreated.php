<?php

declare(strict_types=1);

namespace App\Domains\Photography\Events;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionCreated
{
	use Dispatchable, InteractsWithSockets, SerializesModels;

	public function __construct(
		public readonly PhotoSession $session,
		public readonly string $correlationId
	) {}
}
