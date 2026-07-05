@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-8xl mx-auto pb-12">

        <!-- Breadcrumb / Volver -->
        <div>
            <a href="{{ route('admin.programs.index') }}" class="inline-flex items-center text-base font-bold text-slate-555 hover:text-unimar-blue transition uppercase tracking-wider">
                <svg aria-hidden="true" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver al Centro de Configuración
            </a>
        </div>

        <!-- Tarjeta del Formulario -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Registrar Programa Académico</h3>
                <p class="text-base text-slate-550 font-bold uppercase tracking-wider mt-0.5">Ingresa la información detallada para habilitar un nuevo programa académico o carrera en el Decanato de Ingeniería</p>
            </div>

            <form action="{{ route('admin.programs.store') }}" method="POST" class="p-8 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- Código del Programa -->
                    <div class="col-span-1">
                        <label for="code" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Código del Programa</label>
                        <input type="text"
                               id="code"
                               name="code"
                               value="{{ old('code') }}"
                               required
                               placeholder="P.ej. ING-SYS"
                               class="block w-full px-4 py-3 h-11 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 font-medium placeholder-slate-400 uppercase" />
                        @error('code')
                            <p class="text-base text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Nombre Completo -->
                    <div class="col-span-2">
                        <label for="name" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre Completo</label>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name') }}"
                               required
                               placeholder="P.ej. Ingeniería de Sistemas"
                               class="block w-full px-4 py-3 h-11 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 font-medium placeholder-slate-400" />
                        @error('name')
                            <p class="text-base text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Descripción -->
                <div>
                    <label for="description" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Descripción o Alcance (Opcional)</label>
                    <textarea id="description"
                              name="description"
                              rows="4"
                              placeholder="Describe brevemente el alcance académico del programa..."
                              class="block w-full px-4 py-3 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-150 text-slate-700 font-medium placeholder-slate-400">{{ old('description') }}</textarea>
                    @error('description')
                        <p class="text-base text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Estado Activo -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                    <label class="flex items-start cursor-pointer select-none">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox"
                               id="is_active"
                               name="is_active"
                               value="1"
                               {{ old('is_active', '1') ? 'checked' : '' }}
                               class="mt-0.5 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                        <div class="ml-3 text-base">
                            <span class="block font-bold text-slate-700">Habilitar programa académico</span>
                            <span class="block text-base text-slate-550 font-semibold mt-0.5">Si está inactivo, los estudiantes no podrán asociar sus trabajos de grado ni proyectos a este programa.</span>
                        </div>
                    </label>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.programs.index') }}"
                       class="py-3 px-5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-base uppercase tracking-wider transition focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                        Registrar Programa
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-dashboard-layout>
