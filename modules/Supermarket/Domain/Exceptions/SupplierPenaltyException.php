<?php

declare(strict_types=1);

namespace Modules\Supermarket\Domain\Exceptions;

use Exception;

final class SupplierPenaltyException extends Exception
{
    public static function expiredProductsDetected(int $supplierId, float $totalAmount): self
    {
        return new self("Expired products detected for supplier {$supplierId}. Total amount: {$totalAmount}", 422);
    }

    public static function penaltyAlreadyApplied(int $supplyId): self
    {
        return new self("Penalty already applied for supply {$supplyId}", 409);
    }

    public static function invalidSupplyAmount(float $amount): self
    {
        return new self("Invalid supply amount: {$amount}. Must be greater than 0", 422);
    }

    public static function supplierNotFound(int $supplierId): self
    {
        return new self("Supplier not found: {$supplierId}", 404);
    }

    public static function penaltyNotFound(int $penaltyId): self
    {
        return new self("Penalty not found: {$penaltyId}", 404);
    }

    public static function cannotWaivePaidPenalty(int $penaltyId): self
    {
        return new self("Cannot waive a paid penalty: {$penaltyId}", 400);
    }

    public static function invalidPenaltyAmount(float $amount): self
    {
        return new self("Invalid penalty amount: {$amount}. Must be greater than 0", 422);
    }

    public static function penaltyCalculationError(float $supplyAmount, float $multiplier): self
    {
        return new self("Penalty calculation error. Supply amount: {$supplyAmount}, multiplier: {$multiplier}", 500);
    }

    public static function insufficientFunds(float $required, float $available): self
    {
        return new self("Insufficient funds. Required: {$required}, available: {$available}", 402);
    }

    public static function penaltyDisputePeriodExpired(int $penaltyId): self
    {
        return new self("Dispute period expired for penalty: {$penaltyId}", 400);
    }

    public static function invalidSupplierTier(int $tierId): self
    {
        return new self("Invalid supplier tier: {$tierId}", 422);
    }

    public static function duplicatePenaltyForDocument(int $documentId): self
    {
        return new self("Duplicate penalty for document: {$documentId}", 409);
    }

    public static function penaltyAlreadyDisputed(int $penaltyId): self
    {
        return new self("Penalty already disputed: {$penaltyId}", 409);
    }

    public static function invalidDisputeReason(string $reason): self
    {
        return new self("Invalid dispute reason: {$reason}", 422);
    }

    public static function penaltyChargingFailed(int $penaltyId, string $error): self
    {
        return new self("Penalty charging failed for {$penaltyId}: {$error}", 500);
    }

    public static function refundFailed(int $penaltyId, string $error): self
    {
        return new self("Refund failed for penalty {$penaltyId}: {$error}", 500);
    }

    public static function businessNotFound(int $businessId): self
    {
        return new self("Business not found: {$businessId}", 404);
    }

    public static function expiredProductsListEmpty(): self
    {
        return new self("Expired products list cannot be empty", 422);
    }

    public static function invalidExpiredProductData(array $product): self
    {
        return new self("Invalid expired product data: " . json_encode($product), 422);
    }
}
