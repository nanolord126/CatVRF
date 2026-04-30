<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SupplyChainLink — Звено цепочки поставок
 * 
 * Отслеживает полный путь товара от производителя до конечного покупателя
 * Позволяет предотвратить несанкционированную перепродажу
 */
final class SupplyChainLink extends Model
{
    use SoftDeletes;

    protected $table = 'supply_chain_links';

    protected $fillable = [
        'uuid',
        'product_id',
        'batch_number',
        'batch_identifier',
        'supplier_id',
        'supplier_tier_id',
        'buyer_id',
        'buyer_tier_id',
        'document_id',
        'primary_document_id',
        'chain_position',
        'chain_depth',
        'quantity',
        'unit_price',
        'transaction_reference',
        'is_verified',
        'verified_at',
        'verified_by',
        'verification_notes',
        'is_compliant',
        'compliance_notes',
        'is_resale_blocked',
        'block_reason',
        'metadata',
    ];

    protected $casts = [
        'uuid' => 'string',
        'quantity' => 'decimal:3',
        'unit_price' => 'decimal:2',
        'is_verified' => 'boolean',
        'verified_at' => 'datetime',
        'is_compliant' => 'boolean',
        'is_resale_blocked' => 'boolean',
        'metadata' => 'array',
    ];

    /**
     * Отношения
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(\App\Models\Product::class, 'product_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'supplier_id');
    }

    public function supplierTier(): BelongsTo
    {
        return $this->belongsTo(SupplierTier::class, 'supplier_tier_id');
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'buyer_id');
    }

    public function buyerTier(): BelongsTo
    {
        return $this->belongsTo(SupplierTier::class, 'buyer_tier_id');
    }

    public function document(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'document_id');
    }

    public function primaryDocument(): BelongsTo
    {
        return $this->belongsTo(Document::class, 'primary_document_id');
    }

    public function verifiedBy(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class, 'verified_by');
    }

    /**
     * Проверить валидность цепочки
     */
    public function isValidChain(): bool
    {
        return $this->is_compliant && !$this->is_resale_blocked;
    }

    /**
     * Проверить есть ли первичный документ от производителя
     */
    public function hasPrimaryDocument(): bool
    {
        return $this->primary_document_id !== null;
    }

    /**
     * Получить полную цепочку для партии
     */
    public static function getFullChain(string $batchIdentifier): array
    {
        return self::where('batch_identifier', $batchIdentifier)
            ->with(['supplier', 'buyer', 'supplierTier', 'buyerTier', 'document'])
            ->orderBy('chain_position')
            ->get()
            ->toArray();
    }

    /**
     * Получить глубину цепочки для партии
     */
    public static function getChainDepth(string $batchIdentifier): int
    {
        return self::where('batch_identifier', $batchIdentifier)->max('chain_depth') ?? 0;
    }

    /**
     * Проверить превышает ли цепочка максимальную длину
     */
    public function exceedsMaxDepth(): bool
    {
        if (!$this->supplierTier) {
            return false;
        }

        $maxDepth = $this->supplierTier->getMaxChainLength();
        return $this->chain_depth > $maxDepth;
    }

    /**
     * Заблокировать как несанкционированную перепродажу
     */
    public function blockResale(string $reason): bool
    {
        $this->is_resale_blocked = true;
        $this->block_reason = $reason;
        $this->is_compliant = false;
        
        return $this->save();
    }

    /**
     * Разблокировать
     */
    public function unblockResale(): bool
    {
        $this->is_resale_blocked = false;
        $this->block_reason = null;
        $this->is_compliant = true;
        
        return $this->save();
    }

    /**
     * Верифицировать звено цепочки
     */
    public function verify(int $verifiedBy, ?string $notes = null): bool
    {
        $this->is_verified = true;
        $this->verified_at = now();
        $this->verified_by = $verifiedBy;
        $this->verification_notes = $notes;
        
        return $this->save();
    }

    /**
     * Создать звено цепочки
     */
    public static function createLink(array $data): self
    {
        return self::create(array_merge($data, [
            'uuid' => (string) \Illuminate\Support\Str::uuid(),
        ]));
    }
}
