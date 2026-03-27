<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;

use App\Filament\Tenant\Resources\Education\CourseResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use App\Services\FraudControlService;
use Illuminate\Support\Facades\DB;

/**
 * КАНОН 2026: EditCourse (Education).
 * Обязательный аудит, транзакции, фрод-контроль.
 */
final class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    /**
     * Валидация + фрод-контроль перед изменением
     */
    protected function beforeSave(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        // 1. Фрод-проверка на изменение контента
        app(FraudControlService::class)->checkOperation('edit_education_course', [
            'tenant_id' => tenant()->id,
            'user_id' => auth()->id(),
            'correlation_id' => $correlationId,
            'course_id' => $this->record->id,
        ]);

        Log::channel('audit')->info('User started Education Course edit', [
            'tenant_id' => tenant()->id,
            'course_id' => $this->record->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Сохранение в транзакции
     */
    protected function handleRecordUpdate(\Illuminate\Database\Eloquent\Model $record, array $data): \Illuminate\Database\Eloquent\Model
    {
        return DB::transaction(function () use ($record, $data) {
            $updatedRecord = parent::handleRecordUpdate($record, $data);

            Log::channel('audit')->info('Education Course updated', [
                'course_id' => $updatedRecord->id,
                'correlation_id' => $data['correlation_id'] ?? (string) Str::uuid(),
            ]);

            return $updatedRecord;
        });
    }

    /**
     * Пост-эффекты после сохранения
     */
    protected function afterSave(): void
    {
        Log::channel('audit')->info('Education Course edit successfully finalized', [
            'course_id' => $this->record->id,
            'correlation_id' => $this->record->correlation_id,
        ]);
    }

    /**
     * Редирект на список после сохранения
     */
    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
