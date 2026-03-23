<?php

declare(strict_types=1);

namespace App\Domains\Analytics\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * HeatmapExportService - Handles PNG and PDF export of heatmap visualizations
 *
 * Responsibilities:
 * 1. Receive heatmap data (HTML/Canvas content) from frontend
 * 2. Convert to PNG using server-side rendering (html2canvas alternative)
 * 3. Convert to PDF using DOMPDF
 * 4. Store exports in S3/disk storage with TTL
 * 5. Generate signed URLs for secure file access
 *
 * Uses queue for large exports to prevent request timeout.
 *
 * @uses Storage facade for file management
 * @uses DOMPDF for PDF generation
 * @uses Server-side screenshot capture (Chromium/Puppeteer)
 *
 * @package App\Domains\Analytics\Services
 */
final class HeatmapExportService
{
    /**
     * @var string Default storage disk for exports
     */
    private string $disk = 's3';

    /**
     * @var string Storage path prefix for heatmap exports
     */
    private string $storagePath = 'heatmap-exports';

    /**
     * @var int Export file TTL in seconds (24 hours)
     */
    private int $fileTtl = 86400;

    /**
     * @var int Maximum export file size (50MB)
     */
    private int $maxFileSize = 52428800;

    /**
     * @var array Supported export formats
     */
    private array $supportedFormats = ['png', 'pdf'];

    /**
     * Create a new HeatmapExportService instance.
     *
     * @param string|null $disk Storage disk override (optional)
     */
    public function __construct(?string $disk = null)
    {
        if ($disk) {
            $this->disk = $disk;
        }
    }

    /**
     * Export geo-heatmap to PNG.
     *
     * Takes geo-heatmap HTML/Canvas content and converts to PNG image.
     * Suitable for reports, emails, and storage.
     *
     * @param int $tenantId Tenant ID for directory organization
     * @param string $heatmapHtml HTML content of the heatmap visualization
     * @param array $metadata Export metadata (title, date range, vertical, etc.)
     * @param string|null $correlationId Correlation ID for tracing
     * @return array Export result: {url, filename, size, generated_at, expires_at}
     *
     * @throws \InvalidArgumentException If HTML is empty or invalid
     * @throws \RuntimeException If export generation fails
     */
    public function exportGeoHeatmapToPng(
        int $tenantId,
        string $heatmapHtml,
        array $metadata = [],
        ?string $correlationId = null
    ): array {
        $correlationId ??= "export-{$this->generateTraceId()}";

        try {
            // Validate input
            if (empty($heatmapHtml)) {
                throw new \InvalidArgumentException('Heatmap HTML content cannot be empty');
            }

            // Generate filename
            $filename = $this->generateFilename('geo-heatmap', 'png', $tenantId);
            $storagePath = "{$this->storagePath}/{$tenantId}/{$filename}";

            Log::channel('audit')->info('Starting geo-heatmap PNG export', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'metadata' => $metadata,
                'correlation_id' => $correlationId,
            ]);

            // Convert HTML to PNG using server-side rendering
            $pngContent = $this->htmlToPng($heatmapHtml, $metadata);

            // Validate file size
            $fileSize = \strlen($pngContent);
            if ($fileSize > $this->maxFileSize) {
                throw new \RuntimeException(
                    "Exported file size ({$fileSize} bytes) exceeds maximum ({$this->maxFileSize} bytes)"
                );
            }

            // Store file
            Storage::disk($this->disk)->put(
                $storagePath,
                $pngContent,
                ['visibility' => 'private'] // Private visibility for security
            );

            // Generate signed URL (expires in 24 hours)
            $url = Storage::disk($this->disk)->temporaryUrl(
                $storagePath,
                \now()->addSeconds($this->fileTtl)
            );

            Log::channel('audit')->info('Geo-heatmap PNG export completed', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'storage_path' => $storagePath,
                'correlation_id' => $correlationId,
            ]);

