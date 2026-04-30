<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\AMLCheck;
use Carbon\CarbonImmutable;

interface AMLCheckRepositoryInterface
{
    public function save(AMLCheck $check): void;
    
    public function findByUuid(string $uuid): ?AMLCheck;
    
    public function findByUserId(int $userId, int $limit = 50, int $offset = 0): array;
    
    public function findReportable(): array;
    
    public function deleteOlderThan(CarbonImmutable $date): int;
}
