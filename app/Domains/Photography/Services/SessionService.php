<?php

declare(strict_types=1);

namespace App\Domains\Photography\Services;

use App\Domains\Photography\Models\PhotoSession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final readonly class SessionService
{
	public function createSession(array $data): PhotoSession
	{
		return DB::transaction(function () use ($data) {
			$correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

			$session = PhotoSession::create([
				'uuid' => Str::uuid(),
				'tenant_id' => $data['tenant_id'],
				'photo_studio_id' => $data['photo_studio_id'],
				'photographer_id' => $data['photographer_id'],
				'photo_package_id' => $data['photo_package_id'],
				'user_id' => $data['user_id'],
				'session_number' => 'SES-' . Str::random(8),
				'datetime_start' => $data['datetime_start'],
				'datetime_end' => $data['datetime_end'],
				'total_amount' => $data['total_amount'],
				'commission_amount' => (int) ($data['total_amount'] * 0.14),
				'status' => 'pending',
				'correlation_id' => $correlationId,
			]);

			Log::channel('audit')->info('Photography: Session created', [
				'session_id' => $session->id,
				'tenant_id' => $session->tenant_id,
				'amount' => $data['total_amount'],
				'correlation_id' => $correlationId,
			]);

			return $session;
		});
	}

	public function updateSessionStatus(PhotoSession $session, string $status): PhotoSession
	{
		return DB::transaction(function () use ($session, $status) {
			$session->update(['status' => $status]);

			Log::channel('audit')->info('Photography: Session status updated', [
				'session_id' => $session->id,
				'status' => $status,
				'correlation_id' => $session->correlation_id,
			]);

			return $session;
		});
	}

	public function cancelSession(PhotoSession $session): void
	{
		DB::transaction(function () use ($session) {
			$session->update(['status' => 'cancelled']);

			Log::channel('audit')->info('Photography: Session cancelled', [
				'session_id' => $session->id,
				'correlation_id' => $session->correlation_id,
			]);
		});
	}
}
