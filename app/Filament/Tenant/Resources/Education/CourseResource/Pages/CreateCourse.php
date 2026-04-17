<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\CreateRecord;

final class CreateCourse extends CreateRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}


    protected static string $resource = CourseResource::class;

        /**
         * Валидация + фрод-контроль перед сохранением
         */
        protected function beforeCreate(): void
        {
            $correlationId = $this->data['correlation_id'] ?? (string) Str::uuid();

            // 1. Фрод-проверка на создание контента
            app(FraudControlService::class)->checkOperation('create_education_course', [
                'tenant_id' => tenant()->id,
                'user_id' => auth()->id(),
                'correlation_id' => $correlationId
            ]);

            \Illuminate\Support\Facades\Log::channel('audit')->info('Creating new Education Course', [
                'tenant_id' => tenant()->id,
                'correlation_id' => $correlationId,
            ]);
        }

        /**
         * Сохранение в транзакции
         */
        protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model
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
            \Illuminate\Support\Facades\Log::channel('audit')->info('Education Course created successfully', [
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
