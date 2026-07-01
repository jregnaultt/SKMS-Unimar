@props([
    'program',
    'title',
    'description',
    'link'
])

<div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_15px_40px_rgba(13,77,152,0.06)] hover:-translate-y-1.5 transition-all duration-300 flex flex-col justify-between h-full relative group overflow-hidden">
    <!-- Top accent border on hover -->
    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-unimar-blue to-unimar-gold opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>
    <!-- Inner hover background effect -->
    <div class="absolute inset-0 bg-gradient-to-br from-unimar-blue/[0.01] to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 pointer-events-none"></div>

    <div class="space-y-5 relative z-10">
        <span class="text-[10px] font-extrabold uppercase tracking-wider text-unimar-gold bg-unimar-gold/10 border border-unimar-gold/20 px-2.5 py-0.5 rounded-full inline-block">
            {{ $program }}
        </span>

        <h3 class="text-lg font-extrabold text-slate-800 leading-snug group-hover:text-unimar-blue transition-colors duration-250">
            {{ $title }}
        </h3>

        <p class="text-base text-slate-500 leading-relaxed font-medium line-clamp-4">
            {{ $description }}
        </p>
    </div>

    <div class="mt-8 pt-5 border-t border-slate-100/80 relative z-10">
        <a href="{{ $link }}" class="text-base font-bold text-unimar-blue hover:text-unimar-gold flex items-center transition-colors">
            <span>Ver Proyectos</span>
            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</div>
