<?php

namespace Tests\Feature;

use App\Models\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_templates(): void
    {
        $user = User::factory()->create();
        Template::factory()->count(3)->create();

        $response = $this->actingAs($user)
            ->getJson('/api/templates');

        $response->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure([
                'data' => [['id', 'name', 'type', 'fields']]
            ]);
    }

    public function test_unauthenticated_user_cannot_list_templates(): void
    {
        $response = $this->getJson('/api/templates');
        $response->assertUnauthorized();
    }
}
