<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\CarbonImmutable;

/**
 * Document — Доменная модель документов и сертификатов для B2B
 * 
 * Поставщики могут отправлять документы всем своим B2B клиентам автоматически
 */
final class Document extends Model
{
    protected $table = 'supermarket_documents';

    protected $fillable = [
        'uuid',
        'seller_id',
        'tenant_id',
        'document_type',
        'title',
        'description',
        'file_path',
        'file_name',
        'file_size',
        'file_mime_type',
        'format',
        'file_hash',
        'valid_from',
        'valid_until',
        'document_number',
        'issuing_authority',
        'is_template',
        'template_id',
        'auto_distribute',
        'distribution_schedule',
        'target_segments',
        'target_tiers',
        'status',
        'published_at',
        'expires_at',
        // B2B Certificate fields
        'batch_number',
        'barcode',
        'batch_weight',
        'delivered_quantity',
        'remaining_quantity',
        'is_closed',
        'closed_at',
        'is_valid',
        'validated_at',
        'product_id',
        // Temperature monitoring fields
        'temperature_requirement_id',
        'requires_temperature_compliance',
        'temperature_violation_count',
        'last_temperature_check_at',
        'temperature_compliance_status',
        // Supply chain fields
        'supplier_tier_id',
        'primary_document_id',
        'requires_primary_document',
        'chain_position',
        'is_resale_blocked',
        'block_reason',
    // Temperature monitoring fields
    'temperature_requirement_id',
    'requires_temperature_compliance',
    'temperature_violation_count',
    'last_temperature_check_at',
    'temperature_compliance_status',
    // Payment delay fields
    'payment_delay_days',
    'payment_delay_until',
    // Price markup fields
    'price_with_delay',
    'price_markup_percent',
];

    protected $casts = [
        'uuid' => 'string',
        'valid_from' => 'datetime',
        'valid_until' => 'datetime',
        'is_template' => 'boolean',
        'auto_distribute' => 'boolean',
        'distribution_schedule' => 'array',
        'target_segments' => 'array',
        'target_tiers' => 'array',
        'published_at' => 'datetime',
        'expires_at' => 'datetime',
        // B2B Certificate casts
        'batch_weight' => 'decimal:3',
        'delivered_quantity' => 'decimal:3',
        // Payment delay casts
        'payment_delay_days' => 'integer',
        'payment_delay_until' => 'datetime',
        'remaining_quantity' => 'decimal:3',
        'is_closed' => 'boolean',
        'is_valid' => 'boolean',
        'validated_at' => 'datetime',
        'closed_at' => 'datetime',
        // Temperature monitoring casts
        'requires_temperature_compliance' => 'boolean',
        'temperature_violation_count' => 'integer',
        'last_temperature_check_at' => 'datetime',
        // Price markup casts
        'price_with_delay' => 'decimal:3',
        'price_markup_percent' => 'decimal:2',
    ];

    // Status constants
    public const STATUS_REVOKED = 'revoked';
    public const STATUS_CLOSED = 'closed';

    // Document formats
    public const FORMAT_PDF = 'pdf';
    public const FORMAT_JPEG = 'jpeg';
    public const FORMAT_TIFF = 'tiff';

    // Document types
    public const TYPE_CERTIFICATE = 'certificate';
    public const TYPE_LICENSE = 'license';
    public const TYPE_CONTRACT = 'contract';
    public const TYPE_INVOICE = 'invoice';
    public const TYPE_PRICE_LIST = 'price_list';
    public const TYPE_CATALOG = 'catalog';
    public const TYPE_QUALITY_CERT = 'quality_cert';
    public const TYPE_HONEST_MARK = 'honest_mark';
    public const TYPE_OTHER = 'other';

    // Status
    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_ARCHIVED = 'archived';
    public const STATUS_EXPIRED = 'expired';

    /**
     * Отношения
     */
    public function seller(): BelongsTo
    {
        return $this->belongsTo(Seller::class, 'seller_id');
    }

    public function distributions(): HasMany
    {
        return $this->hasMany(DocumentDistribution::class, 'document_id');
    }

    public function recipients(): BelongsToMany
    {
        return $this->belongsToMany(Customer::class, 'supermarket_document_distributions', 'document_id', 'customer_id')
            ->withPivot(['sent_at', 'viewed_at', 'downloaded_at'])
            ->withTimestamps();
    }

    public function template(): BelongsTo
    {
        return $this->belongsTo(DocumentTemplate::class, 'template_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function temperatureRequirement(): BelongsTo
    {
        return $this->belongsTo(ProductTemperatureRequirement::class, 'temperature_requirement_id');
    }

    public function supplierTier(): BelongsTo
    {
        return $this->belongsTo(SupplierTier::class, 'supplier_tier_id');
    }

    public function primaryDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'primary_document_id');
    }

    public function supplyChainLinks(): HasMany
    {
        return $this->hasMany(SupplyChainLink::class, 'document_id');
    }

