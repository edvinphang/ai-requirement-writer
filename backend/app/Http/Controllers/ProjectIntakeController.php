<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProjectIntakeController extends Controller
{
    public function store(Request $request, Project $project): JsonResponse
    {
        if ($project->user_id !== $request->user()->id) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $request->validate([
            'fields' => ['required', 'array'],
        ]);

        $intake = $project->intake()->updateOrCreate([], ['fields' => $request->fields]);

        return response()->json(['data' => $intake]);
    }
}
