<?php

namespace App\Models;

use App\Models\Traits\HasAuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Class ProductionClaim
 *
 * Represents a claim made by a user to claim authorship or tutorship of a historical production.
 */
class ProductionClaim extends Model
{
    use HasAuditLog;

    protected $guarded = ['id'];

    /**
     * Get the user who claimed the scientific production.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the scientific production that is being claimed.
     */
    public function production(): BelongsTo
    {
        return $this->belongsTo(Production::class);
    }

    /**
     * Get the coordinator who resolved (approved or rejected) the claim.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
