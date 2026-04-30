<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domains\Audit\Jobs\AsyncAuditLogger;

/**
 * AuditLogJob — Backward compatibility alias.
 * 
 * @deprecated 2026-04-28 Use App\Domains\Audit\Jobs\AsyncAuditLogger instead.
 * This class exists for backward compatibility during migration.
 * 
 * @see \App\Domains\Audit\Jobs\AsyncAuditLogger
 */
final class AuditLogJob extends AsyncAuditLogger
{
    // All functionality inherited from Domain job
}
