<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Taxi\DriverResource\Pages;
use App\Filament\Tenant\Resources\Taxi\DriverResource;
use Filament\Resources\Pages\CreateRecord;
class CreateDriver extends CreateRecord { protected static string $resource = DriverResource::class; }
