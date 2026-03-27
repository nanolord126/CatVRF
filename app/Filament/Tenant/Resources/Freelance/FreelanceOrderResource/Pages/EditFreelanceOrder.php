<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource\Pages;

use App\Filament\Tenant\Resources\Freelance\FreelanceOrderResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditFreelanceOrder extends EditRecord
{
    protected static string $resource = FreelanceOrderResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
