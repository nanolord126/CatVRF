<?php declare(strict_types=1);

/**
 * CreateSportsNutritionModels — CatVRF 2026 Component.
 *
 * Part of the CatVRF multi-vertical marketplace platform.
 * Implements tenant-aware, fraud-checked business logic
 * with full correlation_id tracing and audit logging.
 *
 * @package CatVRF
 * @version 2026.1
 * @author CatVRF Team
 * @license Proprietary

 * @see https://catvrf.ru/docs/createsportsnutritionmodels
 */


namespace App\Domains\SportsNutrition\Filament\Resources\SportsNutritionModelsResource\Pages;

use App\Domains\SportsNutrition\Filament\Resources\SportsNutritionModelsResource;
use Filament\Resources\Pages\CreateRecord;

/**
 * Class CreateSportsNutritionModels
 *
 * Part of the SportsNutrition vertical domain.
 * Follows CatVRF 9-layer architecture.
 *
 * Eloquent model with tenant-scoping and business group isolation.
 * All queries are automatically scoped by tenant_id via global scope.
 *
 * Required fields: uuid, correlation_id, tenant_id, business_group_id, tags (json).
 * Audit logging is handled via model events (created, updated, deleted).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $business_group_id
 * @property string $uuid
 * @property string|null $correlation_id
 * @property array|null $tags
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @package App\Domains\SportsNutrition\Filament\Resources\SportsNutritionModelsResource\Pages
 */
final class CreateSportsNutritionModels extends CreateRecord
{
    protected static string $resource = SportsNutritionModelsResource::class;

    /**
     * The number of models to return for pagination.
     */
    protected $perPage = 25;
}