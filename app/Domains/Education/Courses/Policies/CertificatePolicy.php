<?php

declare(strict_types=1);


namespace App\Domains\Education\Courses\Policies;

use App\Domains\Education\Courses\Models\Certificate;
use App\Models\User;
use Illuminate\Auth\Access\Response;

final /**
 * CertificatePolicy
 * 
 * Основной класс для работы с платформой CatVRF.
 * 
 * @author CatVRF
 * @package %NAMESPACE%
 * @version 1.0.0
 */
class CertificatePolicy
{
    public function viewAny(User $user): Response
    {
        return $this->response->allow();
    }

    public function view(User $user, Certificate $certificate): Response
    {
        if ($user->id === $certificate->student_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }

    public function download(User $user, Certificate $certificate): Response
    {
        if ($user->id === $certificate->student_id || $user->isAdmin()) {
            return $this->response->allow();
        }

        return $this->response->deny('Unauthorized');
    }
}
