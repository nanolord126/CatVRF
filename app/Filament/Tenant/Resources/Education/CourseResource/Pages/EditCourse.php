<?php declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Education\CourseResource\Pages;




use Illuminate\Database\DatabaseManager;
use Psr\Log\LoggerInterface;
use Illuminate\Contracts\Auth\Guard;
use Filament\Resources\Pages\EditRecord;

final class EditCourse extends EditRecord
{
    public function __construct(
        private readonly DatabaseManager $db,
        private readonly LoggerInterface $logger,
    ) {}


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

            \Illuminate\Support\Facades\Log::channel('audit')->info('User started Education Course edit', [
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
            return $this->db->transaction(function () use ($record, $data) {
                $updatedRecord = parent::handleRecordUpdate($record, $data);

                \Illuminate\Support\Facades\Log::channel('audit')->info('Education Course updated', [
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
            \Illuminate\Support\Facades\Log::channel('audit')->info('Education Course edit successfully finalized', [
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
