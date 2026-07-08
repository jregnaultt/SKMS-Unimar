<?php

namespace App\Models;

use App\Enums\CommentStatus;
use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Represents a structured observation or reply within the academic review workflow.
 * Comments follow a strict pending → in_progress → addressed lifecycle.
 */
class Comment extends Model
{
    use HasAuditLog, HasFactory;

    protected $guarded = ['id'];

    /**
     * Attribute casts.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CommentStatus::class,
            'resolved_in_google' => 'boolean',
            'annotation_position' => 'array',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    // ─── Relationships ────────────────────────────────────────────────────────

    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id')->with('user');
    }

    // ─── Scopes ───────────────────────────────────────────────────────────────

    /**
     * Only root-level observations (not replies).
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    /**
     * Only pending observations.
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Pending->value);
    }

    /**
     * Only in-progress observations.
     */
    public function scopeInProgress(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::InProgress->value);
    }

    /**
     * Only addressed (resolved) observations.
     */
    public function scopeAddressed(Builder $query): Builder
    {
        return $query->where('status', CommentStatus::Addressed->value);
    }

    // ─── Helpers ──────────────────────────────────────────────────────────────

    /**
     * Whether this comment is a reply to another comment.
     */
    public function isReply(): bool
    {
        return ! is_null($this->parent_id);
    }

    /**
     * Whether this comment has any replies.
     */
    public function hasReplies(): bool
    {
        return $this->replies()->exists();
    }
}
