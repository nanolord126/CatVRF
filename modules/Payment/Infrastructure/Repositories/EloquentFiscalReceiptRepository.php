<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\FiscalReceipt;
use Modules\Payment\Domain\Repositories\FiscalReceiptRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

final readonly class EloquentFiscalReceiptRepository implements FiscalReceiptRepositoryInterface
{
    public function save(FiscalReceipt $receipt): void
    {
        DB::table('fiscal_receipts')->updateOrInsert(
            ['uuid' => $receipt->uuid],
            $receipt->toArray()
        );
    }

    public function findByUuid(string $uuid): ?FiscalReceipt
    {
        $data = DB::table('fiscal_receipts')->where('uuid', $uuid)->first();

        return $data ? FiscalReceipt::fromArray((array) $data) : null;
    }

    public function findByPaymentIntentUuid(string $paymentIntentUuid): array
    {
        $records = DB::table('fiscal_receipts')
            ->where('payment_intent_uuid', $paymentIntentUuid)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();

        return array_map(fn($record) => FiscalReceipt::fromArray((array) $record), $records);
    }

    public function findByOrderId(string $orderId): array
    {
        $records = DB::table('fiscal_receipts')
            ->where('order_id', $orderId)
            ->orderBy('created_at', 'asc')
            ->get()
            ->toArray();

        return array_map(fn($record) => FiscalReceipt::fromArray((array) $record), $records);
    }

    public function findPending(int $limit = 100): array
    {
        $records = DB::table('fiscal_receipts')
            ->where('status', 'pending')
            ->where('retry_count', '<', 3)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();

        return array_map(fn($record) => FiscalReceipt::fromArray((array) $record), $records);
    }

    public function findFailed(int $limit = 100): array
    {
        $records = DB::table('fiscal_receipts')
            ->where('status', 'failed')
            ->where('retry_count', '<', 3)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->toArray();

        return array_map(fn($record) => FiscalReceipt::fromArray((array) $record), $records);
    }

    public function deleteOlderThan(CarbonImmutable $date): int
    {
        return DB::table('fiscal_receipts')
            ->where('created_at', '<', $date->toDateTimeString())
            ->delete();
    }
}
