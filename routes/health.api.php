<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\HealthController;

/*
|--------------------------------------------------------------------------
| Health Check API Routes
|--------------------------------------------------------------------------
|
| Health check endpoints for monitoring, load balancers, and smoke tests.
| These endpoints are used by:
| - Load balancers (Traefik/Nginx) for health checks
| - Kubernetes/Docker for readiness/liveness probes
| - Deployment scripts for smoke tests
| - Monitoring systems (Prometheus/Grafana)
|
*/

// Basic health check (returns 200 if app is running)
Route::get('/health', HealthController::class);

// Detailed health check with all dependencies
Route::get('/health/detailed', [HealthController::class, 'detailed']);

// Smoke tests for critical business flows
Route::get('/health/smoke', [HealthController::class, 'smoke']);

// Readiness probe (Kubernetes/Docker)
Route::get('/health/readiness', [HealthController::class, 'readiness']);

// Liveness probe (Kubernetes/Docker)
Route::get('/health/liveness', [HealthController::class, 'liveness']);
