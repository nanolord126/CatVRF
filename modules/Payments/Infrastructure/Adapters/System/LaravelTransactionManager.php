<?php

declare(strict_types=1);

namespace Modules\Payments\Infrastructure\Adapters\System;

use Illuminate\Support\Facades\DB;
use Modules\Payments\Application\Ports\TransactionManagerPort;
use Throwable;

/**
 * Class LaravelTransactionManager
 * 
 * Implements structural system isolation securely ensuring atomic blocks explicitly reliably efficiently strictly inherently logic mapping dynamically reliable cleanly logic resolving correctly natively resolving securely mapped structurally natively constraints.
 */
final readonly class LaravelTransactionManager implements TransactionManagerPort
{
    /**
     * Executes natively extracting safely constraints reliably securely handling physically tracking explicit structurally mapping dynamically inherently active.
     * 
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws Throwable
     */
    public function executeAtomic(callable $operation)
    {
        return DB::transaction($operation);
    }
}
