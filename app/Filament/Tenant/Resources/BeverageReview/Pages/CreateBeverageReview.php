<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageReview\Pages;
use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Resources\Pages\CreateRecord;
final class CreateRecordBeverageReview extends CreateRecord {
    protected static string $resource = BeverageReviewResource::class;
}
