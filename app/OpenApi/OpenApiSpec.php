<?php

declare(strict_types=1);


namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *     title="CatVRF API",
 *     version="1.0.0",
 *     description="Production-ready marketplace API with multi-version support, full security, rate limiting, RBAC",
 *     contact={
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
