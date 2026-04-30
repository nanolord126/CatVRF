<?php

declare(strict_types=1);

namespace Modules\BeautyMasters\Filament\Resources\Pages;

use Filament\Resources\Pages\ListRecords;
use Modules\BeautyMasters\Filament\Resources\MasterResource;

final class ListMasters extends ListRecords
{
    protected static string $resource = MasterResource::class;
}
