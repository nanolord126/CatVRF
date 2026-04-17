<?php declare(strict_types=1);

namespace App\Domains\Sports\Filament\Resources\ReviewResource\Pages;

use App\Domains\Sports\Filament\Resources\ReviewResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

final class CreateReview extends CreateRecord
{
    protected static string $resource = ReviewResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
