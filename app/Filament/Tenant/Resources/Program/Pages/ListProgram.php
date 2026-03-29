<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Program\Pages;
use App\Filament\Tenant\Resources\ProgramResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsProgram extends ListRecords {
    protected static string $resource = ProgramResource::class;
}
