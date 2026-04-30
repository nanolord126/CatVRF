<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Pages;

use Psr\Log\LoggerInterface;

use Illuminate\Http\Request;
use App\Filament\Tenant\Resources\BloggerProfileResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Log\LogManager;
use Illuminate\Support\Str;

final class CreateBloggerProfile extends CreateRecord
{
    protected static string $resource = BloggerProfileResource::class;

    public function __construct(private readonly LoggerInterface $logger,
        private readonly Request $request,
        private readonly LogManager $log,) {}

    public function getTitle(): string
    {
        return 'Создание профиля блогера';
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['uuid'] = $data['uuid'] ?? (string) Str::uuid();
        $data['correlation_id'] = $data['correlation_id'] ?? (string) ($this->request->header('X-Correlation-ID') ?? Str::uuid());
        $data['tenant_id'] = $data['tenant_id'] ?? $this->guard->user()?->tenant_id;
        $data['business_group_id'] = $data['business_group_id'] ?? $this->guard->user()?->business_group_id;
        $data['verification_status'] = $data['verification_status'] ?? 'pending';
        $data['is_active'] = $data['is_active'] ?? true;
        $data['total_streams'] = $data['total_streams'] ?? 0;
        $data['total_viewers'] = $data['total_viewers'] ?? 0;
        $data['total_earned'] = $data['total_earned'] ?? 0;
        $data['wallet_balance'] = $data['wallet_balance'] ?? 0;

        return $data;
    }

    protected function afterCreate(): void
    {
        $this->log->channel('audit')->$this->logger->info('Blogger profile created', [
            'profile_id' => $this->record->id ?? null,
            'tenant_id' => $this->record->tenant_id ?? null,
            'correlation_id' => $this->record->correlation_id ?? null,
        ]);
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
