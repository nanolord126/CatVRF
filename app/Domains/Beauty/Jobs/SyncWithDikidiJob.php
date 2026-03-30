<?php declare(strict_types=1);

namespace App\Domains\Beauty\Jobs;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SyncWithDikidiJob extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
                Log::channel('audit')->warning('Dikidi sync skipped - no credentials', [
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

                    Log::channel('audit')->info('Dikidi sync completed', [
                        'tenant_id' => $this->tenantId,
                        'synced_count' => count($appointments),
                        'correlation_id' => $this->correlationId,
                    ]);
                }
            } catch (\Exception $e) {
                Log::channel('audit')->error('Dikidi sync failed', [
                    'tenant_id' => $this->tenantId,
                    'error' => $e->getMessage(),
                    'correlation_id' => $this->correlationId,
                ]);
                throw $e;
            }
        }
}
