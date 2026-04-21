<?php

namespace Database\Seeders;

use App\Models\ServicePanel;
use Illuminate\Database\Seeder;

class ServicePanelSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $panels = [
            ['name' => 'EHR', 'slug' => 'ehr', 'link_url' => '/services/ehr', 'button_text' => 'View Service', 'sort_order' => 1],
            ['name' => 'Form Factory', 'slug' => 'form-factory', 'link_url' => '/services/form-factory', 'button_text' => 'View Service', 'sort_order' => 2],
            ['name' => 'Clearinghouse', 'slug' => 'clearinghouse', 'link_url' => '/services/clearinghouse', 'button_text' => 'Coming Soon', 'sort_order' => 3],
            ['name' => 'Project Management', 'slug' => 'project-management', 'link_url' => '/services/project-management', 'button_text' => 'Coming Soon', 'sort_order' => 4],
            ['name' => 'CRM', 'slug' => 'crm', 'link_url' => '/services/crm', 'button_text' => 'Coming Soon', 'sort_order' => 5],
            ['name' => 'Website Development', 'slug' => 'web-development', 'link_url' => '/services/web-development', 'button_text' => 'Coming Soon', 'sort_order' => 6],
            ['name' => 'Mobile App Development', 'slug' => 'mobile-app-development', 'link_url' => '/services/mobile-app-development', 'button_text' => 'Coming Soon', 'sort_order' => 7],
            ['name' => 'Software Development', 'slug' => 'software-development', 'link_url' => '/services/software-development', 'button_text' => 'Coming Soon', 'sort_order' => 8],
        ];

        foreach ($panels as $panel) {
            ServicePanel::updateOrCreate(
                ['slug' => $panel['slug']],
                $panel
            );
        }
    }
}
