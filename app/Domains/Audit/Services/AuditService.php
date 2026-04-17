<?php declare(strict_types=1);

namespace App\Domains\Audit\Services;

use App\Domains\Audit\Models\AuditLog;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Str;
use Psr\Log\LoggerInterface;
use Illuminate\Http\Request;

final readonly class AuditService
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
        private readonly Request $request,
    ) {}

    /**
     * Record audit log entry
     */
    public function record(
        string $action,
        string $subjectType,
        ?int $subjectId,
        array $oldValues = [],
        array $newValues = [],
        ?string $correlationId = null,
    ): void {
        $correlationId ??= Str::uuid()->toString();

        $data = [
            'tenant_id' => function_exists('tenant') && tenant() ? tenant()->id : null,
            'business_group_id' => $this->request->get('business_group_id'),
            'user_id' => $this->request->user()?->id,
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $this->request->ip(),
            'device_fingerprint' => hash('sha256', $this->request->ip() . $this->request->userAgent()),
            'correlation_id' => $correlationId,
        ];

        $this->logger->info($action, [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'correlation_id' => $correlationId,
            'user_id' => $data['user_id'],
            'tenant_id' => $data['tenant_id'],
        ]);

        AuditLog::create($data);
    }

    /**
     * Get audit logs for subject
     */
    public function getLogsForSubject(string $subjectType, ?int $subjectId = null, int $limit = 100)
    {
        return AuditLog::bySubject($subjectType, $subjectId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get audit logs by correlation ID
     */
    public function getLogsByCorrelationId(string $correlationId)
    {
        return AuditLog::byCorrelationId($correlationId)
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * Get audit logs for user
     */
    public function getLogsForUser(int $userId, int $limit = 100)
    {
        return AuditLog::where('user_id', $userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }
}
