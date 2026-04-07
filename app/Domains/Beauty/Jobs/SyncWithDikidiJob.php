<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;



use Carbon\Carbon;
use Psr\Log\LoggerInterface;
use Illuminate\Config\Repository as ConfigRepository;

final class SyncWithDikidiJob
{


    use Dispatchable;
        use InteractsWithQueue;
        use Queueable;
        use SerializesModels;

        public function __construct(
            private int $tenantId,
            private string $correlationId,
            private ConfigRepository $config,
            private LoggerInterface $logger,
        ) {}

        public function handle(\Illuminate\Http\Client\Factory $http): void
        {
            $tenant = \App\Models\Tenant::findOrFail($this->tenantId);
            $dikidiApiKey = $this->config->get('integrations.dikidi.api_key');

            if (!$dikidiApiKey || !$tenant->dikidi_business_id) {
                $this->logger->warning('Dikidi sync skipped - no credentials', [
                    'tenant_id' => $this->tenantId,
                    'correlation_id' => $this->correlationId,
                ]);
                return;
            }

            try {
                $response = $http->withHeaders([
                    'Authorization' => "Bearer {$dikidiApiKey}",
                    'Accept' => 'application/json',
                ])->get("https://api.dikidi.net/v1/business/{$tenant->dikidi_business_id}/appointments", [
                    'from' => Carbon::now()->subDays(7)->toDateString(),
                    'to' => Carbon::now()->toDateString(),
                ]);

                if ($response->successful()) {
                    $appointments = $response->json('data', []);

                    foreach ($appointments as $dikidiAppointment) {
                        // Sync or create appointment from Dikidi
                        \App\Domains\Beauty\Models\Appointment::updateOrCreate(
                            ['dikidi_id' => $dikidiAppointment['id'], 'tenant_id' => $this->tenantId],
                            [
                                'status' => $dikidiAppointment['status'],
                                'datetime_start' => $dikidiAppointment['datetime'],
                                'synced_at' => Carbon::now(),
                            ]
                        );
                    }

                    $this->logger->info('Dikidi sync completed', [
                        'tenant_id' => $this->tenantId,
                        'synced_count' => count($appointments),
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Throwable $e) {
                $this->logger->error('Dikidi sync failed', [
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}
