<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Auth\SanctumTokenService;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Log\LogManager;

final class RotateSanctumTokens extends Command
{
    protected $signature = 'sanctum:rotate
                            {--user-id= : Specific user ID to rotate tokens for}
                            {--force : Force rotation even if not marked}
                            {--cleanup : Only cleanup expired tokens}';

    protected $description = 'Rotate Sanctum tokens for users marked for rotation or specific user';

    public function __construct(
        private readonly SanctumTokenService $tokenService,
        private readonly LogManager $log,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        if ($this->option('cleanup')) {
            return $this->cleanupExpired();
        }

        $userId = $this->option('user-id');
        $force = $this->option('force');

        if ($userId !== null) {
            return $this->rotateForUser((int) $userId, $force);
        }

        return $this->rotateAllMarked($force);
    }

    private function rotateForUser(int $userId, bool $force): int
    {
        $user = User::find($userId);

        if ($user === null) {
            $this->error("User with ID {$userId} not found.");

            return Command::FAILURE;
        }

        if (! $force && ! $this->tokenService->needsRotation($user)) {
            $this->info("User {$userId} is not marked for rotation. Use --force to rotate anyway.");

            return Command::SUCCESS;
        }

        $newToken = $this->tokenService->rotateTokens($user);

        if ($newToken === null) {
            $this->info("No active tokens found for user {$userId}.");

            return Command::SUCCESS;
        }

        $this->info("Tokens rotated for user {$userId}.");
        $this->info("New token: {$newToken->plainTextToken}");
        $this->warn('Store this token securely. It will not be shown again.');

        return Command::SUCCESS;
    }

    private function rotateAllMarked(bool $force): int
    {
        $this->info('Starting token rotation for marked users...');

        $users = User::whereHas('tokens', function ($query) {
            $query->where('revoked', false);
        })->get();

        $rotatedCount = 0;
        $skippedCount = 0;

        foreach ($users as $user) {
            if ($force || $this->tokenService->needsRotation($user)) {
                $this->tokenService->rotateTokens($user);
                $rotatedCount++;
                $this->line("Rotated tokens for user {$user->id}");
            } else {
                $skippedCount++;
            }
        }

        $this->info("Rotation complete. Rotated: {$rotatedCount}, Skipped: {$skippedCount}");

        $this->log->info('Sanctum token rotation completed', [
            'rotated_count' => $rotatedCount,
            'skipped_count' => $skippedCount,
            'forced' => $force,
        ]);

        return Command::SUCCESS;
    }

    private function cleanupExpired(): int
    {
        $this->info('Cleaning up expired tokens...');

        $count = $this->tokenService->cleanupExpiredTokens();

        $this->info("Cleaned up {$count} expired tokens.");

        return Command::SUCCESS;
    }
}
