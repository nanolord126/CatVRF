<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\AutoPartOrder\Pages;
use App\Filament\Tenant\Resources\AutoPartOrderResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordAutoPartOrder extends CreateRecord {
    protected static string $resource = AutoPartOrderResource::class;
}
