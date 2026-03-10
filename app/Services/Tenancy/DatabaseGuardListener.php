<?php
namespace App\Services\Tenancy;

use Stancl\Tenancy\Listeners\BootstrapTenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DatabaseGuardListener
{
    public function handle($event)
    {
        $tenantId = $event->tenancy->tenant->id ?? 'unknown';
        
        // Ensure no cross-polling of sessions or central leaks
        DB::purge('central');
        
        // Log isolation boundary bridge
        Log::info("ISOLATION_GUARD: Booted tenant boundary", [
            'tenant' => $tenantId,
            'correlation_id' => request()->header('X-Correlation-ID')
        ]);
    }
}
