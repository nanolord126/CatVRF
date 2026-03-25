<?php

declare(strict_types=1);

namespace App\Domains\HR\Services;

use App\Domains\HR\Models\JobVacancy;
use App\Domains\HR\Models\JobApplication;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Сервис управления персоналом (HR Модуль)
 */
final class HRService
{
    private string $correlationId;

    public function __construct(?string $correlationId = null)
    {
        $this->correlationId = $correlationId ?? (string) Str::uuid();
    }

    /**
     * Создание новой вакансии
     */
    public function createVacancy(array $data, int $tenantId): JobVacancy
    {
        // Fraud Check: лимит на количество активных вакансий в зависимости от тарифа (через сервис)
        FraudControlService::check($this->correlationId);

        return $this->db->transaction(function () use ($data, $tenantId) {
            $vacancy = JobVacancy::create([
                'tenant_id' => $tenantId,
                'business_group_id' => $data['business_group_id'] ?? null,
                'title' => $data['title'],
                'description' => $data['description'],
                'requirements' => $data['requirements'] ?? [],
                'salary_min' => $data['salary_min'] ?? null,
                'salary_max' => $data['salary_max'] ?? null,
                'location' => $data['location'] ?? null,
                'correlation_id' => $this->correlationId,
                'status' => 'open', // По умолчанию открываем а не драфт
            ]);

            $this->log->channel('audit')->info('Job vacancy created', [
                'vacancy_id' => $vacancy->id,
                'tenant_id' => $tenantId,
                'correlation_id' => $this->correlationId,
            ]);

            return $vacancy;
        });
    }

    /**
     * Подача отклика на вакансию
     */
    public function submitApplication(int $vacancyId, int $userId, array $data): JobApplication
    {
        // 1. Fraud Check (защита от спама откликами)
        FraudControlService::check($this->correlationId);

        return $this->db->transaction(function () use ($vacancyId, $userId, $data) {
            $vacancy = JobVacancy::findOrFail($vacancyId);
            
            if ($vacancy->status !== 'open') {
                throw new \RuntimeException('Cannot apply to inactive vacancy');
            }

            $application = JobApplication::create([
                'tenant_id' => $vacancy->tenant_id,
                'vacancy_id' => $vacancyId,
                'user_id' => $userId,
                'resume_url' => $data['resume_url'] ?? null,
                'cover_letter' => $data['cover_letter'] ?? null,
                'correlation_id' => $this->correlationId,
            ]);

            $this->log->channel('audit')->info('Job application submitted', [
                'application_id' => $application->id,
                'vacancy_id' => $vacancyId,
                'user_id' => $userId,
                'correlation_id' => $this->correlationId,
            ]);

            return $application;
        });
    }
}
