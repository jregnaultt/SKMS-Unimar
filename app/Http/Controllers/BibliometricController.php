<?php

namespace App\Http\Controllers;

use App\Services\BibliometricService;
use Illuminate\View\View;

class BibliometricController extends Controller
{
    public function __construct(private readonly BibliometricService $bibliometricService) {}

    /**
     * Display the bibliometric analysis dashboard.
     */
    public function index(): View
    {
        abort_unless(
            auth()->user()?->hasAnyRole(['Coordinador', 'Super Admin']) ?? false,
            403,
            'No tienes permiso para acceder al análisis bibliométrico.'
        );

        return view('pages.bibliometrics.index', [
            'metrics' => $this->bibliometricService->dashboard(),
        ]);
    }
}
