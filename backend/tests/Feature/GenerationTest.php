<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\ProjectIntake;
use App\Models\RequirementDraft;
use App\Models\User;
use App\Services\GeminiGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GenerationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    private function createIntake(Project $project): ProjectIntake
    {
        return ProjectIntake::create([
            'project_id' => $project->id,
            'fields' => ['project_name' => 'Test', 'problem' => 'Test problem'],
        ]);
    }

    private function createApprovedDraft(Project $project, string $type, int $version = 1): RequirementDraft
    {
        return RequirementDraft::create([
            'project_id' => $project->id,
            'type' => $type,
            'version' => $version,
            'content' => "Approved {$type} content",
            'status' => 'approved',
        ]);
    }

    public function test_brd_generation_creates_draft_and_streams(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $this->createIntake($project);

        $mock = $this->mock(GeminiGenerationService::class);
        $mock->shouldReceive('streamBrd')
            ->andReturnUsing(function (array $fields, callable $cb) {
                $cb('Generated content');
            });

        $response = $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/brd");

        $response->assertOk();
        $this->assertDatabaseHas('requirement_drafts', [
            'project_id' => $project->id,
            'type' => 'brd',
        ]);
    }

    public function test_brd_generation_fails_without_intake(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/brd")
            ->assertUnprocessable();
    }

    public function test_stories_generation_requires_approved_brd(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $brd = RequirementDraft::create([
            'project_id' => $project->id,
            'type' => 'brd',
            'version' => 1,
            'content' => 'BRD content',
            'status' => 'draft', // NOT approved
        ]);

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/stories", [
                'brd_draft_id' => $brd->id,
            ])
            ->assertUnprocessable();
    }

    public function test_stories_generation_passes_brd_as_context(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $brd = $this->createApprovedDraft($project, 'brd');

        $mock = $this->mock(GeminiGenerationService::class);
        $mock->shouldReceive('streamStories')
            ->andReturnUsing(function (string $brdContent, callable $cb) {
                $cb('Stories content');
            });

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/stories", [
                'brd_draft_id' => $brd->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('requirement_drafts', ['type' => 'stories', 'project_id' => $project->id]);
    }

    public function test_spec_generation_passes_brd_and_stories_as_context(): void
    {
        $project = Project::factory()->create(['user_id' => $this->user->id]);
        $brd = $this->createApprovedDraft($project, 'brd');
        $stories = $this->createApprovedDraft($project, 'stories');

        $mock = $this->mock(GeminiGenerationService::class);
        $mock->shouldReceive('streamSpec')
            ->andReturnUsing(function (string $b, string $s, callable $cb) {
                $cb('Spec content');
            });

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/spec", [
                'brd_draft_id' => $brd->id,
                'stories_draft_id' => $stories->id,
            ])
            ->assertOk();

        $this->assertDatabaseHas('requirement_drafts', ['type' => 'spec', 'project_id' => $project->id]);
    }

    public function test_cannot_generate_for_another_users_project(): void
    {
        $project = Project::factory()->create(); // another user

        $this->actingAs($this->user)
            ->postJson("/api/projects/{$project->id}/generate/brd")
            ->assertForbidden();
    }
}
