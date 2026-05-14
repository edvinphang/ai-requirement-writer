<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\RequirementDraft;
use App\Services\GeminiGenerationService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class GenerationController extends Controller
{
    public function __construct(private GeminiGenerationService $gemini) {}

    public function brd(Request $request, Project $project): StreamedResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        $intake = $project->intake;

        if (! $intake) {
            abort(422, 'Project intake is missing.');
        }

        $draft = $project->drafts()->create([
            'type' => 'brd',
            'version' => $this->nextVersion($project, 'brd'),
        ]);

        return response()->stream(function () use ($intake, $draft) {
            $accumulated = '';
            $this->gemini->streamBrd($intake->fields, function (string $chunk) use (&$accumulated) {
                $accumulated .= $chunk;
                echo 'data: ' . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            });
            $draft->update(['content' => $accumulated]);
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function stories(Request $request, Project $project): StreamedResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'brd_draft_id' => ['required', 'integer'],
        ]);

        $brdDraft = RequirementDraft::findOrFail($request->brd_draft_id);

        if ($brdDraft->status !== 'approved') {
            abort(422, 'BRD draft must be approved before generating stories.');
        }

        $draft = $project->drafts()->create([
            'type' => 'stories',
            'version' => $this->nextVersion($project, 'stories'),
        ]);

        return response()->stream(function () use ($brdDraft, $draft) {
            $accumulated = '';
            $this->gemini->streamStories($brdDraft->content, function (string $chunk) use (&$accumulated) {
                $accumulated .= $chunk;
                echo 'data: ' . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            });
            $draft->update(['content' => $accumulated]);
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    public function spec(Request $request, Project $project): StreamedResponse
    {
        if ($project->user_id !== $request->user()->id) {
            abort(403, 'Forbidden');
        }

        $request->validate([
            'brd_draft_id' => ['required', 'integer'],
            'stories_draft_id' => ['required', 'integer'],
        ]);

        $brdDraft = RequirementDraft::findOrFail($request->brd_draft_id);
        $storiesDraft = RequirementDraft::findOrFail($request->stories_draft_id);

        if ($brdDraft->status !== 'approved') {
            abort(422, 'BRD draft must be approved before generating spec.');
        }

        if ($storiesDraft->status !== 'approved') {
            abort(422, 'Stories draft must be approved before generating spec.');
        }

        $draft = $project->drafts()->create([
            'type' => 'spec',
            'version' => $this->nextVersion($project, 'spec'),
        ]);

        return response()->stream(function () use ($brdDraft, $storiesDraft, $draft) {
            $accumulated = '';
            $this->gemini->streamSpec($brdDraft->content, $storiesDraft->content, function (string $chunk) use (&$accumulated) {
                $accumulated .= $chunk;
                echo 'data: ' . json_encode(['text' => $chunk]) . "\n\n";
                ob_flush();
                flush();
            });
            $draft->update(['content' => $accumulated]);
            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    private function nextVersion(Project $project, string $type): int
    {
        return $project->drafts()->where('type', $type)->max('version') + 1 ?? 1;
    }
}
