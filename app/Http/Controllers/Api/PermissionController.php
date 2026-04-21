<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use Illuminate\Http\JsonResponse;

class PermissionController extends Controller
{
    /**
     * List all permissions grouped by their group field.
     * Requires: roles.view permission
     */
    public function index(): JsonResponse
    {
        $permissions = Permission::orderBy('group')->orderBy('name')->get();

        $grouped = $permissions->groupBy('group');

        return response()->json([
            'permissions' => $grouped,
        ]);
    }

    /**
     * List all permission groups.
     */
    public function groups(): JsonResponse
    {
        $groups = Permission::select('group')
            ->distinct()
            ->orderBy('group')
            ->pluck('group');

        return response()->json([
            'groups' => $groups,
        ]);
    }
}
