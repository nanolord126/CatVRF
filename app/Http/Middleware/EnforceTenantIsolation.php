<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;

class EnforceTenantIsolation {
    public function handle($request, Closure $next) {
        // Zero Trust Tracing
        $correlationId = $request->header('X-Correlation-ID') ?? (string) Str::uuid();
        Context::add('correlation_id', $correlationId);
        Context::add('client_ip', $request->ip());

        if ($tenant = tenant()) {
            DB::statement("SET search_path TO " . $tenant->getInternal('schema') . ", public");
            // Audit log check for tenant isolation
            logger()->channel('audit')->info("Accessing tenant " . $tenant->id, [
                'user_id' => auth()->id(),
                'ip' => $request->ip(),
                'correlation_id' => $correlationId
            ]);
        }
        
        $response = $next($request);
        
        // Return tracing to client
        $response->headers->set('X-Correlation-ID', $correlationId);
        
        return $response;
    }
}