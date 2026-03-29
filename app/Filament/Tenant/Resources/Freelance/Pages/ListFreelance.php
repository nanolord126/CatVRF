<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\Freelance\Pages;
use App\Filament\Tenant\Resources\FreelanceResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsFreelance extends ListRecords {
    protected static string $resource = FreelanceResource::class;
}
