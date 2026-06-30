@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <!-- Encabezado de la Página -->
    <div class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Mi Perfil Académico</h1>
            <p class="text-sm text-slate-500 mt-1 font-medium">Administra tus datos personales, contraseña y consulta tus credenciales académicas institucionales</p>
        </div>
        
        <!-- Estatus del Investigador -->
        <div class="inline-flex items-center space-x-2 bg-emerald-50 border border-emerald-200/60 px-4 py-2 rounded-xl text-emerald-800">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-xs font-bold uppercase tracking-wider">Investigador Activo</span>
        </div>
    </div>

    <!-- Contenedor Unificado con Pestañas Alpine.js -->
    <div x-data="{ tab: 'personal' }" class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden transition-all duration-300">
        <!-- Barra de Navegación de Pestañas -->
        <div class="flex flex-wrap border-b border-slate-200 bg-slate-50/50">
            <!-- Pestaña: Datos Personales -->
            <button @click="tab = 'personal'" 
                    :class="tab === 'personal' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'" 
                    class="px-6 py-4 text-sm font-semibold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Datos Personales</span>
            </button>

            <!-- Pestaña: Seguridad y Contraseña -->
            <button @click="tab = 'security'" 
                    :class="tab === 'security' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'" 
                    class="px-6 py-4 text-sm font-semibold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Seguridad y Contraseña</span>
            </button>

            <!-- Pestaña: Información Académica -->
            <button @click="tab = 'academic'" 
                    :class="tab === 'academic' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'" 
                    class="px-6 py-4 text-sm font-semibold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                </svg>
                <span>Información Académica</span>
            </button>
        </div>

        <!-- Cuerpo del Contenedor de Pestañas -->
        <div class="p-8 sm:p-10">
            <!-- Sección: Datos Personales (Editable) -->
            <div x-show="tab === 'personal'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-3xl">
                @include('profile.partials.update-profile-information-form')
            </div>

            <!-- Sección: Seguridad y Contraseña (Editable) -->
            <div x-show="tab === 'security'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-3xl space-y-10" style="display: none;">
                <div>
                    @include('profile.partials.update-password-form')
                </div>
                <hr class="border-slate-200" />
                <div class="bg-rose-50/50 border border-rose-100/80 rounded-2xl p-6 sm:p-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>

            <!-- Sección: Información Académica (Solo Lectura) -->
            <div x-show="tab === 'academic'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
                <div class="max-w-3xl">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-slate-800">Ficha Institucional</h3>
                        <p class="text-xs text-slate-500 mt-0.5">Información registrada y auditada por el Decanato de Ingeniería</p>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Roles Activos -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5">
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-2">Roles Institucionales</span>
                            <div class="flex flex-wrap gap-2">
                                @forelse($userRoles as $role)
                                    <span class="bg-unimar-blue/10 text-unimar-blue border border-unimar-blue/20 px-3 py-1 rounded-full text-xs font-bold uppercase tracking-wider">
                                        {{ $role }}
                                    </span>
                                @empty
                                    <span class="text-xs text-slate-400 font-medium">Ningún rol institucional asignado</span>
                                @endforelse
                            </div>
                        </div>

                        <!-- Fecha de Ingreso -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5">
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Fecha de Registro</span>
                            <p class="text-sm font-bold text-slate-700 mt-1">
                                {{ $user->created_at->translatedFormat('d \d\e F \d\e Y') }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Ingreso al sistema científico</p>
                        </div>

                        <!-- Cédula de Identidad -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5">
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Documento de Identidad</span>
                            <p class="text-sm font-bold text-slate-700 mt-1">
                                {{ $user->cedula ?? 'No registrada' }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Cédula del Investigador</p>
                        </div>

                        <!-- Teléfono -->
                        <div class="bg-slate-50 border border-slate-200/60 rounded-xl p-5">
                            <span class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1">Contacto Telefónico</span>
                            <p class="text-sm font-bold text-slate-700 mt-1">
                                {{ $user->telefono ?? 'No registrado' }}
                            </p>
                            <p class="text-[10px] text-slate-400 mt-0.5">Número con formato internacional</p>
                        </div>
                    </div>

                    <!-- Nota de Privacidad Académica -->
                    <div class="mt-8 p-4 bg-blue-50/50 border border-blue-100 rounded-xl flex items-start space-x-3">
                        <svg class="w-5 h-5 text-unimar-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <div>
                            <h4 class="text-xs font-bold text-unimar-blue">Seguridad de la Ficha Institucional</h4>
                            <p class="text-[10px] text-slate-500 mt-0.5 leading-relaxed">
                                La información de sus roles y documentos es auditada constantemente. Si detecta alguna discrepancia en sus datos académicos, por favor comuníquese directamente con la Coordinación de Investigación del Decanato.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