            return [
                'url' => $url,
                'filename' => $filename,
                'size' => $fileSize,
                'format' => 'png',
                'generated_at' => \now()->toIso8601String(),
                'expires_at' => \now()->addSeconds($this->fileTtl)->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

        } catch (\Exception $e) {
            Log::channel('audit')->error('Geo-heatmap PNG export failed', [
                'tenant_id' => $tenantId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Export geo-heatmap to PDF.
     *
     * Generates a professional PDF report with heatmap visualization,
     * statistics, and metadata.
     *
     * @param int $tenantId Tenant ID for directory organization
     * @param string $heatmapHtml HTML content of the heatmap
     * @param array $metadata Report metadata (title, date range, stats, etc.)
     * @param string|null $correlationId Correlation ID for tracing
     * @return array Export result: {url, filename, size, generated_at, expires_at}
     *
     * @throws \InvalidArgumentException If HTML or metadata is invalid
     * @throws \RuntimeException If PDF generation fails
     */
    public function exportGeoHeatmapToPdf(
        int $tenantId,
        string $heatmapHtml,
        array $metadata = [],
        ?string $correlationId = null
    ): array {
        $correlationId ??= "export-{$this->generateTraceId()}";

        try {
            // Validate inputs
            if (empty($heatmapHtml)) {
                throw new \InvalidArgumentException('Heatmap HTML content cannot be empty');
            }

            if (empty($metadata) || !isset($metadata['title'])) {
                throw new \InvalidArgumentException('PDF metadata must include title');
            }

            // Generate filename
            $filename = $this->generateFilename('geo-heatmap-report', 'pdf', $tenantId);
            $storagePath = "{$this->storagePath}/{$tenantId}/{$filename}";

            Log::channel('audit')->info('Starting geo-heatmap PDF export', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'metadata_keys' => array_keys($metadata),
                'correlation_id' => $correlationId,
            ]);

            // Generate PDF with heatmap and report data
            $pdfContent = $this->htmlToPdf($heatmapHtml, $metadata);

            // Validate file size
            $fileSize = \strlen($pdfContent);
            if ($fileSize > $this->maxFileSize) {
                throw new \RuntimeException(
                    "Exported PDF size ({$fileSize} bytes) exceeds maximum ({$this->maxFileSize} bytes)"
                );
            }

            // Store file
            Storage::disk($this->disk)->put(
                $storagePath,
                $pdfContent,
                ['visibility' => 'private']
            );

            // Generate signed URL
            $url = Storage::disk($this->disk)->temporaryUrl(
                $storagePath,
                \now()->addSeconds($this->fileTtl)
            );

            Log::channel('audit')->info('Geo-heatmap PDF export completed', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'storage_path' => $storagePath,
                'correlation_id' => $correlationId,
            ]);

            return [
                'url' => $url,
                'filename' => $filename,
                'size' => $fileSize,
                'format' => 'pdf',
                'generated_at' => \now()->toIso8601String(),
                'expires_at' => \now()->addSeconds($this->fileTtl)->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

        } catch (\Exception $e) {
            Log::channel('audit')->error('Geo-heatmap PDF export failed', [
                'tenant_id' => $tenantId,
                'error_message' => $e->getMessage(),
                'error_trace' => $e->getTraceAsString(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Export click-heatmap to PNG.
     *
     * Converts click-heatmap Canvas visualization to PNG image.
     *
     * @param int $tenantId Tenant ID
     * @param string $canvasDataUrl Canvas data URL from frontend (data:image/png;base64,...)
     * @param array $metadata Export metadata
     * @param string|null $correlationId Correlation ID
     * @return array Export result
     *
     * @throws \InvalidArgumentException If canvas data is invalid
     * @throws \RuntimeException If export fails
     */
    public function exportClickHeatmapToPng(
        int $tenantId,
        string $canvasDataUrl,
        array $metadata = [],
        ?string $correlationId = null
    ): array {
        $correlationId ??= "export-{$this->generateTraceId()}";

        try {
            // Validate canvas data
            if (!Str::startsWith($canvasDataUrl, 'data:image/')) {
                throw new \InvalidArgumentException('Invalid canvas data URL format');
            }

            // Extract base64 content
            $pngData = $this->extractBase64FromDataUrl($canvasDataUrl);

            // Generate filename
            $filename = $this->generateFilename('click-heatmap', 'png', $tenantId);
            $storagePath = "{$this->storagePath}/{$tenantId}/{$filename}";

            Log::channel('audit')->info('Starting click-heatmap PNG export', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'correlation_id' => $correlationId,
            ]);

            // Validate file size
            $fileSize = \strlen($pngData);
            if ($fileSize > $this->maxFileSize) {
                throw new \RuntimeException(
                    "Exported PNG size ({$fileSize} bytes) exceeds maximum ({$this->maxFileSize} bytes)"
                );
            }

            // Store file
            Storage::disk($this->disk)->put(
                $storagePath,
                $pngData,
                ['visibility' => 'private']
            );

            // Generate signed URL
            $url = Storage::disk($this->disk)->temporaryUrl(
                $storagePath,
                \now()->addSeconds($this->fileTtl)
            );

            Log::channel('audit')->info('Click-heatmap PNG export completed', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'correlation_id' => $correlationId,
            ]);

            return [
                'url' => $url,
                'filename' => $filename,
                'size' => $fileSize,
                'format' => 'png',
                'generated_at' => \now()->toIso8601String(),
                'expires_at' => \now()->addSeconds($this->fileTtl)->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

        } catch (\Exception $e) {
            Log::channel('audit')->error('Click-heatmap PNG export failed', [
                'tenant_id' => $tenantId,
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Export click-heatmap to PDF.
     *
     * Generates PDF report with click-heatmap visualization and statistics.
     *
     * @param int $tenantId Tenant ID
     * @param string $canvasDataUrl Canvas data URL from frontend
     * @param array $metadata Report metadata
     * @param string|null $correlationId Correlation ID
     * @return array Export result
     *
     * @throws \InvalidArgumentException If data is invalid
     * @throws \RuntimeException If PDF generation fails
     */
    public function exportClickHeatmapToPdf(
        int $tenantId,
        string $canvasDataUrl,
        array $metadata = [],
        ?string $correlationId = null
    ): array {
        $correlationId ??= "export-{$this->generateTraceId()}";

        try {
            // Validate inputs
            if (!Str::startsWith($canvasDataUrl, 'data:image/')) {
                throw new \InvalidArgumentException('Invalid canvas data URL format');
            }

            if (empty($metadata) || !isset($metadata['title'])) {
                throw new \InvalidArgumentException('PDF metadata must include title');
            }

            // Extract base64 image data
            $imageData = $this->extractBase64FromDataUrl($canvasDataUrl);

            // Generate filename
            $filename = $this->generateFilename('click-heatmap-report', 'pdf', $tenantId);
            $storagePath = "{$this->storagePath}/{$tenantId}/{$filename}";

            Log::channel('audit')->info('Starting click-heatmap PDF export', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'correlation_id' => $correlationId,
            ]);

            // Create PDF with image and report metadata
            $pdfContent = $this->createPdfWithImage($imageData, $metadata);

            // Validate file size
            $fileSize = \strlen($pdfContent);
            if ($fileSize > $this->maxFileSize) {
                throw new \RuntimeException(
                    "Exported PDF size ({$fileSize} bytes) exceeds maximum ({$this->maxFileSize} bytes)"
                );
            }

            // Store file
            Storage::disk($this->disk)->put(
                $storagePath,
                $pdfContent,
                ['visibility' => 'private']
            );

            // Generate signed URL
            $url = Storage::disk($this->disk)->temporaryUrl(
                $storagePath,
                \now()->addSeconds($this->fileTtl)
            );

            Log::channel('audit')->info('Click-heatmap PDF export completed', [
                'tenant_id' => $tenantId,
                'filename' => $filename,
                'file_size' => $fileSize,
                'correlation_id' => $correlationId,
            ]);

            return [
                'url' => $url,
                'filename' => $filename,
                'size' => $fileSize,
                'format' => 'pdf',
                'generated_at' => \now()->toIso8601String(),
                'expires_at' => \now()->addSeconds($this->fileTtl)->toIso8601String(),
                'correlation_id' => $correlationId,
            ];

        } catch (\Exception $e) {
            Log::channel('audit')->error('Click-heatmap PDF export failed', [
                'tenant_id' => $tenantId,
                'error_message' => $e->getMessage(),
                'correlation_id' => $correlationId,
            ]);

            throw $e;
        }
    }

    /**
     * Convert HTML to PNG image using server-side rendering.
     *
     * Uses headless Chromium (Puppeteer) or similar technology
     * to render HTML and capture as PNG.
     *
     * @param string $html HTML content to render
     * @param array $options Rendering options
     * @return string PNG image binary content
     *
     * @throws \RuntimeException If rendering fails
     */
    private function htmlToPng(string $html, array $options = []): string
    {
        // This is a placeholder implementation
        // In production, you would use:
        // - Puppeteer (Node.js) via shell_exec
        // - Headless Chrome via API
        // - Browsershot package (Laravel wrapper for Puppeteer)
        //
        // Example with Browsershot:
        // $png = Browsershot::html($html)->png();
        //
        // For now, return placeholder
        Log::channel('audit')->debug('Converting HTML to PNG', [
            'html_length' => \strlen($html),
            'options' => $options,
        ]);

        // TODO: Implement with Browsershot or Puppeteer
        throw new \RuntimeException(
            'HTML to PNG conversion not yet implemented. Install Browsershot: composer require spatie/browsershot'
        );
    }

    /**
     * Convert HTML to PDF document.
     *
     * Uses DOMPDF library to convert HTML/CSS to PDF.
     *
     * @param string $html HTML content
     * @param array $metadata Document metadata (title, author, subject)
     * @return string PDF document binary content
     *
     * @throws \RuntimeException If PDF generation fails
     */
    private function htmlToPdf(string $html, array $metadata = []): string
    {
        // This is a placeholder implementation
        // In production, use DOMPDF:
        //
        // $dompdf = new \Dompdf\Dompdf();
        // $dompdf->loadHtml($html);
        // $dompdf->setPaper('A4', 'portrait');
        // $dompdf->render();
        // $pdfContent = $dompdf->output();
        //
        // For now, return placeholder
        Log::channel('audit')->debug('Converting HTML to PDF', [
            'html_length' => \strlen($html),
            'metadata' => $metadata,
        ]);

        // TODO: Implement with DOMPDF
        throw new \RuntimeException(
            'HTML to PDF conversion not yet implemented. Install DOMPDF: composer require dompdf/dompdf'
        );
    }

    /**
     * Create PDF document with embedded image and metadata.
     *
     * @param string $imageData Binary image data
     * @param array $metadata Report metadata
     * @return string PDF document binary content
     *
     * @throws \RuntimeException If PDF creation fails
     */
    private function createPdfWithImage(string $imageData, array $metadata): string
    {
        // Placeholder for PDF creation with embedded image
        // TODO: Implement with DOMPDF
        throw new \RuntimeException(
            'PDF creation with image embedding not yet implemented. Install DOMPDF: composer require dompdf/dompdf'
        );
    }

    /**
     * Extract base64 content from data URL.
     *
     * Converts data:image/png;base64,{content} to binary content.
     *
     * @param string $dataUrl Data URL from canvas toDataURL()
     * @return string Binary image content
     *
     * @throws \InvalidArgumentException If data URL format is invalid
     */
    private function extractBase64FromDataUrl(string $dataUrl): string
    {
        if (!Str::contains($dataUrl, 'base64,')) {
            throw new \InvalidArgumentException('Data URL must be in base64 format');
        }

        [, $base64Content] = explode('base64,', $dataUrl, 2);

        return \base64_decode($base64Content, true) 
            ?: throw new \InvalidArgumentException('Invalid base64 content in data URL');
    }

    /**
     * Generate unique filename for exported file.
     *
     * Format: {type}-{tenantId}-{timestamp}-{random}.{extension}
     *
     * @param string $type Export type (geo-heatmap, click-heatmap, etc.)
     * @param string $extension File extension (png, pdf)
     * @param int $tenantId Tenant ID for organization
     * @return string Unique filename
     */
    private function generateFilename(string $type, string $extension, int $tenantId): string
    {
        $timestamp = \now()->format('YmdHis');
        $random = Str::random(8);

        return "{$type}-{$timestamp}-{$random}.{$extension}";
    }

    /**
     * Generate unique trace ID for export request.
     *
     * @return string Trace ID (timestamp-random)
     */
    private function generateTraceId(): string
    {
        return \now()->timestamp . '-' . Str::random(8);
    }

    /**
     * Get storage disk instance.
     *
     * @return \Illuminate\Contracts\Filesystem\Filesystem Storage disk
     */
    public function getStorageDisk()
    {
        return Storage::disk($this->disk);
    }

    /**
     * Set storage disk for exports.
     *
     * @param string $disk Disk name (s3, local, etc.)
     * @return $this Fluent interface
     */
    public function setDisk(string $disk): self
    {
        $this->disk = $disk;
        return $this;
    }

    /**
     * Set file TTL (time to live) for exports.
     *
     * @param int $seconds TTL in seconds
     * @return $this Fluent interface
     */
    public function setFileTtl(int $seconds): self
    {
        $this->fileTtl = $seconds;
        return $this;
    }

    /**
     * Set maximum file size limit.
     *
     * @param int $bytes Maximum size in bytes
     * @return $this Fluent interface
     */
    public function setMaxFileSize(int $bytes): self
    {
        $this->maxFileSize = $bytes;
        return $this;
    }
}
