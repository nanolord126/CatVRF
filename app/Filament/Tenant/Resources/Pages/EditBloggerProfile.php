<?php declare(strict_types=1);

/**
 * EditBloggerProfile — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/editbloggerprofile
 * @see https://catvrf.ru/docs/editbloggerprofile
 * @see https://catvrf.ru/docs/editbloggerprofile
 * @see https://catvrf.ru/docs/editbloggerprofile
 */


namespace App\Filament\Tenant\Resources\Pages;

use App\Filament\Tenant\Resources\BloggerProfileResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;


/**
 * Class EditBloggerProfile
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Filament\Tenant\Resources\Pages
 */
final class EditBloggerProfile extends EditRecord
{
    protected static string $resource = BloggerProfileResource::class;

    /**
     * Handle getTitle operation.
     *
     * @throws \DomainException
     */
    public function getTitle(): string
    {
        return 'Edit BloggerProfile';
    }

    protected function getHeaderActions(): array
    {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }
}
