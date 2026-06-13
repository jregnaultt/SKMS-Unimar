<?php

namespace App\Services;

use App\Models\Production;
use App\Models\ProductionClaim;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Class ProductionClaimService
 *
 * Manages suggestions and approvals for historical scientific production claims.
 */
class ProductionClaimService
{
    /**
     * Suggests historical productions that match the user's name.
     *
     * @return Collection<int, Production>
     */
    public function suggestHistoricalProductions(User $user): Collection
    {
        // Split name into parts (ignoring very short words like "de", "del", "y")
        $nameParts = array_filter(
            explode(' ', $user->name),
            fn ($part) => mb_strlen($part) > 2
        );

        if (empty($nameParts)) {
            return collect();
        }

        // Start querying published productions
        $query = Production::query()->where('workflow_state', 'published');

        // Search for any name parts in authors or tutor columns
        $query->where(function ($q) use ($nameParts) {
            foreach ($nameParts as $part) {
                $q->orWhere('authors', 'LIKE', '%'.$part.'%')
                    ->orWhere('tutor', 'LIKE', '%'.$part.'%');
            }
        });

        // Find IDs of productions the user has already claimed (pending or approved)
        $alreadyClaimedIds = ProductionClaim::where('user_id', $user->id)
            ->whereIn('status', ['pending', 'approved'])
            ->pluck('production_id')
            ->toArray();

        // Find IDs of productions the user is already officially linked to
        $alreadyLinkedIds = $user->productions()
            ->pluck('productions.id')
            ->toArray();

        $excludeIds = array_unique(array_merge($alreadyClaimedIds, $alreadyLinkedIds));

        if (! empty($excludeIds)) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->get();
    }
}
