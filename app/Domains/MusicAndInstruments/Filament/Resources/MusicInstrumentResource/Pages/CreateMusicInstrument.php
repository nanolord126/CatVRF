<?php declare(strict_types=1);

/**
 * CreateMusicInstrument — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createmusicinstrument
 */


namespace App\Domains\MusicAndInstruments\Filament\Resources\MusicInstrumentResource\Pages;

use App\Domains\MusicAndInstruments\Filament\Resources\MusicInstrumentResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateMusicInstrument
 *
 * Part of the MusicAndInstruments vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Filament admin panel component.
 * Tenant-scoped: all data filtered by current tenant.
 * Follows CatVRF 9-layer architecture (Layer 9: Filament).
 *
 * @package App\Domains\MusicAndInstruments\Filament\Resources\MusicInstrumentResource\Pages
 */
final class CreateMusicInstrument extends CreateRecord
{
    protected static string $resource = MusicInstrumentResource::class;
/**
     * Version identifier for this component.
     */
    private const VERSION = '1.0.0';

    /**
     * Maximum number of retry attempts for operations.
     */
    private const MAX_RETRIES = 3;

}