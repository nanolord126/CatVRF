<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BloggerProfile\Pages;
use App\Filament\Tenant\Resources\BloggerProfileResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBloggerProfile extends ListRecords {
    protected static string $resource = BloggerProfileResource::class;
}
