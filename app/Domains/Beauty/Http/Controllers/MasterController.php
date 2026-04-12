<?php
declare(strict_types=1);

namespace App\Domains\Beauty\Http\Controllers;

use App\Http\Controllers\Api\V1\Beauty\MasterController as BaseMasterController;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * MasterController — Domain-level controller proxy (Beauty vertical).
 * Delegates all standard CRUD operations to base API controller.
 * Override individual methods here for domain-specific behaviour.
 *
 * @see \App\Http\Controllers\Api\V1\Beauty\BaseMasterController
 */
final class MasterController extends BaseMasterController
{
    /**
     * Display a listing of the resource.
     * Inherits tenant-scoped index from base controller.
     */
    public function index(Request $request): JsonResponse
    {
        return parent::index($request);
    }

    /**
     * Display the specified resource.
     * Inherits full detail view with relationships from base.
     */
    public function show(int $id, Request $request): JsonResponse
    {
        return parent::show($id, $request);
    }

    /**
     * Store a newly created resource in storage.
     * Inherits FraudControlService::check() + DB::transaction() from base.
     */
    public function store(Request $request): JsonResponse
    {
        return parent::store($request);
    }

    /**
     * Update the specified resource in storage.
     * Inherits fraud check + audit log from base.
     */
    public function update(int $master, Request $request): JsonResponse
    {
        return parent::update($master, $request);
    }

    /**
     * Remove the specified resource from storage.
     * Inherits soft-delete + audit from base.
     */
    public function destroy(int $master, Request $request): JsonResponse
    {
        return parent::destroy($master, $request);
    }
}
