<?php

declare(strict_types=1);

namespace App\Filament\Tenant\Resources\BloggerProfile\Pages;

use use App\Filament\Tenant\Resources\BloggerProfileResource;;
use Filament\Resources\Pages\ViewRecord;

final class ViewBloggerProfile extends ViewRecord
{
    protected static string $resource = BloggerProfileResource::class;

    public function getTitle(): string
    {
        return 'View BloggerProfile';
    }
}