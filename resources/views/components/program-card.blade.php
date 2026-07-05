@props([
    'icon',
    'title',
    'description',
    'count',
    'link'
])

<div class="bg-white p-6 lg:p-8 rounded-2xl border border-slate-100 shadow-[0_8px_30px_rgb(0,0,0,0.02)] hover:shadow-[0_15px_40px_rgba(13,77,152,0.06)] hover:-translate-y-1.5 transition-all duration-300 group flex flex-col justify-between h-full relative overflow-hidden">
    <!-- Top Decorative Line with Color Gradient -->
    <div class="absolute top-0 inset-x-0 h-1 bg-gradient-to-r from-unimar-blue to-unimar-gold opacity-0 group-hover:opacity-100 transition-opacity duration-300"></div>

    <div class="space-y-5">
        <!-- Icon Container -->
        <div class="w-14 h-14 rounded-2xl bg-unimar-blue/5 text-unimar-blue flex items-center justify-center group-hover:bg-unimar-blue group-hover:text-white group-hover:scale-110 shadow-sm transition-all duration-300">
            {!! $icon !!}
        </div>

        <div class="space-y-3.5">
            <h3 class="text-xl font-extrabold text-slate-800 tracking-tight group-hover:text-unimar-blue transition-colors duration-200">
                {{ $title }}
            </h3>
            <p class="text-base text-slate-500 leading-relaxed font-medium">
                {{ $description }}
            </p>
        </div>
    </div>

    <!-- Footer Actions -->
    <div class="flex items-center justify-between w-full mt-8 pt-5 border-t border-slate-100/80 gap-3">
        <span class="text-xs font-extrabold text-unimar-blue bg-unimar-blue/5 border border-unimar-blue/10 px-2.5 py-0.5 rounded-full uppercase tracking-wider group-hover:bg-unimar-blue group-hover:text-white group-hover:border-transparent transition-colors duration-300">
            {{ $count }}
        </span>
        <a href="{{ $link }}" class="text-base font-bold text-unimar-blue hover:text-unimar-gold flex items-center transition-colors">
            <span>Explorar</span>
            <svg class="w-4 h-4 ml-1 transition-transform duration-200 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
            </svg>
        </a>
    </div>
</div>
