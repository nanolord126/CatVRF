<?php declare(strict_types=1);

namespace App\Domains\Education\Courses\Filament\Resources\CertificateResource\Pages;

use App\Domains\Education\Courses\Filament\Resources\CertificateResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListCertificates extends ListRecords
{
    protected static string $resource = CertificateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
