<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RequirementDraft extends Model
{
    use HasFactory;

    protected $fillable = ['project_id', 'type', 'version', 'content', 'status'];

    protected $attributes = [
        'status' => 'draft',
        'version' => 1,
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }

    public function approve(): void
    {
        $this->status = 'approved';
        $this->save();
    }
}
