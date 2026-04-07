<?php declare(strict_types=1);

namespace App\OpenApi;

/**
 * Class OpenApiSpec
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
 * @package App\OpenApi
 */
final class OpenApiSpec extends Model
{

    *         "name": "CatVRF Support",
     *         "email": "support@catvrf.com"
     *     },
     *     license={
     *         "name": "Proprietary",
     *         "url": "https://catvrf.com/license"
     *     }
     * )
     *
     * @OA\SecurityScheme(
     *     type="http",
     *     scheme="bearer",
     *     bearerFormat="JWT",
     *     securityScheme="bearer_token",
     *     description="Sanctum API authentication token"
     * )
     *
     * @OA\SecurityScheme(
     *     type="apiKey",
     *     in="header",
     *     name="X-API-Key",
     *     securityScheme="api_key"
     * )
     *
     * @OA\Server(
     *     url="/api/v1",
     *     description="API v1"
     * )
     *
     * @OA\Server(
     *     url="/api/v2",
     *     description="API v2"
     * )
     */
    final class OpenApiSpec
    {
}
