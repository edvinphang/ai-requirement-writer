<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class TemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->words(2, true),
            'type' => $this->faker->randomElement(['webapp', 'mobile', 'api', 'data', 'custom']),
            'fields' => [
                ['key' => 'project_name', 'label' => 'Project Name', 'type' => 'text', 'required' => true],
                ['key' => 'problem', 'label' => 'Problem Statement', 'type' => 'textarea', 'required' => true],
            ],
        ];
    }
}
