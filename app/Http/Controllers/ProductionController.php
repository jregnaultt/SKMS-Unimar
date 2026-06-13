<?php

namespace App\Http\Controllers;

use App\Jobs\ExtractMetadataJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductionController extends Controller
{
    public function create()
    {
        return view('pages.productions.create');
    }

    public function extractMetadata(Request $request)
    {
        $fieldName = $request->hasFile('documento') ? 'documento' : ($request->hasFile('pdf') ? 'pdf' : 'documento');

        $request->validate([
            $fieldName => 'required|file|extensions:pdf,docx|max:10240', // 10MB max
        ]);

        $file = $request->file($fieldName);
        $fileId = Str::uuid()->toString();
        $extension = $file->getClientOriginalExtension();
        $path = $file->storeAs('temp_pdfs', $fileId.'.'.$extension, 'local');

        $fullPath = Storage::disk('local')->path($path);

        ExtractMetadataJob::dispatch($request->user()->id, $fullPath, $fileId);

        return response()->json([
            'status' => 'processing',
            'file_id' => $fileId,
        ]);
    }
}
