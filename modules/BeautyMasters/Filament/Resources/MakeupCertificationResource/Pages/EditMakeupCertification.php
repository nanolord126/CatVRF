<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\MakeupCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditMakeupCertification extends EditRecord
{
    protected static string $resource = MakeupCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
