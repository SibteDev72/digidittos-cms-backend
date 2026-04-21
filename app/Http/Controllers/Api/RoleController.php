<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RoleController extends Controller
{
    /**
     * List all roles with permission counts and user counts.
     * Requires: roles.view permission
     */
    public function index(): JsonResponse
    {
        $roles = Role::withCount(['permissions', 'users'])->get();

        return response()->json([
            'roles' => $roles,
        ]);
    }

    /**
     * Create a custom role.
     * Requires: roles.create permission
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'slug' => 'required|string|max:255|unique:roles,slug',
            'description' => 'nullable|string|max:500',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
            'is_system' => false,
        ]);

        if (! empty($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role created successfully.',
            'role' => $role,
        ], 201);
    }

    /**
     * Get a role with its permissions.
     * Requires: roles.view permission
     */
    public function show($id): JsonResponse
    {
        $role = Role::with('permissions')->withCount('users')->findOrFail($id);

        return response()->json([
            'role' => $role,
        ]);
    }

    /**
     * Update a role.
     * Requires: roles.edit permission
     */
    public function update(Request $request, $id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Build validation rules - prevent editing system role name/slug
        $rules = [
            'description' => 'nullable|string|max:500',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ];

        if (! $role->is_system) {
            $rules['name'] = 'sometimes|required|string|max:255|unique:roles,name,' . $role->id;
            $rules['slug'] = 'sometimes|required|string|max:255|unique:roles,slug,' . $role->id;
        }

        $validated = $request->validate($rules);

        // Update fields (prevent name/slug change on system roles)
        if (! $role->is_system) {
            if (isset($validated['name'])) {
                $role->name = $validated['name'];
            }
            if (isset($validated['slug'])) {
                $role->slug = $validated['slug'];
            }
        } elseif ($request->has('name') || $request->has('slug')) {
            return response()->json([
                'message' => 'Cannot modify the name or slug of a system role.',
            ], 403);
        }

        if (array_key_exists('description', $validated)) {
            $role->description = $validated['description'];
        }

        $role->save();

        // Sync permissions (allowed even for system roles)
        if (isset($validated['permission_ids'])) {
            $role->permissions()->sync($validated['permission_ids']);
        }

        $role->load('permissions');

        return response()->json([
            'message' => 'Role updated successfully.',
            'role' => $role,
        ]);
    }

    /**
     * Delete a role.
     * Requires: roles.delete permission
     */
    public function destroy($id): JsonResponse
    {
        $role = Role::findOrFail($id);

        // Prevent deleting system roles
        if ($role->is_system) {
            return response()->json([
                'message' => 'Cannot delete a system role.',
            ], 403);
        }

        // Detach permissions and users, then delete the role
        $role->permissions()->detach();
        $role->users()->detach();
        $role->delete();

        return response()->json([
            'message' => 'Role deleted successfully. Affected users have been detached from this role.',
        ]);
    }
}
