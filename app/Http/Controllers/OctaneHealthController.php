<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Octane\Services\SwooleTableService;
use App\Octane\Services\PrometheusSwooleExporter;
use Illuminate\Http\JsonResponse;
use Laravel\Octane\Facades\Octane;
use Swoole\Coroutine;

final readonly class OctaneHealthController
{
    public function __construct(
        private readonly SwooleTableService $tableService,
        private readonly PrometheusSwooleExporter $prometheusExporter
    ) {}

    public function index(): JsonResponse
    {
        return new JsonResponse([
            'status' => function_exists('swoole_server') ? 'running' : 'not_installed',
            'tables' => $this->tableService->getStats(),
            'coroutines' => $this->getCoroutineStats(),
            'workers' => $this->getWorkerStats(),
        ]);
    }

    public function metrics(): JsonResponse
    {
        return new JsonResponse([
            'metrics' => $this->prometheusExporter->getMetricsText(),
        ]);
    }

    public function tables(): JsonResponse
    {
        return new JsonResponse([
            'tables' => $this->tableService->getStats(),
        ]);
    }

    private function getCoroutineStats(): array
    {
        if (! function_exists('Swoole\Coroutine::stats')) {
            return ['status' => 'not_available'];
        }

        return Coroutine::stats();
    }

    private function getWorkerStats(): array
    {
        $server = Octane::server();

        if (! $server || ! method_exists($server, 'stats')) {
            return ['status' => 'not_available'];
        }

        return $server->stats();
    }
}
