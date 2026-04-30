<?php

declare(strict_types=1);

namespace Modules\Payment\Infrastructure\Repositories;

use Modules\Payment\Domain\Entities\AMLCheck;
use Modules\Payment\Domain\Repositories\AMLCheckRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Carbon\CarbonImmutable;

final readonly class EloquentAMLCheckRepository implements AMLCheckRepositoryInterface
{
    public function save(AMLCheck $check): void
    {
        DB::table('aml_checks')->updateOrInsert(
            ['uuid' => $check->uuid],
            $check->toArray()
        );
    }

    public function findByUuid(string $uuid): ?AMLCheck
    {
        $data = DB::table('aml_checks')->where('uuid', $uuid)->first();

        return $data ? AMLCheck::fromArray((array) $data) : null;
    }

    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array
    {
        $records = DB::table('aml_checks')
            ->where('user_id', $userId)
            ->orderBy('checked_at', 'desc')
            ->offset($offset)
            ->limit($limit)
            ->get()
            ->toArray();

        return array_map(fn($record) => AMLCheck::fromArray((array) $record), $records);
    }

    public function findReportable(): array
    {
        $records = DB::table('aml_checks')
            ->where('is_reported_to_rosfinmonitoring', false)
            ->where(function ($query) {
                $query->where('risk_level', 'critical')
                      ->orWhere('amount_kopecks', '>', 100_000_00); // > 1M RUB
            })
            ->orderBy('checked_at', 'asc')
            ->limit(100)
            ->get()
            ->toArray();

        return array_map(fn($record) => AMLCheck::fromArray((array) $record), $records);
    }

    public function deleteOlderThan(CarbonImmutable $date): int
    {
        return DB::table('aml_checks')
            ->where('checked_at', '<', $date->toDateTimeString())
            ->delete();
    }
}