    public function history(): HasMany
    {
        return $this->hasMany(DocumentHistory::class, 'document_id');
    }

    /**
     * Проверить действителен ли документ
     */
    public function isValid(): bool
    {
        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        if ($this->valid_until && $this->valid_until->isPast()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }

    /**
     * Получить дни до истечения срока действия
     */
    public function getDaysUntilExpiry(): ?int
    {
        if (!$this->valid_until) {
            return null;
        }

        return now()->diffInDays($this->valid_until, false);
    }

    /**
     * Получить тип документа для отображения
     */
    public function getTypeLabel(): string
    {
        return match($this->document_type) {
            self::TYPE_CERTIFICATE => 'Сертификат',
            self::TYPE_LICENSE => 'Лицензия',
            self::TYPE_CONTRACT => 'Договор',
            self::TYPE_INVOICE => 'Счёт',
            self::TYPE_PRICE_LIST => 'Прайс-лист',
            self::TYPE_CATALOG => 'Каталог',
            self::TYPE_QUALITY_CERT => 'Сертификат качества',
            self::TYPE_HONEST_MARK => 'Честный знак',
            default => 'Другое',
        };
    }

    /**
     * Проверить нужно ли автораспределение
     */
    public function shouldAutoDistribute(): bool
    {
        if (!$this->auto_distribute) {
            return false;
        }

        if ($this->status !== self::STATUS_PUBLISHED) {
            return false;
        }

        // Проверка расписания
        if ($this->distribution_schedule) {
            $schedule = $this->distribution_schedule;

            if (isset($schedule['type'])) {
                $now = now();

                if ($schedule['type'] === 'daily') {
                    // Распределить если сегодня ещё не было
                    $lastDistribution = $this->distributions()
                        ->where('created_at', '>=', $now->startOfDay())
                        ->exists();
                    return !$lastDistribution;
                }

                if ($schedule['type'] === 'weekly') {
                    $dayOfWeek = $now->dayOfWeek;
                    if (in_array($dayOfWeek, $schedule['days'] ?? [])) {
                        $lastDistribution = $this->distributions()
                            ->where('created_at', '>=', $now->startOfDay())
                            ->exists();
                        return !$lastDistribution;
                    }
                }

                if ($schedule['type'] === 'monthly') {
                    $dayOfMonth = $now->day;
                    if ($dayOfMonth === ($schedule['day'] ?? 1)) {
                        $lastDistribution = $this->distributions()
                            ->where('created_at', '>=', $now->startOfDay())
                            ->exists();
                        return !$lastDistribution;
                    }
                }
            }
        }

        return true;
    }

    /**
     * B2B Certificate: Проверить валидность формата документа
     */
    public function isValidFormat(): bool
    {
        return in_array($this->format, [self::FORMAT_PDF, self::FORMAT_JPEG, self::FORMAT_TIFF]);
    }

    /**
     * B2B Certificate: Проверить срок действия сертификата
     */
    public function isCertificateValid(): bool
    {
        if (!$this->valid_from || !$this->valid_until) {
            return true; // Если даты не указаны, считаем валидным
        }

        $now = now();
        return $now->between($this->valid_from, $this->valid_until);
    }

    /**
     * B2B Certificate: Проверить истёк ли срок действия
     */
    public function isCertificateExpired(): bool
    {
        if (!$this->valid_until) {
            return false;
        }

        return now()->isAfter($this->valid_until);
    }

    /**
     * B2B Certificate: Рассчитать оставшееся количество партии
     */
    public function calculateRemainingQuantity(): void
    {
        if ($this->batch_weight === null) {
            return;
        }

        $this->remaining_quantity = max(0, $this->batch_weight - $this->delivered_quantity);
    }

    /**
     * B2B Certificate: Проверить можно ли добавить количество
     */
    public function canAddQuantity(float $quantity): bool
    {
        if ($this->is_closed) {
            return false;
        }

        if ($this->batch_weight === null) {
            return true;
        }

        $newTotal = $this->delivered_quantity + $quantity;
        return $newTotal <= $this->batch_weight;
    }

    /**
     * B2B Certificate: Добавить доставленное количество
     */
    public function addDeliveredQuantity(float $quantity): bool
    {
        if (!$this->canAddQuantity($quantity)) {
            return false;
        }

        $this->delivered_quantity += $quantity;
        $this->calculateRemainingQuantity();

        // Автоматически закрыть сертификат если партия полностью продана
        if ($this->remaining_quantity <= 0.001) { // допуск для плавающей точки
            $this->closeCertificate();
        }

        return $this->save();
    }

    /**
     * B2B Certificate: Закрыть сертификат
     */
    public function closeCertificate(): bool
    {
        $this->is_closed = true;
        $this->closed_at = now();
        $this->status = self::STATUS_CLOSED;
        
        return $this->save();
    }

    /**
     * B2B Certificate: Открыть сертификат (для админа)
     */
    public function reopenCertificate(): bool
    {
        $this->is_closed = false;
        $this->closed_at = null;
        $this->status = self::STATUS_PUBLISHED;
        
        return $this->save();
    }

    /**
     * B2B Certificate: Получить процент использования партии
     */
    public function getBatchUsagePercentage(): float
    {
        if ($this->batch_weight === null || $this->batch_weight == 0) {
            return 0;
        }

        return ($this->delivered_quantity / $this->batch_weight) * 100;
    }

    /**
     * B2B Certificate: Валидировать сертификат
     */
    public function validateCertificate(?int $userId = null): bool
    {
        $this->is_valid = $this->isValidFormat() && $this->isCertificateValid();
        $this->validated_at = now();

        if ($this->isCertificateExpired()) {
            $this->status = self::STATUS_EXPIRED;
        }

        $result = $this->save();

        // Записать в историю
        DocumentHistory::createLog(
            $this->id,
            DocumentHistory::ACTION_VALIDATED,
            $userId,
            DocumentHistory::USER_TYPE_SYSTEM,
            null,
            ['is_valid' => $this->is_valid],
            "Certificate validation: " . ($this->is_valid ? 'valid' : 'invalid')
        );

    /**
     * Temperature compliance: Check if document requires temperature monitoring
     */
    public function requiresTemperatureMonitoring(): bool
    {
        return $this->requires_temperature_compliance ?? false;
    }

    /**
     * Temperature compliance: Get current compliance status
     */
    public function getTemperatureComplianceStatus(): string
    {
        return $this->temperature_compliance_status ?? 'unknown';
    }

    /**
     * Temperature compliance: Check if batch has temperature violations
     */
    public function hasTemperatureViolations(): bool
    {
        return ($this->temperature_violation_count ?? 0) > 0;
    }

    /**
     * Temperature compliance: Update temperature compliance status
     */
    public function updateTemperatureCompliance(string $status, ?int $violationCount = null): bool
    {
        $this->temperature_compliance_status = $status;
        $this->last_temperature_check_at = now();
        
        if ($violationCount !== null) {
            $this->temperature_violation_count = $violationCount;
        }

        return $this->save();
    }

    /**
     * Temperature compliance: Increment violation count
     */
    public function incrementTemperatureViolation(): bool
    {
        $this->temperature_violation_count = ($this->temperature_violation_count ?? 0) + 1;
        $this->temperature_compliance_status = 'violation';
        $this->last_temperature_check_at = now();

        return $this->save();
    }

    /**
     * Temperature compliance: Get compliance status label
     */
    public function getTemperatureComStatusLabel(): string
    {
        return match($this->temperature_compliance_status) {
            'compliant' => 'Соответствует',
            'violation' => 'Нарушение',
            'warning' => 'Предупреждение',
            'unknown' => 'Не проверено',
            default => 'Неизвестно',
        };
    }

    /**
     * Temperature compliance: Get compliance status color class
     */
    public function getTemperatureComplianceColor(): string
    {
        return match($this->temperature_compliance_status) {
            'compliant' => 'text-green-600',
            'violation' => 'text-red-600',
            'warning' => 'text-yellow-600',
            'unknown' => 'text-gray-600',
            default => 'text-gray-600',
        };
    }

    /**
     * Проверить, есть ли отсрочка платежа
     */
    public function hasPaymentDelay(): bool
    {
        return $this->payment_delay_days > 0 
            && (!$this->payment_delay_until || $this->payment_delay_until->isFuture());
    }

    /**
     * Получить цену с учетом отсрочки
     */
    public function getPriceWithDelay(): float
    {
        if (!$this->hasPaymentDelay() || !$this->price_with_delay) {
            return $this->batch_weight ?? 0;
        }
        
        return (float)$this->price_with_delay;
    }

    /**
     * Получить повышение цены в процентах
     */
    public function getPriceMarkupPercent(): float
    {
        return (float)($this->price_markup_percent ?? 0);
    }

    /**
     * Получить рекомендацию по повышению цены
     */
    public function getPriceMarkupRecommendation(): ?string
    {
        if (!$this->price_markup_percent || $this->price_markup_percent <= 3) {
            return null;
        }
        
        return "Price markup of {$this->price_markup_percent}% exceeds recommended 3%";
    }

    /**
     * B2B Certificate: Получить список поддерживаемых форматов
     */
    public static function getSupportedFormats(): array
    {
        return [
            self::FORMAT_PDF => 'PDF',
            self::FORMAT_JPEG => 'JPEG',
            self::FORMAT_TIFF => 'TIFF',
        ];
    }

    /**
     * Получить целевых получателей
     */
    public function getTargetRecipients(): array
    {
        // TODO: Запрос к БД для получения клиентов по критериям
        // - target_segments: RFM сегменты
        // - target_tiers: loyalty tiers
        // - is_b2b: только B2B клиенты
        
        return []; // mock
    }
}
