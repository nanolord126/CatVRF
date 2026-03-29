<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Fitness\Pages;
use App\Filament\Tenant\Resources\FitnessResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFitness extends ListRecords {
    protected static string $resource = FitnessResource::class;
}
