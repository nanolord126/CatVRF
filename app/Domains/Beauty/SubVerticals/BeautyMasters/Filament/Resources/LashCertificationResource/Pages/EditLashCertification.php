<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\LashCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\LashCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditLashCertification extends EditRecord
{
    protected static string $resource = LashCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
