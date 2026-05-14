<?php

namespace App\Services;

use Gemini\Data\Content;
use Gemini\Laravel\Facades\Gemini;

class GeminiGenerationService
{
    private string $model = 'gemini-2.0-flash';

    /**
     * Stream BRD generation from intake fields.
     *
     * @param  array<string, mixed>  $intakeFields
     */
    public function streamBrd(array $intakeFields, callable $onChunk): void
    {
        $prompt = $this->buildBrdPrompt($intakeFields);
        $this->streamGeneration($this->brdSystemPrompt(), $prompt, $onChunk);
    }

    /**
     * Stream User Stories generation from an approved BRD.
     */
    public function streamStories(string $approvedBrd, callable $onChunk): void
    {
        $prompt = $this->buildStoriesPrompt($approvedBrd);
        $this->streamGeneration($this->storiesSystemPrompt(), $prompt, $onChunk);
    }

    /**
     * Stream Technical Specification generation from an approved BRD and approved stories.
     */
    public function streamSpec(string $approvedBrd, string $approvedStories, callable $onChunk): void
    {
        $prompt = $this->buildSpecPrompt($approvedBrd, $approvedStories);
        $this->streamGeneration($this->specSystemPrompt(), $prompt, $onChunk);
    }

    /**
     * Run a single streaming generation call, invoking $onChunk for each non-empty text chunk.
     */
    private function streamGeneration(string $systemPrompt, string $userPrompt, callable $onChunk): void
    {
        $stream = Gemini::generativeModel(model: $this->model)
            ->withSystemInstruction(Content::parse($systemPrompt))
            ->streamGenerateContent($userPrompt);

        foreach ($stream as $response) {
            $text = $response->text();
            if ($text !== '') {
                $onChunk($text);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $fields
     */
    private function buildBrdPrompt(array $fields): string
    {
        $formatted = collect($fields)
            ->map(fn ($value, $key) => "**{$key}**: {$value}")
            ->implode("\n");

        return "Generate a Business Requirements Document (BRD) based on the following project intake:\n\n{$formatted}";
    }

    private function buildStoriesPrompt(string $brd): string
    {
        return "Based on the following BRD, generate a prioritized list of User Stories with acceptance criteria:\n\n{$brd}";
    }

    private function buildSpecPrompt(string $brd, string $stories): string
    {
        return "Based on the following BRD and User Stories, generate a Technical Specification:\n\n## BRD\n{$brd}\n\n## User Stories\n{$stories}";
    }

    private function brdSystemPrompt(): string
    {
        return 'You are a senior business analyst. Write structured, professional Business Requirements Documents in markdown. Include: Executive Summary, Problem Statement, Goals & Objectives, Stakeholders, Functional Requirements, Non-Functional Requirements, Constraints, and Success Criteria.';
    }

    private function storiesSystemPrompt(): string
    {
        return "You are a senior product manager. Write clear, testable User Stories in the format 'As a [user], I want [goal] so that [benefit]'. Include acceptance criteria for each story. Group stories by epic. Prioritize by business value (Must Have, Should Have, Could Have).";
    }

    private function specSystemPrompt(): string
    {
        return 'You are a senior software architect. Write comprehensive Technical Specifications in markdown. Include: System Overview, Architecture, Data Models, API Contracts, Security Considerations, Performance Requirements, and Implementation Notes.';
    }
}
