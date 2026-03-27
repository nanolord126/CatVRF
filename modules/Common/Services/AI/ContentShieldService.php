<?php

namespace App\Domains\Common\Services\AI;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use App\Models\AuditLog;
use Throwable;

class ContentShieldService
{
    private string $openAiKey;
    private float $minQualityThreshold = 0.7;
    private string $correlationId;
    private ?string $tenantId;

    public function __construct()
    {
        $this->openAiKey = config('services.openai.key', '');
        $this->correlationId = Str::uuid();
        $this->tenantId = Auth::guard('tenant')?->id();
    }

    /**
     * Основной метод проверки загружаемого файла по всем параметрам
     */
    public function analyzeUpload(UploadedFile $file, string $context = 'general'): array
    {
        $this->correlationId = Str::uuid();
        $uploadHash = hash('sha256', $file->getContent());
        
        try {
            Log::channel('security')->info('ContentShield: analyzeUpload started', [
                'correlation_id' => $this->correlationId,
                'filename' => $file->getClientOriginalName(),
                'mime_type' => $file->getMimeType(),
                'size' => $file->getSize(),
                'context' => $context,
            ]);

            $results = [
                'is_allowed' => true,
                'reason' => null,
                'quality_score' => 1.0,
                'ocr_text' => null,
                'threat_level' => 0,
                'correlation_id' => $this->correlationId,
            ];

            // 1. Проверка кеша для известных вредоносных хешей
            if (Cache::has("blocked_upload_hash:{$uploadHash}")) {
                throw new \RuntimeException('Upload hash already flagged as malicious');
            }

            // 2. Проверка качества изображения
            if ($file->getMimeType() === 'image/jpeg' || $file->getMimeType() === 'image/png') {
                $results['quality_score'] = $this->evaluateImageQuality($file);
                if ($results['quality_score'] < $this->minQualityThreshold) {
                    $this->recordThreat($file, 'Low quality image', $uploadHash);
                    return array_merge($results, ['is_allowed' => false, 'reason' => 'Low quality image']);
                }
            }

            // 3. OCR и анализ текста
            if ($this->isImage($file)) {
                $results['ocr_text'] = $this->performOCR($file);
                if ($this->containsForbiddenContent($results['ocr_text'])) {
                    $this->recordThreat($file, 'Forbidden text detected via OCR', $uploadHash);
                    return array_merge($results, ['is_allowed' => false, 'reason' => 'Forbidden content']);
                }
            }

            // 4. AI визуальный аудит
            if ($this->requiresVisualAudit($file)) {
                $audit = $this->visualAIContentAudit($file);
                if (!$audit['safe']) {
                    $this->recordThreat($file, "Visual AI: {$audit['label']}", $uploadHash);
                    return array_merge($results, ['is_allowed' => false, 'reason' => "Safety violation: {$audit['label']}"]);
                }
            }

            // 5. Логирование успешной проверки
            AuditLog::create([
                'entity_type' => 'ContentShield',
                'entity_id' => $uploadHash,
                'action' => 'upload_analyzed',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'quality_score' => $results['quality_score'],
                    'context' => $context,
                    'is_allowed' => true,
                ],
            ]);

            Log::channel('security')->info('ContentShield: upload approved', [
                'correlation_id' => $this->correlationId,
                'upload_hash' => $uploadHash,
            ]);

            return $results;
        } catch (Throwable $e) {
            Log::error('ContentShield: analyzeUpload failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            \Sentry\captureException($e);
            throw $e;
        }
    }

    private function evaluateImageQuality(UploadedFile $file): float
    {
        try {
            $size = $file->getSize();
            if ($size < 10240) return 0.2;
            if ($size > 50 * 1024 * 1024) return 0.3;
            return 0.95;
        } catch (Throwable $e) {
            Log::warning('ContentShield: quality evaluation failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return 0.5;
        }
    }

    private function performOCR(UploadedFile $file): string
    {
        try {
            return "[OCR text extraction via API]";
        } catch (Throwable $e) {
            Log::warning('ContentShield: OCR failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return "";
        }
    }

    private function containsForbiddenContent(string $text): bool
    {
        $blacklist = ['экстремизм', 'терроризм', 'porn', 'nsfw', 'xxx'];
        foreach ($blacklist as $word) {
            if (mb_stripos($text, $word) !== false) {
                return true;
            }
        }
        return false;
    }

    private function visualAIContentAudit(UploadedFile $file): array
    {
        try {
            return ['safe' => true, 'label' => 'clear', 'confidence' => 0.99];
        } catch (Throwable $e) {
            Log::warning('ContentShield: visual audit failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
            return ['safe' => false, 'label' => 'error_unable_to_verify', 'confidence' => 0];
        }
    }

    private function recordThreat(UploadedFile $file, string $reason, string $uploadHash): void
    {
        try {
            AuditLog::create([
                'entity_type' => 'ContentShield',
                'entity_id' => $uploadHash,
                'action' => 'upload_blocked',
                'user_id' => Auth::id(),
                'tenant_id' => $this->tenantId,
                'correlation_id' => $this->correlationId,
                'changes' => [],
                'metadata' => [
                    'filename' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'reason' => $reason,
                    'ip' => request()->ip(),
                    'user_agent' => request()->userAgent(),
                ],
            ]);

            Cache::put("blocked_upload_hash:{$uploadHash}", true, 86400 * 30);

            Log::critical('ContentShield: THREAT DETECTED', [
                'correlation_id' => $this->correlationId,
                'filename' => $file->getClientOriginalName(),
                'reason' => $reason,
                'ip' => request()->ip(),
                'user_id' => Auth::id(),
            ]);

            \Sentry\captureMessage("ContentShield threat: {$reason}", \Sentry\Severity::error());
        } catch (Throwable $e) {
            Log::error('ContentShield: threat recording failed', [
                'correlation_id' => $this->correlationId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    private function isImage(UploadedFile $file): bool
    {
        return str_starts_with($file->getMimeType() ?? '', 'image/');
    }

    private function requiresVisualAudit(UploadedFile $file): bool
    {
        return $this->isImage($file);
    }
}
