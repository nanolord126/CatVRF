<?php

declare(strict_types=1);

namespace Modules\CatCRM\Domain\Staff\Repositories;

use Modules\CatCRM\Domain\Staff\Mentorship;
use Modules\CatCRM\Domain\Staff\ValueObjects\MentorshipId;

/**
 * MentorshipRepositoryInterface — Interface for Mentorship repository
 */
interface MentorshipRepositoryInterface
{
    public function findById(MentorshipId $id): ?Mentorship;

    public function findByMentor(int $tenantId, int $mentorId): array;

    public function findByMentee(int $tenantId, int $menteeId): array;

    public function findActiveByMentor(int $tenantId, int $mentorId): array;

    public function findActiveByMentee(int $tenantId, int $menteeId): ?Mentorship;

    public function save(Mentorship $mentorship): bool;

    public function delete(MentorshipId $id): bool;
}
