<?php

declare(strict_types=1);

namespace App\Models;

use App\Domains\Audit\Models\AuditLog as DomainAuditLog;

/**
 * AuditLog — Backward compatibility alias.
 * 
 * @deprecated 2026-04-28 Use App\Domains\Audit\Models\AuditLog instead.
 * This class exists for backward compatibility during migration.
 * 
 * @see \App\Domains\Audit\Models\AuditLog
 */
final class AuditLog extends DomainAuditLog
{
    // All functionality inherited from Domain model
}
