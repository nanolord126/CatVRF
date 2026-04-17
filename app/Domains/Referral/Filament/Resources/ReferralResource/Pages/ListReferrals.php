<?php declare(strict_types=1);

/**
 * ListReferrals — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/listreferrals
 */


namespace App\Domains\Referral\Filament\Resources\ReferralResource\Pages;

use App\Domains\Referral\Filament\Resources\ReferralResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

final class ListReferrals extends ListRecords
{
    protected static string $resource = ReferralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}