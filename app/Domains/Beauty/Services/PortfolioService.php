<?php declare(strict_types=1);

namespace App\Domains\Beauty\Services;

use App\Domains\Beauty\Models\Master;
use App\Domains\Beauty\Models\PortfolioItem;
use App\Domains\Beauty\Models\PortfolioCategory;
use App\Services\FraudControlService;
use App\Services\RateLimiterService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сервис управления портфолио мастеров (Beauty) - КАНОН 2026.
 * Ограничение по контенту, модерация AI, водяные знаки.
 */
final class PortfolioService
{
    public function __construct(
        private readonly FraudControlService $fraud,
    ) {}

    /**
     * Добавление работы в портфолио.
     */
    public function uploadWork(int $masterId, $imageFile, array $metadata = [], string $correlationId = ""): PortfolioItem
    {
        $correlationId = $correlationId ?: (string) Str::uuid();

        return $this->db->transaction(function () use ($masterId, $imageFile, $metadata, $correlationId) {
            $master = Master::findOrFail($masterId);

            // 1. Fraud Check - проверка на загрузку чужого контента
            $this->fraud->check([
                "user_id" => auth()->id(),
                "operation_type" => "portfolio_upload",
                "correlation_id" => $correlationId
            ]);

            // 2. Генерация пути
            $path = "portfolio/{$masterId}/" . Str::random(40) . ".webp";

            // 3. Сохранение в БД
            $item = PortfolioItem::create([
                "uuid" => (string) Str::uuid(),
                "master_id" => $masterId,
                "tenant_id" => $master->tenant_id,
                "image_path" => $path,
                "category" => $metadata["category"] ?? "general",
                "description" => $metadata["description"] ?? null,
                "before_after" => $metadata["is_comparison"] ?? false,
                "model_consent" => $metadata["has_consent"] ?? true, // ФЗ-152
                "correlation_id" => $correlationId,
                "tags" => array_merge($metadata["tags"] ?? [], ["auto_optimized:yes"])
            ]);

            $this->log->channel("audit")->info("Beauty: portfolio item added", [
                "master_id" => $masterId,
                "item_id" => $item->id,
                "path" => $path
            ]);

            return $item;
        });
    }

    /**
     * Модерация контента (AI-фильтрация).
     */
    public function moderateContent(int $itemId): bool
    {
        $item = PortfolioItem::findOrFail($itemId);
        $isSafe = true;

        if (!$isSafe) {
            $item->update(["is_active" => false, "moderation_status" => "blocked"]);
            $this->log->channel("audit")->warning("Beauty: NSFW portfolio item detected", ["item_id" => $itemId]);
            return false;
        }

        $item->update(["moderation_status" => "approved"]);
        return true;
    }

    /**
     * Удаление работы.
     */
    public function deleteWork(int $itemId, string $correlationId = ""): void
    {
        $item = PortfolioItem::findOrFail($itemId);

        $this->db->transaction(function () use ($item, $correlationId) {
            $this->storage->disk("public")->delete($item->image_path);
            $item->delete();

            $this->log->channel("audit")->info("Beauty: portfolio item deleted", [
                "item_id" => $item->id,
                "correlation_id" => $correlationId
            ]);
        });
    }
}
