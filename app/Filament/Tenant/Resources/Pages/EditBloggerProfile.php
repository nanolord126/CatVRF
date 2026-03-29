<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BloggerProfile\Pages;

use use App\Filament\Tenant\Resources\BloggerProfileResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditBloggerProfile extends EditRecord
{
    protected static string $resource = BloggerProfileResource::class;

    public function getTitle(): string
    {
        return 'Edit BloggerProfile';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}