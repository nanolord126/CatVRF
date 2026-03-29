<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\DentalReview\Pages;
use App\Filament\Tenant\Resources\DentalReviewResource;
use Filament\Resources\Pages\EditRecord;
final class EditRecordDentalReview extends EditRecord {
    protected static string $resource = DentalReviewResource::class;
}
