<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BloggerProfile\Pages;
use App\Filament\Tenant\Resources\BloggerProfileResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordBloggerProfile extends EditRecord {
    protected static string $resource = BloggerProfileResource::class;
}
