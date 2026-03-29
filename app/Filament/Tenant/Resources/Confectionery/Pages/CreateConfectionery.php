<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Confectionery\Pages;
use App\Filament\Tenant\Resources\ConfectioneryResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordConfectionery extends CreateRecord {
    protected static string $resource = ConfectioneryResource::class;
}
