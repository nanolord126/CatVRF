<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\RealEstate\PropertyResource\Pages;
use App\Filament\Tenant\Resources\RealEstate\PropertyResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListProperties extends ListRecords { protected static string $resource = PropertyResource::class; protected function getHeaderActions(): array { return [Actions\CreateAction::make()]; } }
