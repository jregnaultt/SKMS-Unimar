<?php

namespace App\Services;

use App\Events\ProductionStateChanged;
use App\Models\DocumentVersion;
use App\Models\Production;
use App\Models\Revision;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class WorkflowService
{
    public function __construct(
        protected MetadataExtractorService $metadataExtractorService
    ) {}

    /**
     * Map of valid state transitions.
     *
     * @var array<string, array<int, string>>
     */
    protected array $validTransitions = [
        'draft' => ['under_tutor_review'],
        'under_tutor_review' => ['needs_corrections', 'under_jury_review', 'under_coordinator_review', 'rejection_proposed'],
        'under_jury_review' => ['needs_corrections', 'approved', 'rejection_proposed'],
        'under_coordinator_review' => ['needs_corrections', 'approved', 'rejected', 'rejection_proposed'],
        'needs_corrections' => ['under_tutor_review'],
        'approved' => ['published'],
        'published' => [],
        'rejection_proposed' => ['rejected', 'needs_corrections', 'under_tutor_review', 'under_jury_review', 'under_coordinator_review'],
        'rejected' => ['draft'],
    ];

    /**
     * Determine if a user can transition a production to a new state.
     */
    public function canTransition(Production $production, string $targetState, User $user): bool
    {
        $currentState = $production->workflow_state;

        $isSubjectWithoutJury = in_array($production->subject?->code, ['SMI1004341', 'TRI1106341']);

        // 1. Validate state transition path
        $allowed = $this->validTransitions[$currentState] ?? [];
        if (! in_array($targetState, $allowed)) {
            return false;
        }

        // Restrict publication transition only to Trabajo de Investigación II
        if ($targetState === 'published') {
            $subject = $production->subject;
            if (! $subject || $subject->code !== 'TRI1206441') {
                return false;
            }
        }

        // 2. Coordinators and Super Admins can transition anything
        if ($user->hasRole(['Coordinador', 'Super Admin', 'Decano'])) {
            return true;
        }

        // Appeal transition check (Author can appeal exactly once)
        if ($currentState === 'rejected' && $targetState === 'draft') {
            return $this->isProductionUser($production, $user, 'author')
                && $user->hasRole('Estudiante')
                && $production->appeals_count < 1;
        }

        // 3. Check role-conditioned transitions for normal users
        // Students (Authors) can submit drafts or resubmit corrections
        if ($currentState === 'draft' && $targetState === 'under_tutor_review') {
            return $this->isProductionUser($production, $user, 'author') && $user->hasRole('Estudiante');
        }

        if ($currentState === 'needs_corrections' && $targetState === 'under_tutor_review') {
            return $this->isProductionUser($production, $user, 'author') && $user->hasRole('Estudiante');
        }

        // Tutors review under_tutor_review
        if ($currentState === 'under_tutor_review') {
            $allowedTutorStates = ['needs_corrections', 'rejection_proposed'];
            if ($isSubjectWithoutJury) {
                $allowedTutorStates[] = 'under_coordinator_review';
            } else {
                $allowedTutorStates[] = 'under_jury_review';
            }
            if (in_array($targetState, $allowedTutorStates)) {
                return $this->isProductionUser($production, $user, 'tutor') && $user->hasRole('Tutor');
            }
        }

        // Juries review under_jury_review
        if ($currentState === 'under_jury_review') {
            if (in_array($targetState, ['needs_corrections', 'approved', 'rejection_proposed'])) {
                return $this->isProductionUser($production, $user, 'jury') && $user->hasRole('Jurado');
            }
        }

        return false;
    }

    /**
     * Helper to check pivot connection.
     */
    protected function isProductionUser(Production $production, User $user, string $role): bool
    {
        return $production->users()
            ->where('user_id', $user->id)
            ->wherePivot('role', $role)
            ->exists();
    }

    /**
     * Transition a production to a new state.
     *
     * @param  array<string, mixed>  $data
     */
    public function transition(Production $production, string $targetState, User $user, array $data = []): void
    {
        if (! $this->canTransition($production, $targetState, $user)) {
            throw new \InvalidArgumentException('Transición no autorizada o no válida.');
        }

        $previousState = $production->workflow_state;

        DB::transaction(function () use ($production, $targetState, $previousState, $user, $data) {
            // 1. Create version 1 if transitioning from draft for the first time
            if ($previousState === 'draft' && $targetState === 'under_tutor_review') {
                $this->createInitialVersion($production, $user);
            }

            // 2. Update production state
            $updateData = ['workflow_state' => $targetState];

            if ($targetState === 'under_tutor_review' && $previousState === 'draft') {
                $updateData['submission_date'] = now();
            }

            if ($previousState === 'rejected' && $targetState === 'draft') {
                $updateData['appeals_count'] = $production->appeals_count + 1;
            }

            if ($targetState === 'approved') {
                $updateData['approval_date'] = now();
                if ($production->subject?->code === 'TRI1106341') {
                    if (isset($data['preassigned_jury_1_id'])) {
                        $updateData['preassigned_jury_1_id'] = $data['preassigned_jury_1_id'];
                    }
                    if (isset($data['preassigned_jury_2_id'])) {
                        $updateData['preassigned_jury_2_id'] = $data['preassigned_jury_2_id'];
                    }
                }
            }

            if ($targetState === 'published') {
                $updateData['published_at'] = now();
            }

            // Reset request flag if tutor handles it
            if ($currentState = $previousState === 'under_tutor_review' && $targetState === 'under_jury_review') {
                $updateData['jury_review_requested'] = false;
            }

            $production->update($updateData);

            // 3. Handle file resubmission for needs_corrections -> under_tutor_review
            if ($previousState === 'needs_corrections' && $targetState === 'under_tutor_review') {
                $this->handleResubmission($production, $user, $data);
            }

            // 4. Record revision history
            Revision::create([
                'production_id' => $production->id,
                'user_id' => $user->id,
                'previous_state' => $previousState,
                'new_state' => $targetState,
                'comment' => $data['comment'] ?? null,
            ]);
        });

        event(new ProductionStateChanged($production, $previousState, $targetState, $user, $data['comment'] ?? null));
    }

    /**
     * Create version 1 of the document.
     */
    protected function createInitialVersion(Production $production, User $user): void
    {
        // Only create if version 1 doesn't exist yet
        $exists = DocumentVersion::where('production_id', $production->id)
            ->where('version_number', 1)
            ->exists();

        if ($exists) {
            return;
        }

        $media = $production->getFirstMedia('documento');
        if (! $media) {
            return;
        }

        $version = DocumentVersion::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'version_number' => 1,
            'changelog' => 'Carga inicial del documento.',
        ]);

        // Copy media from production to the version record
        $media->copy($version, 'documento_version');
    }

    /**
     * Handle the resubmission of a production with corrections.
     *
     * @param  array<string, mixed>  $data
     */
    protected function handleResubmission(Production $production, User $user, array $data): void
    {
        // For Google Drive Docs: we already synced/embedded it. We just need to version the current media.
        if ($production->google_drive_file_id) {
            $latestVersion = DocumentVersion::where('production_id', $production->id)
                ->max('version_number') ?? 1;

            $newVersionNumber = $latestVersion + 1;

            $version = DocumentVersion::create([
                'production_id' => $production->id,
                'user_id' => $user->id,
                'version_number' => $newVersionNumber,
                'changelog' => $data['changelog'] ?? 'Correcciones aplicadas en Google Docs.',
            ]);

            $media = $production->getFirstMedia('documento');
            if ($media) {
                $media->copy($version, 'documento_version');
            }

            return;
        }

        $fileId = $data['file_id'] ?? null;
        if (! $fileId) {
            throw new \InvalidArgumentException('Se requiere un archivo para reenviar la producción.');
        }

        $tempPathPdf = "temp_pdfs/{$fileId}.pdf";
        $tempPathDocx = "temp_pdfs/{$fileId}.docx";
        $relativePath = '';

        if (Storage::disk('local')->exists($tempPathPdf)) {
            $relativePath = $tempPathPdf;
        } elseif (Storage::disk('local')->exists($tempPathDocx)) {
            $relativePath = $tempPathDocx;
        } else {
            throw new \InvalidArgumentException('El archivo temporal no existe o ha expirado.');
        }

        $tempFullPath = Storage::disk('local')->path($relativePath);

        $this->metadataExtractorService->removeExtraUnimarCoverPage($tempFullPath);

        // Get the latest version number
        $latestVersion = DocumentVersion::where('production_id', $production->id)
            ->max('version_number') ?? 1;

        $newVersionNumber = $latestVersion + 1;

        // Create new DocumentVersion
        $version = DocumentVersion::create([
            'production_id' => $production->id,
            'user_id' => $user->id,
            'version_number' => $newVersionNumber,
            'changelog' => $data['changelog'] ?? 'Correcciones aplicadas.',
        ]);

        // Copy media to version first
        $version->addMedia($tempFullPath)
            ->preservingOriginal()
            ->toMediaCollection('documento_version');

        // Overwrite production's main document with the new one (clear first to avoid accumulation)
        $production->clearMediaCollection('documento');
        $production->addMedia($tempFullPath)
            ->toMediaCollection('documento');

        // Delete the temporary file
        Storage::disk('local')->delete($relativePath);
    }
}
