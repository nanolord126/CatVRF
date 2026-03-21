<?php declare(strict_types=1);

namespace App\Domains\HomeServices\Services;

use App\Domains\HomeServices\Models\ServiceJob;
use App\Domains\HomeServices\Events\ServiceJobCreated;
use Illuminate\Support\Str;

final class JobService
{
    public function __construct() {}

    public function createJob(
        int $serviceListingId,
        int $clientId,
        string $address,
        string $description,
        string $correlationId
    ): ServiceJob {
        try {
            return \DB::transaction(function () use ($serviceListingId, $clientId, $address, $description, $correlationId) {
                $listing = \App\Domains\HomeServices\Models\ServiceListing::findOrFail($serviceListingId);
                
                $baseAmount = $listing->base_price;
                $commissionAmount = round($baseAmount * 0.14, 2);
                $totalAmount = $baseAmount + $commissionAmount;

                $job = ServiceJob::create([
                    'tenant_id' => tenant('id'),
                    'service_listing_id' => $serviceListingId,
                    'contractor_id' => $listing->contractor_id,
                    'client_id' => $clientId,
                    'status' => 'pending',
                    'description' => $description,
                    'address' => $address,
                    'base_amount' => $baseAmount,
                    'commission_amount' => $commissionAmount,
                    'total_amount' => $totalAmount,
                    'payment_status' => 'pending',
                    'correlation_id' => $correlationId,
                ]);

                ServiceJobCreated::dispatch($job, $correlationId);

                \Log::channel('audit')->info('Service job created', [
                    'job_id' => $job->id,
                    'contractor_id' => $listing->contractor_id,
                    'client_id' => $clientId,
                    'amount' => $totalAmount,
                    'correlation_id' => $correlationId,
                ]);

                return $job;
            });
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to create service job', ['error' => $e->getMessage(), 'correlation_id' => $correlationId]);
            throw $e;
        }
    }

    public function completeJob(ServiceJob $job, string $correlationId): void
    {
        try {
            \DB::transaction(function () use ($job, $correlationId) {
                $job->update([
                    'status' => 'completed',
                    'completed_at' => now(),
                    'payment_status' => 'paid',
                    'correlation_id' => $correlationId,
                ]);

                \Log::channel('audit')->info('Service job completed', [
                    'job_id' => $job->id,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to complete job', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    public function cancelJob(ServiceJob $job, string $reason, string $correlationId): void
    {
        try {
            \DB::transaction(function () use ($job, $reason, $correlationId) {
                $job->update([
                    'status' => 'cancelled',
                    'correlation_id' => $correlationId,
                    'notes' => $reason,
                ]);

                \Log::channel('audit')->info('Service job cancelled', [
                    'job_id' => $job->id,
                    'reason' => $reason,
                    'correlation_id' => $correlationId,
                ]);
            });
        } catch (\Throwable $e) {
            \Log::channel('audit')->error('Failed to cancel job', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
