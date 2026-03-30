<?php declare(strict_types=1);

namespace App\OpenApi;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class OpenApiSpec extends Model
{
    use HasFactory;

    // TODO: Проверить и восстановить содержимое класса, если оно было утеряно
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
