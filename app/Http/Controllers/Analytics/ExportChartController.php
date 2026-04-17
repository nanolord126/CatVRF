<?php declare(strict_types=1);

namespace App\Http\Controllers\Analytics;

use App\Http\Controllers\Controller;
use Illuminate\Log\LogManager;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\Routing\ResponseFactory;

final class ExportChartController extends Controller
{
    public function __construct(
        private readonly LogManager $logger,
        private readonly Guard $guard,
        private readonly ResponseFactory $response,
    ) {}



    /**
         * Экспортировать график в PNG
         *
         * POST /api/analytics/export/png
         */
        public function exportPng(Request $request): Response
        {
            try {
                $validated = $request->validate([
                    'chart_image' => 'required|string', // Base64 image data
                    'filename' => 'nullable|string|max:255',
                ]);
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();
                // Base64 decode image
                if (!preg_match('/^data:image\/png;base64,/', $validated['chart_image'])) {
                    $this->logger->channel('error')->error('Invalid PNG data format', [
                        'correlation_id' => $correlationId,
                        'provided_format' => substr($validated['chart_image'], 0, 50),
                    ]);
                    return $this->response->json(['error' => 'Invalid image format'], 400);
                }
                $imageData = base64_decode(
                    preg_replace('/^data:image\/png;base64,/', '', $validated['chart_image']),
                    true
                );
                if ($imageData === false) {
                    throw new \RuntimeException('Failed to decode PNG image');
                }
                $filename = $validated['filename'] ?? 'chart-' . now()->format('Y-m-d-H-i-s') . '.png';
                $this->logger->channel('audit')->info('Chart PNG exported', [
                    'correlation_id' => $correlationId,
                    'filename' => $filename,
                    'size_bytes' => strlen($imageData),
                    'tenant_id' => $this->guard->user()->current_tenant_id,
                ]);
                return $this->response->make($imageData)
                    ->header('Content-Type', 'image/png')
                    ->header('Content-Disposition', "attachment; filename=\"{$filename}\"")
                    ->header('X-Correlation-ID', $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('PNG export failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json(['error' => 'Export failed: ' . $e->getMessage()], 500)
                    ->header('X-Correlation-ID', $request->header('X-Correlation-ID'));
            }
        }
        /**
         * Экспортировать график в PDF с метаданными
         *
         * POST /api/analytics/export/pdf
         */
        public function exportPdf(Request $request): Response
        {
            try {
                $validated = $request->validate([
                    'chart_data' => 'required|array',
                    'title' => 'nullable|string|max:255',
                    'description' => 'nullable|string|max:1000',
                    'metadata' => 'nullable|array',
                    'chart_image' => 'nullable|string', // Base64 PNG
                ]);
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();
                $tenant = $this->guard->user()->currentTenant;
                // Подготовить данные для PDF
                $pdfData = [
                    'title' => $validated['title'] ?? 'Analytics Report',
                    'description' => $validated['description'] ?? '',
                    'tenant_name' => $tenant->name ?? 'N/A',
                    'generated_at' => now()->format('d.m.Y H:i:s'),
                    'correlation_id' => $correlationId,
                    'chart_data' => $validated['chart_data'] ?? [],
                    'metadata' => $validated['metadata'] ?? [],
                    'chart_image' => $validated['chart_image'] ?? null,
                ];
                // Генерировать PDF
                $pdf = Pdf::loadView('exports.chart-pdf', $pdfData)
                    ->setOption('defaultFont', 'sans-serif')
                    ->setOption('margin-top', 10)
                    ->setOption('margin-bottom', 10)
                    ->setOption('margin-left', 10)
                    ->setOption('margin-right', 10);
                $filename = $validated['title'] ?? 'analytics-report';
                $filename = Str::slug($filename) . '-' . now()->format('Y-m-d-H-i-s') . '.pdf';
                $this->logger->channel('audit')->info('Chart PDF exported', [
                    'correlation_id' => $correlationId,
                    'filename' => $filename,
                    'title' => $pdfData['title'],
                    'tenant_id' => $tenant->id,
                ]);
                return $pdf->download($filename)
                    ->header('X-Correlation-ID', $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('PDF export failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                return $this->response->json(['error' => 'Export failed: ' . $e->getMessage()], 500)
                    ->header('X-Correlation-ID', $request->header('X-Correlation-ID'));
            }
        }
        /**
         * Экспортировать через Browsershot (более качественный PNG)
         * Требует установленного Chrome/Chromium
         */
        public function exportPngBrowsershot(Request $request): Response
        {
            try {
                $validated = $request->validate([
                    'html_content' => 'required|string',
                    'filename' => 'nullable|string|max:255',
                    'width' => 'nullable|integer|min:400|max:2000',
                    'height' => 'nullable|integer|min:300|max:2000',
                ]);
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();
                $width = $validated['width'] ?? 1200;
                $height = $validated['height'] ?? 600;
                // Использовать Browsershot для высокого качества
                // ТРЕБУЕТ установленного Chrome: https://github.com/spatie/browsershot
                // npm install puppeteer (or install Chrome system-wide)
                // Для простоты - используем базовый PNG экспорт
                // В production используйте:
                // $image = Browsershot::html($validated['html_content'])
                //     ->setNodeBinary('/usr/bin/node')
                //     ->setNpmBinary('/usr/bin/npm')
                //     ->windowSize($width, $height)
                //     ->screenshot();
                $this->logger->channel('audit')->info('Browsershot PNG export attempted', [
                    'correlation_id' => $correlationId,
                    'width' => $width,
                    'height' => $height,
                ]);
                return $this->response->json(['error' => 'Browsershot PNG export requires Chrome installation. Use basic PNG export instead.'], 501)
                    ->header('X-Correlation-ID', $correlationId);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('Browsershot PNG export failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'message' => $e->getMessage(),
                ]);
                return $this->response->json(['error' => 'Browsershot export failed: ' . $e->getMessage()], 500);
            }
        }
        /**
         * Быстрый экспорт - сохранить PNG в storage и вернуть URL
         */
        public function quickExport(Request $request): \Illuminate\Http\JsonResponse
        {
            try {
                $validated = $request->validate([
                    'chart_image' => 'required|string',
                    'export_type' => 'required|in:png,pdf',
                ]);
                $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid();
                $tenant = $this->guard->user()->currentTenant;
                $filename = 'analytics/' . $tenant->id . '/' . now()->format('Y-m-d') . '/'
                    . Str::uuid() . '.' . $validated['export_type'];
                if ($validated['export_type'] === 'png') {
                    $imageData = base64_decode(
                        preg_replace('/^data:image\/png;base64,/', '', $validated['chart_image']),
                        true
                    );
                    Storage::disk('public')->put($filename, $imageData);
                } elseif ($validated['export_type'] === 'pdf') {
                    $filename = str_replace('.pdf', '', $filename) . '.pdf';
                }
                $url = Storage::disk('public')->url($filename);
                $this->logger->channel('audit')->info('Quick export completed', [
                    'correlation_id' => $correlationId,
                    'type' => $validated['export_type'],
                    'filename' => $filename,
                    'url' => $url,
                    'tenant_id' => $tenant->id,
                ]);
                return $this->response->json([
                    'success' => true,
                    'url' => $url,
                    'filename' => basename($filename),
                    'correlation_id' => $correlationId,
                ]);
            } catch (\Exception $e) {
                $this->logger->channel('audit')->error($e->getMessage(), [
                    'exception' => $e::class,
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'correlation_id' => request()->header('X-Correlation-ID'),
                ]);

                $this->logger->channel('error')->error('Quick export failed', [
                    'correlation_id' => $request->header('X-Correlation-ID'),
                    'message' => $e->getMessage(),
                ]);
                return $this->response->json([
                    'success' => false,
                    'error' => 'Export failed: ' . $e->getMessage(),
                    'correlation_id' => $request->header('X-Correlation-ID'),
                ], 500);
            }
        }
}
