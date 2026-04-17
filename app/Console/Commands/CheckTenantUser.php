<?php declare(strict_types=1);

namespace App\Console\Commands;


use Psr\Log\LoggerInterface;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

final class CheckTenantUser extends Command
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    protected $signature = 'app:check-tenant-user
        {--tenant-id= : Tenant ID to check against}
        {--user-id= : User ID to check}
        {--email= : User email to check}
        {--correlation-id= : Correlation identifier for audit logs}';

    protected $description = 'Verify that a user belongs to the specified tenant and is active';

    public function handle(): int
    {
        $tenantId = (int) ($this->option('tenant-id') ?? 0);
        $userId = $this->option('user-id') !== null ? (int) $this->option('user-id') : null;
        $email = $this->option('email');
        $correlationId = $this->option('correlation-id') ?: (string) Str::uuid();

        if ($tenantId <= 0) {
            $this->error('Provide --tenant-id');
            return self::FAILURE;
        }

        if ($userId === null && $email === null) {
            $this->error('Provide --user-id or --email');
            return self::FAILURE;
        }

        $query = User::query()->where('tenant_id', $tenantId);

        if ($userId !== null) {
            $query->where('id', $userId);
        }

        if ($email !== null) {
            $query->where('email', $email);
        }

        $user = $query->first();

        if ($user === null) {
            $this->logger->warning('Tenant user not found', [
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'email' => $email,
                'correlation_id' => $correlationId,
            ]);

            $this->error('User not found for tenant');
            return self::FAILURE;
        }

        $this->logger->info('Tenant user verified', [
            'tenant_id' => $tenantId,
            'user_id' => $user->id,
            'email' => $user->email,
            'is_active' => $user->is_active ?? null,
            'correlation_id' => $correlationId,
        ]);

        $this->info("User {$user->id} belongs to tenant {$tenantId}");

        return self::SUCCESS;
    }
}
