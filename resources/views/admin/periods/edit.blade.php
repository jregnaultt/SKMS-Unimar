@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-4xl mx-auto pb-12">
        
        <!-- Breadcrumb / Volver -->
        <div>
            <a href="{{ route('admin.periods.index') }}" class="inline-flex items-center text-xs font-bold text-unimar-blue hover:text-unimar-blue/85 transition uppercase tracking-wider">
                <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver al Centro de Configuración
            </a>
        </div>

        <!-- Tarjeta del Formulario -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Modificar Período Académico</h3>
                <p class="text-xs text-slate-500 mt-0.5 font-medium">Actualiza los detalles de la vigencia cronológica de la cohorte seleccionada</p>
            </div>

            <form action="{{ route('admin.periods.update', $period) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <!-- Nombre del Período -->
                    <div class="col-span-1">
                        <label for="name" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Nombre del Período</label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name', $period->name) }}" 
                               required 
                               placeholder="P.ej. 2026-I, U-2026" 
                               class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium placeholder-slate-400" />
                        @error('name')
                            <p class="text-xs text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha de Inicio -->
                    <div class="col-span-1">
                        <label for="start_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de Inicio</label>
                        <input type="date" 
                               id="start_date" 
                               name="start_date" 
                               value="{{ old('start_date', $period->start_date ? $period->start_date->format('Y-m-d') : '') }}" 
                               required 
                               class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium" />
                        @error('start_date')
                            <p class="text-xs text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Fecha de Finalización -->
                    <div class="col-span-1">
                        <label for="end_date" class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de Finalización</label>
                        <input type="date" 
                               id="end_date" 
                               name="end_date" 
                               value="{{ old('end_date', $period->end_date ? $period->end_date->format('Y-m-d') : '') }}" 
                               required 
                               class="block w-full px-4 py-2.5 bg-white border border-slate-200 rounded-xl text-sm focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium" />
                        @error('end_date')
                            <p class="text-xs text-rose-600 font-bold mt-1.5">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                <!-- Estado Activo -->
                <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                    <label class="flex items-start cursor-pointer select-none">
                        <input type="hidden" name="is_active" value="0">
                        <input type="checkbox" 
                               id="is_active" 
                               name="is_active" 
                               value="1" 
                               {{ old('is_active', $period->is_active) ? 'checked' : '' }} 
                               class="mt-0.5 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4" />
                        <div class="ml-3 text-xs">
                            <span class="block font-bold text-slate-700">Establecer período académico como activo</span>
                            <span class="block text-slate-400 font-medium mt-0.5">Si está inactivo, no se podrán realizar cargas ni entregas de trabajos bajo esta cohorte temporal.</span>
                        </div>
                    </label>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.periods.index') }}" 
                       class="py-2.5 px-5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-xs uppercase tracking-wider transition focus:outline-none">
                        Cancelar
                    </a>
                    <button type="submit" 
                            class="py-2.5 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-xs uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none">
                        Actualizar Período
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-dashboard-layout>
