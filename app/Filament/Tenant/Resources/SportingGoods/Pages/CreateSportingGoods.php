<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\SportingGoods\Pages;
use App\Filament\Tenant\Resources\SportingGoodsResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordSportingGoods extends CreateRecord {
    protected static string $resource = SportingGoodsResource::class;
}
