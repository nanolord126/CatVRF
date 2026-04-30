<?php

declare(strict_types=1);

namespace App\Filament\Actions;

use AIConstructorService;

use Illuminate\Filesystem\FilesystemManager;

use Illuminate\Notifications\ChannelManager;

use Psr\Log\LoggerInterface;
use Filament\Resources\Resource;
use Illuminate\Http\UploadedFile;

final class RunAIConstructorAction extends Resource
{
    public function __construct(private readonly AIConstructorService $aIConstructorService,
        private readonly FilesystemManager $storage,
        private readonly ChannelManager $notificationManager,
        private readonly LoggerInterface $logger,) {}


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
            $service = \$this->aIConstructorService /* TODO: inject via constructor DI */ /* TODO: inject via DI */;

            // Преобразовать загруженный файл в UploadedFile
            $photoPath = $data['photo'];
            $photo = new UploadedFile(
                \$this->storage->path("livewire-tmp/{$photoPath}"),
                \basename($photoPath),
                \mime_content_type(\$this->storage->path("livewire-tmp/{$photoPath}")),
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

            $this->logger->$this->logger->info('AI Constructor run from Filament', [
                'correlation_id' => $result['correlation_id'],
                'user_id' => $user->id,
                'type' => $data['type'],
                'admin_id' => $this->guard->id(),
            ]);

            // Показать результаты
            $this->notificationManager->make()
                ->title('Конструктор завершён')
                ->body("Создано {$result['construction']->type} с уверенностью {$result['confidence']}")
                ->success()
                ->send();
        } catch (\Throwable $e) {
            $this->logger->error('AI Constructor Filament action failed', [
                'error' => $e->getMessage(),
                'admin_id' => $this->guard->id(),
            ]);

            $this->notificationManager->make()
                ->title('Ошибка')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
}
