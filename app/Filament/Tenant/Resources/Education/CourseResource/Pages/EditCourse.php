<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;

use FraudControlService;

use Psr\Log\LoggerInterface;

use Illuminate\Database\DatabaseManager;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;

final class EditCourse extends EditRecord
{
    protected static string $resource = CourseResource::class;

    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Валидация + фрод-контроль перед изменением
     */
    protected function beforeSave(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        // 1. Фрод-проверка на изменение контента
        $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */->checkOperation('edit_education_course', [
            'tenant_id' => tenant()->id,
            'user_id' => auth()->id(),
            'correlation_id' => $correlationId,
            'course_id' => $this->record->id,
        ]);

        $this->log->channel('audit')->$this->logger->info('User started Education Course edit', [
            'tenant_id' => tenant()->id,
            'course_id' => $this->record->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Сохранение в транзакции
     */
    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        return $this->db->transaction(function () use ($record, $data) {
            $updatedRecord = parent::handleRecordUpdate($record, $data);

            $this->log->channel('audit')->$this->logger->info('Education Course updated', [
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
        $this->log->channel('audit')->$this->logger->info('Education Course edit successfully finalized', [
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
