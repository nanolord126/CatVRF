<?php

declare(strict_types=1);

namespace App\Domains\Photography\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;


use App\Domains\Photography\Models\B2BPhotoStorefront;
use App\Domains\Photography\Models\B2BPhotoOrder;
use Illuminate\Support\Facades\DB;

final readonly class B2BService
{
	public function createStorefront(array $data): B2BPhotoStorefront
	{
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

		return $this->db->transaction(function () use ($data) {
			$correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

			$storefront = B2BPhotoStorefront::create([
				'uuid' => Str::uuid(),
				'tenant_id' => $data['tenant_id'],
				'company_name' => $data['company_name'],
				'inn' => $data['inn'],
				'description' => $data['description'] ?? null,
				'corporate_packages' => $data['corporate_packages'] ?? null,
				'corporate_rate' => $data['corporate_rate'] ?? null,
				'min_booking_hours' => $data['min_booking_hours'] ?? 4,
				'is_verified' => false,
				'correlation_id' => $correlationId,
			]);

			$this->log->channel('audit')->info('Photography B2B: Storefront created', [
				'storefront_id' => $storefront->id,
				'inn' => $data['inn'],
				'correlation_id' => $correlationId,
			]);

			return $storefront;
		});
	}

	public function createB2BOrder(array $data): B2BPhotoOrder
	{
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

		return $this->db->transaction(function () use ($data) {
			$correlationId = $data['correlation_id'] ?? Str::uuid()->toString();

			$order = B2BPhotoOrder::create([
				'uuid' => Str::uuid(),
				'tenant_id' => $data['tenant_id'],
				'b2b_photo_storefront_id' => $data['b2b_photo_storefront_id'],
				'photographer_id' => $data['photographer_id'],
				'order_number' => 'B2B-' . Str::random(8),
				'company_contact_person' => $data['company_contact_person'],
				'company_phone' => $data['company_phone'],
				'datetime_start' => $data['datetime_start'],
				'duration_hours' => $data['duration_hours'],
				'total_amount' => $data['total_amount'],
				'commission_amount' => (int) ($data['total_amount'] * 0.14),
				'status' => 'pending',
				'correlation_id' => $correlationId,
			]);

			$this->log->channel('audit')->info('Photography B2B: Order created', [
				'order_id' => $order->id,
				'amount' => $data['total_amount'],
				'correlation_id' => $correlationId,
			]);

			return $order;
		});
	}

	public function approveB2BOrder(B2BPhotoOrder $order): B2BPhotoOrder
	{
        $correlationId = Str::uuid()->toString();
        $this->log->channel('audit')->info('Service method called in Photography', ['correlation_id' => $correlationId]);

		return $this->db->transaction(function () use ($order) {
			$order->update(['status' => 'approved']);

			$this->log->channel('audit')->info('Photography B2B: Order approved', [
				'order_id' => $order->id,
				'correlation_id' => $order->correlation_id,
			]);

			return $order;
		});
	}
}
