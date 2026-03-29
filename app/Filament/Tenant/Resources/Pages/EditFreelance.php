<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\Freelance\Pages;

use use App\Filament\Tenant\Resources\FreelanceResource;;
use Filament\Resources\Pages\EditRecord;
use Filament\Actions\{ViewAction, DeleteAction};

final class EditFreelance extends EditRecord
{
    protected static string $resource = FreelanceResource::class;

    public function getTitle(): string
    {
        return 'Edit Freelance';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}