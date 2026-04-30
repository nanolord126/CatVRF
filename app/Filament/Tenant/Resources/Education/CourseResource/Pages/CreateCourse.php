<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;

use FraudControlService;

use Psr\Log\LoggerInterface;

use Illuminate\Database\DatabaseManager;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Log\LogManager;

final class CreateCourse extends CreateRecord
{
    protected static string $resource = CourseResource::class;

    public function __construct(private readonly FraudControlService $fraudControlService,
        private readonly LoggerInterface $logger,
        private readonly DatabaseManager $db,
        private readonly LogManager $log,) {}

    /**
     * Валидация + фрод-контроль перед сохранением
     */
    protected function beforeCreate(): void
    {
        $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

        // 1. Фрод-проверка на создание контента
        $this->fraudControlService /* TODO: inject via constructor DI */ /* TODO: inject via DI */->checkOperation('create_education_course', [
            'tenant_id' => tenant()->id,
            'user_id' => auth()->id(),
            'correlation_id' => $correlationId,
        ]);

        $this->log->channel('audit')->$this->logger->info('Creating new Education Course', [
            'tenant_id' => tenant()->id,
            'correlation_id' => $correlationId,
        ]);
    }

    /**
     * Сохранение в транзакции
     */
    protected function handleRecordCreation(array $data): Model
    {
        return $this->db->transaction(function () use ($data) {
            $record = parent::handleRecordCreation($data);

            // Дополнительная логика (например, создание первого модуля по умолчанию)
            $record->modules()->create([
                'title' => 'Введение',
                'description' => 'Автоматически созданный модуль',
                'order' => 1,
                'correlation_id' => $data['correlation_id'] ?? (string) Str::uuid(),
            ]);

            return $record;
        });
    }

    /**
     * Пост-эффекты после создания
     */
    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Education Course created successfully', [
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
