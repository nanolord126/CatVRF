<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\FreshProduce\Pages;
use App\Filament\Tenant\Resources\FreshProduceResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFreshProduce extends ListRecords {
    protected static string $resource = FreshProduceResource::class;
}
