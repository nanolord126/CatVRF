<?php declare(strict_types=1);

namespace App\Filament\Actions;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class RunAIConstructorAction extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
    public static function getDefaultName(): ?string
        {
            return 'run_ai_constructor';
        }

        protected function setUp(): void
        {
            $this
                ->label('Запустить AI-конструктор')
                ->icon('heroicon-o-sparkles')
                ->fillForm()
                ->form([
                    Select::make('user_id')
                        ->label('Пользователь')
                        ->relationship('user', 'email')
                        ->required()
                        ->searchable(),
                    Select::make('type')
                        ->label('Тип конструктора')
                        ->options([
                            'interior' => 'Дизайн интерьера',
                            'beauty_look' => 'Макияж и причёска',
                            'outfit' => 'Подбор одежды',
                            'cake' => 'Дизайн торта',
                            'menu' => 'Подбор меню',
                        ])
                        ->required(),
                    FileUpload::make('photo')
                        ->label('Фото')
                        ->image()
                        ->imagePreviewHeight(300)
                        ->required(),
                    Textarea::make('prompt')
                        ->label('Описание задачи')
                        ->rows(3)
                        ->placeholder('Опишите, что нужно сделать с фото...')
                        ->nullable(),
                ])
                ->action(fn (array $data) => $this->runConstructor($data))
                ->successNotificationTitle('Конструктор успешно запущен');
        }

        /**
         * Запустить конструктор
         */
        private function runConstructor(array $data): void
        {
            try {
                $user = User::findOrFail($data['user_id']);

                // Получить сервис из контейнера
                $service = \app(AIConstructorService::class);

                // Преобразовать загруженный файл в UploadedFile
                $photoPath = $data['photo'];
                $photo = new \Illuminate\Http\UploadedFile(
                    \Storage::path("livewire-tmp/{$photoPath}"),
                    \basename($photoPath),
                    \mime_content_type(\Storage::path("livewire-tmp/{$photoPath}")),
                );

                // Запустить конструктор
                $result = $service->run(
                    user: $user,
                    type: $data['type'],
                    photo: $photo,
                    params: [
                        'prompt' => $data['prompt'] ?? '',
                    ],
                );

                Log::channel('audit')->info('AI Constructor run from Filament', [
                    'correlation_id' => $result['correlation_id'],
                    'user_id' => $user->id,
                    'type' => $data['type'],
                    'admin_id' => \auth()->id(),
                ]);

                // Показать результаты
                Notification::make()
                    ->title('Конструктор завершён')
                    ->body("Создано {$result['construction']->type} с уверенностью {$result['confidence']}")
                    ->success()
                    ->send();
            } catch (\Throwable $e) {
                Log::channel('audit')->error('AI Constructor Filament action failed', [
                    'error' => $e->getMessage(),
                    'admin_id' => \auth()->id(),
                ]);

                Notification::make()
                    ->title('Ошибка')
                    ->body($e->getMessage())
                    ->danger()
                    ->send();
            }
        }
}
