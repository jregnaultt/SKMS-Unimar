<?php

namespace App\Http\Controllers;

use App\Enums\CommentStatus;
use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentStatusRequest;
use App\Models\Comment;
use App\Models\Production;
use App\Services\CommentService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function __construct(private readonly CommentService $commentService) {}

    /**
     * Store a new root observation on a production (Tutor/Jury only).
     */
    public function store(StoreCommentRequest $request, Production $production): RedirectResponse
    {
        try {
            $this->commentService->createObservation(
                $production,
                $request->user(),
                $request->validated()
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['comment' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Observación registrada correctamente.');
    }

    /**
     * Store a reply from the student to an existing observation.
     */
    public function reply(StoreCommentRequest $request, Comment $comment): RedirectResponse
    {
        try {
            $this->commentService->replyToObservation(
                $comment,
                $request->user(),
                $request->validated('content')
            );
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['reply' => $e->getMessage()])->withInput();
        }

        return back()->with('success', 'Respuesta registrada correctamente.');
    }

    /**
     * Update the status of an existing comment.
     */
    public function updateStatus(UpdateCommentStatusRequest $request, Comment $comment): RedirectResponse
    {
        $newStatus = CommentStatus::from($request->validated('status'));

        try {
            $this->commentService->changeStatus($comment, $request->user(), $newStatus);
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['status' => $e->getMessage()]);
        }

        return back()->with('success', 'Estado de la observación actualizado.');
    }

    /**
     * Verify/close an addressed observation (original tutor/jury only).
     */
    public function verify(Comment $comment): RedirectResponse
    {
        try {
            $this->commentService->verifyObservation($comment, auth()->user());
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        }

        return back()->with('success', 'Observación verificada y cerrada correctamente.');
    }

    /**
     * Delete a pending observation without replies (author only).
     */
    public function destroy(Comment $comment): RedirectResponse
    {
        try {
            $this->commentService->deleteComment($comment, auth()->user());
        } catch (AuthorizationException $e) {
            abort(403, $e->getMessage());
        } catch (\InvalidArgumentException $e) {
            return back()->withErrors(['comment' => $e->getMessage()]);
        }

        return back()->with('success', 'Observación eliminada.');
    }
}
