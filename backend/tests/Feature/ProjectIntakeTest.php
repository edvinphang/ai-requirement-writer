<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProjectIntakeTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_save_intake(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/intake", [
                'fields' => ['project_name' => 'My App', 'problem' => 'Too slow'],
            ]);

        $response->assertOk()->assertJsonStructure(['data' => ['id', 'fields']]);
        $this->assertDatabaseHas('project_intakes', ['project_id' => $project->id]);
    }

    public function test_intake_upserts_on_second_submission(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/intake", ['fields' => ['problem' => 'v1']]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/intake", ['fields' => ['problem' => 'v2']]);

        $this->assertDatabaseCount('project_intakes', 1);
    }

    public function test_intake_requires_fields(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/intake", [])
            ->assertUnprocessable();
    }

    public function test_user_cannot_save_intake_for_another_users_project(): void
    {
        $project = Project::factory()->create(); // different user

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/intake", ['fields' => ['x' => 'y']])
            ->assertForbidden();
    }
}
