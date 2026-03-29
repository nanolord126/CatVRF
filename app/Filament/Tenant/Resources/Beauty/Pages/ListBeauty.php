<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Beauty\Pages;
use App\Filament\Tenant\Resources\BeautyResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeauty extends ListRecords {
    protected static string $resource = BeautyResource::class;
}
