<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'name' => 'Web Application',
                'type' => 'webapp',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Mobile Application',
                'type' => 'mobile',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'platform', 'label' => 'Platform (iOS / Android / Both)', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'API / Microservice',
                'type' => 'api',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'consumers', 'label' => 'API Consumers', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Data Pipeline',
                'type' => 'data',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'data_sources', 'label' => 'Data Sources', 'type' => 'textarea', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
            [
                'name' => 'Custom',
                'type' => 'custom',
                'fields' => [
                    ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                    ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
                    ['key' => 'target_users', 'label' => 'Target Users', 'type' => 'text', 'required' => true],
                    ['key' => 'goals', 'label' => 'Goals', 'type' => 'textarea', 'required' => true],
                    ['key' => 'constraints', 'label' => 'Constraints', 'type' => 'textarea', 'required' => false],
                    ['key' => 'stakeholders', 'label' => 'Stakeholders', 'type' => 'text', 'required' => false],
                    ['key' => 'timeline', 'label' => 'Timeline', 'type' => 'text', 'required' => false],
                ],
            ],
        ];

        foreach ($templates as $template) {
            Template::create($template);
        }
    }
}
