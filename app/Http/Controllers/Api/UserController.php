<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Traits\LogsActivity;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    use LogsActivity;
    /**
     * List users with pagination, search, and role filter.
     * Requires: users.view permission
     */
    public function index(Request $request): JsonResponse
    {
        $query = User::with('roles');

        // Search by name or email
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        // Filter by role slug
        if ($role = $request->input('role')) {
            $query->whereHas('roles', function ($q) use ($role) {
                $q->where('roles.slug', $role);
            });
        }

        // Sorting
        $sortBy = $request->input('sort_by', 'created_at');
        $sortDir = $request->input('sort_dir', 'desc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['name', 'email', 'created_at', 'last_login_at', 'role'];

        if (in_array($sortBy, $allowedSorts)) {
            if ($sortBy === 'role') {
                // Sort by role name using a subquery
                $query->orderBy(
                    Role::select('name')
                        ->join('role_user', 'roles.id', '=', 'role_user.role_id')
                        ->whereColumn('role_user.user_id', 'users.id')
                        ->orderBy('roles.name')
                        ->limit(1),
                    $sortDir
                );
            } else {
                $query->orderBy($sortBy, $sortDir);
            }
        }

        $perPage = min((int) $request->input('per_page', 10), 100);
        $users = $query->paginate($perPage);

        return response()->json($users);
    }

    /**
     * Create a new user.
     * Requires: users.create permission
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role_ids' => 'required|array',
            'role_ids.*' => 'exists:roles,id',
            'permission_ids' => 'nullable|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => 'user', // Legacy column default
        ]);

        // Attach roles
        $user->roles()->sync($validated['role_ids']);

        // Attach direct permissions if provided
        if (! empty($validated['permission_ids'])) {
            $user->directPermissions()->sync($validated['permission_ids']);
        }

        $this->logActivity('user_created', "User \"{$user->name}\" was created.");

        $user->load('roles', 'directPermissions');

        return response()->json([
            'message' => 'User created successfully.',
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('slug'),
        ], 201);
    }

    /**
     * Get a single user with roles and permissions.
     * Requires: users.view permission
     */
    public function show($id): JsonResponse
    {
        $user = User::with('roles', 'directPermissions')->findOrFail($id);

        return response()->json([
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('slug'),
            'roles' => $user->roles->pluck('slug'),
        ]);
    }

    /**
     * Update a user.
     * Requires: users.edit permission
     */
    public function update(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();

        // Prevent editing own roles if not super_admin
        if ($user->id === $currentUser->id && ! $currentUser->isSuperAdmin() && $request->has('role_ids')) {
            return response()->json([
                'message' => 'You cannot edit your own roles unless you are a super admin.',
            ], 403);
        }

        // Prevent removing super_admin role from the last super_admin
        if ($request->has('role_ids')) {
            $superAdminRole = Role::where('slug', 'super_admin')->first();
            if ($superAdminRole && $user->hasRole('super_admin')) {
                $requestedRoleIds = $request->input('role_ids', []);
                if (! in_array($superAdminRole->id, $requestedRoleIds)) {
                    // Check if this is the last super_admin
                    $superAdminCount = $superAdminRole->users()->count();
                    if ($superAdminCount <= 1) {
                        return response()->json([
                            'message' => 'Cannot remove super_admin role from the last super admin user.',
                        ], 403);
                    }
                }
            }
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => ['sometimes', 'required', 'email', Rule::unique('users')->ignore($user->id)],
            'password' => 'nullable|string|min:8',
            'role_ids' => 'sometimes|array',
            'role_ids.*' => 'exists:roles,id',
            'permission_ids' => 'sometimes|array',
            'permission_ids.*' => 'exists:permissions,id',
        ]);

        // Update basic fields
        if (isset($validated['name'])) {
            $user->name = $validated['name'];
        }
        if (isset($validated['email'])) {
            $user->email = $validated['email'];
        }
        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }
        $user->save();

        // Sync roles if provided
        if (isset($validated['role_ids'])) {
            $user->roles()->sync($validated['role_ids']);
        }

        // Sync direct permissions if provided
        if (isset($validated['permission_ids'])) {
            $user->directPermissions()->sync($validated['permission_ids']);
        }

        $this->logActivity('user_updated', "User \"{$user->name}\" was updated.");

        $user->load('roles', 'directPermissions');

        return response()->json([
            'message' => 'User updated successfully.',
            'user' => $user,
            'permissions' => $user->getAllPermissions()->pluck('slug'),
        ]);
    }

    /**
     * Delete a user.
     * Requires: users.delete permission
     */
    public function destroy(Request $request, $id): JsonResponse
    {
        $user = User::findOrFail($id);
        $currentUser = $request->user();

        // Prevent self-deletion
        if ($user->id === $currentUser->id) {
            return response()->json([
                'message' => 'You cannot delete your own account.',
            ], 403);
        }

        // Prevent deleting the last super_admin
        if ($user->hasRole('super_admin')) {
            $superAdminRole = Role::where('slug', 'super_admin')->first();
            if ($superAdminRole && $superAdminRole->users()->count() <= 1) {
                return response()->json([
                    'message' => 'Cannot delete the last super admin user.',
                ], 403);
            }
        }

        $this->logActivity('user_deleted', "User \"{$user->name}\" was deleted.");
        $user->delete();

        return response()->json([
            'message' => 'User deleted successfully.',
        ]);
    }
}
