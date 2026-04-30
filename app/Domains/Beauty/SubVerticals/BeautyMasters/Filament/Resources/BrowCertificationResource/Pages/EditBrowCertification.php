<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\BrowCertificationResource\Pages;

use Modules\BeautyMasters\Filament\Resources\BrowCertificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

final class EditBrowCertification extends EditRecord
{
    protected static string $resource = BrowCertificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
