<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RolePermissionSeeder extends Seeder
{
    /**
     * Seed the roles and permissions.
     */
    public function run(): void
    {
        // Create the default super_admin role
        $superAdmin = Role::updateOrCreate(
            ['slug' => 'super_admin'],
            ['name' => 'Super Admin', 'description' => 'Full system access', 'is_system' => true]
        );

        // Define permissions grouped
        $permissionsData = [
            // Users group
            ['name' => 'View Users', 'slug' => 'users.view', 'group' => 'users', 'description' => 'View user listings'],
            ['name' => 'Create Users', 'slug' => 'users.create', 'group' => 'users', 'description' => 'Create new users'],
            ['name' => 'Edit Users', 'slug' => 'users.edit', 'group' => 'users', 'description' => 'Edit existing users'],
            ['name' => 'Delete Users', 'slug' => 'users.delete', 'group' => 'users', 'description' => 'Delete users'],
            ['name' => 'Manage User Roles', 'slug' => 'users.manage-roles', 'group' => 'users', 'description' => 'Assign and remove roles from users'],

            // Roles group
            ['name' => 'View Roles', 'slug' => 'roles.view', 'group' => 'roles', 'description' => 'View role listings'],
            ['name' => 'Create Roles', 'slug' => 'roles.create', 'group' => 'roles', 'description' => 'Create new roles'],
            ['name' => 'Edit Roles', 'slug' => 'roles.edit', 'group' => 'roles', 'description' => 'Edit existing roles'],
            ['name' => 'Delete Roles', 'slug' => 'roles.delete', 'group' => 'roles', 'description' => 'Delete roles'],

            // Blog group
            ['name' => 'View Blog', 'slug' => 'blog.view', 'group' => 'blog', 'description' => 'View blog posts, categories, and tags'],
            ['name' => 'Create Blog', 'slug' => 'blog.create', 'group' => 'blog', 'description' => 'Create blog posts, categories, and tags'],
            ['name' => 'Edit Blog', 'slug' => 'blog.edit', 'group' => 'blog', 'description' => 'Edit blog posts, categories, and tags'],
            ['name' => 'Delete Blog', 'slug' => 'blog.delete', 'group' => 'blog', 'description' => 'Delete blog posts, categories, and tags'],
            ['name' => 'Publish Blog', 'slug' => 'blog.publish', 'group' => 'blog', 'description' => 'Publish, unpublish, and schedule blog posts'],

            // Projects group
            ['name' => 'View Projects', 'slug' => 'projects.view', 'group' => 'projects', 'description' => 'View projects'],
            ['name' => 'Create Projects', 'slug' => 'projects.create', 'group' => 'projects', 'description' => 'Create new projects'],
            ['name' => 'Edit Projects', 'slug' => 'projects.edit', 'group' => 'projects', 'description' => 'Edit existing projects'],
            ['name' => 'Delete Projects', 'slug' => 'projects.delete', 'group' => 'projects', 'description' => 'Delete projects'],
            ['name' => 'Publish Projects', 'slug' => 'projects.publish', 'group' => 'projects', 'description' => 'Publish and unpublish projects'],

            // Services group
            ['name' => 'View Services', 'slug' => 'services.view', 'group' => 'services', 'description' => 'View services'],
            ['name' => 'Create Services', 'slug' => 'services.create', 'group' => 'services', 'description' => 'Create new services'],
            ['name' => 'Edit Services', 'slug' => 'services.edit', 'group' => 'services', 'description' => 'Edit existing services'],
            ['name' => 'Delete Services', 'slug' => 'services.delete', 'group' => 'services', 'description' => 'Delete services'],

            // Features group
            ['name' => 'View Features', 'slug' => 'features.view', 'group' => 'features', 'description' => 'View features'],
            ['name' => 'Create Features', 'slug' => 'features.create', 'group' => 'features', 'description' => 'Create new features'],
            ['name' => 'Edit Features', 'slug' => 'features.edit', 'group' => 'features', 'description' => 'Edit existing features'],
            ['name' => 'Delete Features', 'slug' => 'features.delete', 'group' => 'features', 'description' => 'Delete features'],

            // Pricing group
            ['name' => 'View Pricing', 'slug' => 'pricing.view', 'group' => 'pricing', 'description' => 'View pricing plans and settings'],
            ['name' => 'Create Pricing', 'slug' => 'pricing.create', 'group' => 'pricing', 'description' => 'Create pricing plans and FAQs'],
            ['name' => 'Edit Pricing', 'slug' => 'pricing.edit', 'group' => 'pricing', 'description' => 'Edit pricing plans and settings'],
            ['name' => 'Delete Pricing', 'slug' => 'pricing.delete', 'group' => 'pricing', 'description' => 'Delete pricing plans and FAQs'],

            // Pages group
            ['name' => 'View Pages', 'slug' => 'pages.view', 'group' => 'pages', 'description' => 'View page settings'],
            ['name' => 'Edit Pages', 'slug' => 'pages.edit', 'group' => 'pages', 'description' => 'Edit page settings and sections'],

            // Media group
            ['name' => 'View Media', 'slug' => 'media.view', 'group' => 'media', 'description' => 'View media files'],
            ['name' => 'Upload Media', 'slug' => 'media.upload', 'group' => 'media', 'description' => 'Upload media files'],
            ['name' => 'Delete Media', 'slug' => 'media.delete', 'group' => 'media', 'description' => 'Delete media files'],

            // Settings group
            ['name' => 'View Settings', 'slug' => 'settings.view', 'group' => 'settings', 'description' => 'View system settings'],
            ['name' => 'Edit Settings', 'slug' => 'settings.edit', 'group' => 'settings', 'description' => 'Edit system settings'],

            // SEO group
            ['name' => 'View SEO', 'slug' => 'seo.view', 'group' => 'seo', 'description' => 'View SEO Engine settings and audit'],
            ['name' => 'Edit SEO', 'slug' => 'seo.edit', 'group' => 'seo', 'description' => 'Edit SEO settings, robots.txt, llms.txt, sitemap, and auto-generate'],
            ['name' => 'Delete SEO', 'slug' => 'seo.delete', 'group' => 'seo', 'description' => 'Delete SEO redirects'],

            // Dashboard group
            ['name' => 'View Dashboard', 'slug' => 'dashboard.view', 'group' => 'dashboard', 'description' => 'View dashboard'],
            ['name' => 'View Analytics', 'slug' => 'dashboard.analytics', 'group' => 'dashboard', 'description' => 'View dashboard analytics'],
        ];

        // Create all permissions
        $permissions = [];
        foreach ($permissionsData as $permData) {
            $permissions[$permData['slug']] = Permission::updateOrCreate(
                ['slug' => $permData['slug']],
                $permData
            );
        }

        // Assign ALL permissions to super_admin
        $superAdmin->permissions()->sync(
            collect($permissions)->pluck('id')->toArray()
        );
    }
}
