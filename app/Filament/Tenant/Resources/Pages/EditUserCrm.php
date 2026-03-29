<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\UserCrm\Pages;

use use App\Filament\Tenant\Resources\UserCrmResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditUserCrm extends EditRecord
{
    protected static string $resource = UserCrmResource::class;

    public function getTitle(): string
    {
        return 'Edit UserCrm';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}