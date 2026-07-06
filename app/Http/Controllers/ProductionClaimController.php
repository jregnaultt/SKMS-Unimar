<?php

namespace App\Http\Controllers;

use App\Events\ClaimApproved;
use App\Events\ClaimRejected;
use App\Events\ClaimSubmitted;
use App\Models\Production;
use App\Models\ProductionClaim;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionClaimController extends Controller
{
    /**
     * Store a newly created claim in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'production_id' => 'required|exists:productions,id',
            'role' => 'required|in:author,tutor',
        ]);

        $user = $request->user();
        $productionId = $request->input('production_id');
        $role = $request->input('role');

        // Validation for role/user type mismatches
        if ($role === 'author' && ! $user->hasRole('Estudiante')) {
            return back()->with('error', 'Solo los estudiantes pueden reclamar la autoría de un trabajo.');
        }

        if ($role === 'tutor' && ! $user->hasRole('Tutor')) {
            return back()->with('error', 'Solo los tutores pueden reclamar la tutoría de un trabajo.');
        }

        // Check if user is already linked with this role
        $alreadyLinked = $user->productions()
            ->where('production_id', $productionId)
            ->wherePivot('role', $role)
            ->exists();

        if ($alreadyLinked) {
            return back()->with('error', 'Ya estás oficialmente registrado como '.($role === 'author' ? 'autor' : 'tutor').' de este trabajo.');
        }

        // Check if there is already a pending or approved claim for this role and user on this production
        $existingClaim = ProductionClaim::where('production_id', $productionId)
            ->where('user_id', $user->id)
            ->where('role', $role)
            ->whereIn('status', ['pending', 'approved'])
            ->first();

        if ($existingClaim) {
            $statusText = $existingClaim->status === 'pending' ? 'pendiente de revisión' : 'aprobada';

            return back()->with('error', "Ya tienes una solicitud {$statusText} para este trabajo.");
        }

        $claim = ProductionClaim::create([
            'production_id' => $productionId,
            'user_id' => $user->id,
            'role' => $role,
            'status' => 'pending',
        ]);

        event(new ClaimSubmitted($claim));

        return back()->with('success', 'Solicitud de reclamación enviada con éxito. Será revisada por la Coordinación de Investigación.');
    }

    /**
     * Display a listing of pending claims.
     */
    public function index(Request $request): View
    {
        // Spatie HasRoles method or simple check
        if (! $request->user()->hasAnyRole(['Coordinador', 'Super Admin', 'coordinador', 'admin'])) {
            abort(403, 'No tienes autorización para acceder a esta sección.');
        }

        $claims = ProductionClaim::with(['user', 'production'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(15);

        return view('admin.claims.index', compact('claims'));
    }

    /**
     * Approve the specified claim.
     */
    public function approve(Request $request, ProductionClaim $claim): RedirectResponse
    {
        if (! $request->user()->hasAnyRole(['Coordinador', 'Super Admin', 'coordinador', 'admin'])) {
            abort(403, 'No tienes autorización para realizar esta acción.');
        }

        if ($claim->status !== 'pending') {
            return back()->with('error', 'Esta reclamación ya ha sido procesada.');
        }

        // Update claim status
        $claim->update([
            'status' => 'approved',
            'resolved_by' => $request->user()->id,
        ]);

        // Link user to production using the pivot table
        $claim->production->users()->attach($claim->user_id, [
            'role' => $claim->role,
        ]);

        event(new ClaimApproved($claim));

        return back()->with('success', 'La reclamación ha sido aprobada con éxito. El trabajo se ha vinculado al usuario.');
    }

    /**
     * Reject the specified claim.
     */
    public function reject(Request $request, ProductionClaim $claim): RedirectResponse
    {
        if (! $request->user()->hasAnyRole(['Coordinador', 'Super Admin', 'coordinador', 'admin'])) {
            abort(403, 'No tienes autorización para realizar esta acción.');
        }

        if ($claim->status !== 'pending') {
            return back()->with('error', 'Esta reclamación ya ha sido procesada.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|max:1000',
        ]);

        // Update claim status
        $claim->update([
            'status' => 'rejected',
            'resolved_by' => $request->user()->id,
            'rejection_reason' => $request->input('rejection_reason'),
        ]);

        event(new ClaimRejected($claim));

        return back()->with('success', 'La reclamación ha sido rechazada.');
    }
}
