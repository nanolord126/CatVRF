<?php
declare(strict_types=1);
namespace App\Filament\Tenant\Resources\BeverageReview\Pages;
use App\Filament\Tenant\Resources\BeverageReviewResource;
use Filament\Resources\Pages\ListRecords;
final class ListRecordsBeverageReview extends ListRecords {
    protected static string $resource = BeverageReviewResource::class;
}
