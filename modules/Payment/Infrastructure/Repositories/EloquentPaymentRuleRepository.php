<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\PaymentRule;
use Modules\Payment\Domain\Repositories\PaymentRuleRepositoryInterface;
use Illuminate\Support\Facades\DB;

final readonly class EloquentPaymentRuleRepository implements PaymentRuleRepositoryInterface
{
    public function save(PaymentRule $rule): void
    {
        DB::table('payment_rules')->updateOrInsert(
            ['uuid' => $rule->uuid],
            $rule->toArray()
        );
    }

    public function findByUuid(string $uuid): ?PaymentRule
    {
        $data = DB::table('payment_rules')->where('uuid', $uuid)->first();

        return $data ? PaymentRule::fromArray((array) $data) : null;
    }

    public function findByCode(string $code): array
    {
        $records = DB::table('payment_rules')
            ->where('code', $code)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return array_map(fn($record) => PaymentRule::fromArray((array) $record), $records);
    }

    public function findByCategory(string $category): array
    {
        $records = DB::table('payment_rules')
            ->where('category', $category)
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return array_map(fn($record) => PaymentRule::fromArray((array) $record), $records);
    }

    public function findEffectiveBetween(\DateTime $from, \DateTime $to): array
    {
        $records = DB::table('payment_rules')
            ->where('effective_from', '>=', $from->format('Y-m-d H:i:s'))
            ->where(function ($query) use ($to) {
                $query->where('effective_to', '<=', $to->format('Y-m-d H:i:s'))
                      ->orWhereNull('effective_to');
            })
            ->orderBy('created_at', 'desc')
            ->get()
            ->toArray();

        return array_map(fn($record) => PaymentRule::fromArray((array) $record), $records);
    }
}
