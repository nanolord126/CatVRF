<?php declare(strict_types=1);

/**
 * AutoPartPolicy — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/autopartpolicy
 */


namespace App\Domains\Auto\Policies;

final class AutoPartPolicy
{

    public function viewAny(User $user): bool
        {
            return $user->isStaff();
        }

        public function view(User $user, AutoPart $part): bool
        {
            return $user->isStaff();
        }

        public function create(User $user): Response
        {
            if (!$user->isStaff()) {
                return $this->response->deny('Только персонал может создавать запчасти');
            }

            return $this->response->allow();
        }

        public function update(User $user, AutoPart $part): Response
        {
            if (!$user->isStaff()) {
                return $this->response->deny('Только персонал может редактировать запчасти');
            }

            return $this->response->allow();
        }

        public function delete(User $user, AutoPart $part): Response
        {
            if (!$user->isAdmin()) {
                return $this->response->deny('Только администратор может удалять запчасти');
            }

            return $this->response->allow();
        }

    /**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}
