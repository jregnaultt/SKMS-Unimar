@props([
    'title',
    'author',
    'tutor',
    'program',
    'period',
    'status' => 'Publicado',
    'link',
    'showPdf' => false,
    'pdfLink' => '#'
])

<div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_15px_40px_rgba(13,77,152,0.06)] hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between h-full group relative overflow-hidden">
    <!-- Top accent border on hover -->
    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-unimar-blue to-unimar-gold opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

    <div>
        <!-- Card Header status & period -->
        <div class="flex items-center justify-between w-full mb-5">
            <span class="px-2.5 py-0.5 text-[9px] font-extrabold uppercase tracking-wider bg-emerald-50 text-emerald-700 rounded-full border border-emerald-100">
                {{ $status }}
            </span>
            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-wider">
                {{ $period }}
            </span>
        </div>

        <!-- Title block with document icon -->
        <div class="flex items-start space-x-3 mb-5">
            <div class="w-9 h-9 rounded-xl bg-unimar-blue/5 text-unimar-blue flex items-center justify-center shrink-0 group-hover:bg-unimar-blue group-hover:text-white transition-colors duration-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <h3 class="text-lg font-extrabold text-slate-800 line-clamp-2 group-hover:text-unimar-blue transition-colors duration-250 leading-snug">
                <a href="{{ $link }}">{{ $title }}</a>
            </h3>
        </div>

        <!-- Metadata fields styled neatly -->
        <div class="text-base text-slate-500 space-y-2.5 border-t border-slate-100/80 pt-4 mb-4">
            <div class="flex items-center">
                <span class="text-slate-400 font-semibold w-28 shrink-0">Autor:</span>
                <span class="text-slate-700 font-medium truncate">{{ $author }}</span>
            </div>
            <div class="flex items-center">
                <span class="text-slate-400 font-semibold w-28 shrink-0">Tutor:</span>
                <span class="text-slate-700 font-medium truncate">{{ $tutor }}</span>
            </div>
            <div class="flex items-center">
                <span class="text-slate-400 font-semibold w-28 shrink-0">Programa:</span>
                <span class="text-slate-700 font-medium truncate">{{ $program }}</span>
            </div>
        </div>
    </div>

    <!-- Actions block -->
    <div class="flex items-center justify-between w-full mt-4 pt-4 border-t border-slate-100/85">
        <a href="{{ $link }}" class="text-base font-bold text-unimar-blue hover:text-unimar-gold flex items-center transition-colors">
            <span>Ver Detalles</span>
            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>

        @if($showPdf && $pdfLink && $pdfLink !== '#')
            <a href="{{ $pdfLink }}" target="_blank" class="inline-flex items-center px-3 py-1.5 bg-slate-50 hover:bg-unimar-blue hover:text-white rounded-xl text-[10px] font-bold text-slate-700 border border-slate-200/60 shadow-sm transition">
                <svg class="w-3.5 h-3.5 mr-1 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                </svg>
                PDF
            </a>
        @endif
    </div>
</div>
