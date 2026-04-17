<?php declare(strict_types=1);

namespace App\Domains\Fashion\Http\Controllers;

use App\Domains\Fashion\Services\FashionOnlineStylistService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

final readonly class FashionOnlineStylistController
{
    public function __construct(private FashionOnlineStylistService $stylist) {}

    public function getStyleConsultation(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;

        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $validated = $request->validate(['gender' => 'required|in:men,women,children', 'category' => 'required', 'preferences' => 'array']);

        $result = $this->stylist->getStyleConsultation($userId, $validated['gender'], $validated['category'], $validated['preferences'] ?? [], $correlationId);

        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getMensStyle(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getMensStyle($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getWomensStyle(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getWomensStyle($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getWomensUnderwear(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getWomensUnderwear($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getMensShoes(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getMensShoes($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getWomensShoes(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getWomensShoes($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getChildrensClothing(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getChildrensClothing($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getChildrensShoes(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getChildrensShoes($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getScarves(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getScarvesAndAccessories($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getHeadwear(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getHeadwear($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getCareProducts(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getCareProducts($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getUmbrellas(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getUmbrellas($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getMensAccessories(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getMensAccessories($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }

    public function getWomensAccessories(Request $request): JsonResponse
    {
        $correlationId = $request->header('X-Correlation-ID') ?? Str::uuid()->toString();
        $userId = $request->user()?->id ?? 0;
        if ($userId === 0) throw ValidationException::withMessages(['user' => ['Authentication required']]);

        $result = $this->stylist->getWomensAccessories($userId, $correlationId);
        return response()->json(['success' => true, 'data' => $result, 'correlation_id' => $correlationId]);
    }
}
