<?php

namespace App\Http\Controllers;

use App\Services\OaiPmhService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class OaiPmhController extends Controller
{
    public function __construct(private readonly OaiPmhService $oaiPmhService) {}

    /**
     * Handle an OAI-PMH 2.0 request and return XML.
     */
    public function index(Request $request): Response
    {
        $xml = $this->oaiPmhService->handle($request->query());

        return response($xml, 200, [
            'Content-Type' => 'text/xml; charset=UTF-8',
        ]);
    }
}
