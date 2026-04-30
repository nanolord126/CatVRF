<?php

declare(strict_types=1);

namespace Modules\Payment\Domain\Repositories;

use Modules\Payment\Domain\Entities\PaymentRule;

interface PaymentRuleRepositoryInterface
{
    public function save(PaymentRule $rule): void;
    
    public function findByUuid(string $uuid): ?PaymentRule;
    
    public function findByCode(string $code): array;
    
    public function findByCategory(string $category): array;
    
    public function findEffectiveBetween(\DateTime $from, \DateTime $to): array;
}
