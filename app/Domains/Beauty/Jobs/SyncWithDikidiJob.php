<?php

declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final class SyncWithDikidiJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        private readonly int $tenantId,
        private readonly string $correlationId,
    ) {}

    public function handle(): void
    {
        $tenant = \App\Models\Tenant::findOrFail($this->tenantId);
        $dikidiApiKey = config('integrations.dikidi.api_key');
        
        if (!$dikidiApiKey || !$tenant->dikidi_business_id) {
            $this->log->channel('audit')->warning('Dikidi sync skipped - no credentials', [
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
            ]);
            return;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$dikidiApiKey}",
                'Accept' => 'application/json',
            ])->get("https://api.dikidi.net/v1/business/{$tenant->dikidi_business_id}/appointments", [
                'from' => now()->subDays(7)->toDateString(),
                'to' => now()->toDateString(),
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
                            'synced_at' => now(),
                        ]
                    );
                }

                $this->log->channel('audit')->info('Dikidi sync completed', [
                    'tenant_id' => $this->tenantId,
                    'synced_count' => count($appointments),
                    'correlation_id' => $this->correlationId,
                ]);
            }
        } catch (\Exception $e) {
            $this->log->channel('audit')->error('Dikidi sync failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'correlation_id' => $this->correlationId,
            ]);
            throw $e;
        }
    }
}
