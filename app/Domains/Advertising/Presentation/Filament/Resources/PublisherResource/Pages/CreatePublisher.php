<?php

declare(strict_types=1);

namespace App\Domains\Advertising\Presentation\Filament\Resources\PublisherResource\Pages;

use App\Domains\Advertising\Presentation\Filament\Resources\PublisherResource;
use Filament\Pages\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreatePublisher extends CreateRecord
{
    protected static string $resource = PublisherResource::class;
}
