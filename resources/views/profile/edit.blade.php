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
            <p class="text-base text-slate-500 mt-1.5 font-medium">Administra tus datos personales, contraseña y consulta tus credenciales académicas institucionales</p>
        </div>

        <!-- Estatus del Investigador -->
        <div class="inline-flex items-center space-x-2 bg-emerald-50 border border-emerald-200/60 px-4 py-2 rounded-xl text-emerald-800">
            <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 animate-pulse"></span>
            <span class="text-sm font-bold uppercase tracking-wider">Investigador Activo</span>
        </div>
    </div>

    <!-- Contenedor Unificado con Pestañas Alpine.js -->
    <div x-data="{ tab: 'personal' }" class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden transition-all duration-300">
        <!-- Barra de Navegación de Pestañas -->
        <div id="profile-tabs-navigator" class="flex flex-wrap border-b border-slate-200 bg-slate-50/50">
            <!-- Pestaña: Datos Personales -->
            <button @click="tab = 'personal'"
                    :class="tab === 'personal' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'"
                    class="px-6 py-4.5 text-base font-bold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
                <span>Datos Personales</span>
            </button>

            <!-- Pestaña: Seguridad y Contraseña -->
            <button @click="tab = 'security'"
                    :class="tab === 'security' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'"
                    class="px-6 py-4.5 text-base font-bold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                </svg>
                <span>Seguridad y Contraseña</span>
            </button>

            <!-- Pestaña: Información Académica -->
            <button @click="tab = 'academic'"
                    :class="tab === 'academic' ? 'border-b-2 border-unimar-blue text-unimar-blue font-bold bg-white' : 'text-slate-500 hover:text-slate-800 hover:bg-slate-100/50 border-b-2 border-transparent'"
                    class="px-6 py-4.5 text-base font-bold transition-all duration-150 flex items-center space-x-2 focus:outline-none">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l9-5-9-5-9 5 9 5z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 14l6.16-3.422a12.083 12.083 0 01.665 6.479A11.952 11.952 0 0012 20.055a11.952 11.952 0 00-6.824-2.998 12.078 12.078 0 01.665-6.479L12 14z"></path>
                </svg>
                <span>Información Académica</span>
            </button>
        </div>

        <!-- Cuerpo del Contenedor de Pestañas -->
        <div id="profile-tab-content" class="p-8 sm:p-10">
            <!-- Sección: Datos Personales (Editable) -->
            <div x-show="tab === 'personal'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-5xl mx-auto">
                @include('profile.partials.update-profile-information-form')
            </div>

            <!-- Sección: Seguridad y Contraseña (Editable) -->
            <div x-show="tab === 'security'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" class="max-w-5xl mx-auto space-y-10" style="display: none;">
                <div>
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <!-- Sección: Información Académica (Solo Lectura) -->
            <div x-show="tab === 'academic'" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="opacity-0 transform translate-y-1" x-transition:enter-end="opacity-100 transform translate-y-0" style="display: none;">
                <div class="max-w-5xl mx-auto">
                    <div class="mb-8">
                        <h3 class="text-2xl font-bold text-slate-800">Ficha Institucional</h3>
                        <p class="text-base text-slate-555 mt-1.5 font-medium">Información registrada y auditada por el Decanato de Ingeniería</p>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-start">
                        <!-- Columna Izquierda: Carnet Digital de Investigador -->
                        <div class="lg:col-span-5 flex flex-col items-center">
                            <!-- El Carnet -->
                            <div class="w-full max-w-[385px] bg-gradient-to-br from-[#0d4d98] via-[#1056a8] to-[#07366b] text-white rounded-3xl shadow-xl overflow-hidden relative border border-[#0d4d98]/30 hover:scale-[1.02] hover:shadow-2xl transition-all duration-300 aspect-[1.58/1] flex flex-col justify-between p-6 select-none">
                                <!-- Marca de Agua de Fondo (Simulación de Escudo) -->
                                <div class="absolute right-0 bottom-0 opacity-5 -mr-12 -mb-12 pointer-events-none">
                                    <svg class="w-64 h-64 text-white" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5" />
                                    </svg>
                                </div>

                                <!-- Encabezado del Carnet -->
                                <div class="flex items-center justify-between border-b border-white/10 pb-2">
                                    <div>
                                        <span class="block text-[9.5px] font-bold tracking-widest text-[#F5B800] uppercase leading-none">República Bolivariana de Venezuela</span>
                                        <span class="block text-sm font-extrabold tracking-wider uppercase leading-tight mt-0.5">Universidad de Margarita</span>
                                    </div>
                                    <span class="text-[11px] bg-[#F5B800] text-slate-900 font-extrabold px-1.5 py-0.5 rounded uppercase leading-none">UNIMAR</span>
                                </div>

                                <!-- Cuerpo del Carnet -->
                                <div class="flex items-center space-x-4 my-4">
                                    <!-- Foto/Monograma -->
                                    <div class="w-20 h-20 rounded-2xl bg-white/10 border border-white/20 flex items-center justify-center font-extrabold text-2xl text-[#F5B800] shadow-inner shrink-0">
                                        {{ strtoupper(substr($user->name, 0, 2)) }}
                                    </div>
                                    <!-- Datos de Identidad -->
                                    <div class="truncate">
                                        <span class="block text-[15px] font-extrabold tracking-tight truncate leading-tight">{{ $user->name }}</span>
                                        <span class="block text-[9px] font-bold text-white/70 uppercase tracking-widest mt-0.5">INVESTIGADOR</span>
                                        
                                        <!-- Rol Badge en Carnet -->
                                        <span class="inline-flex mt-1.5 px-2 py-0.5 text-[9px] font-extrabold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 rounded uppercase tracking-wider">
                                            {{ $activeRole }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Pie de Carnet -->
                                <div class="flex items-end justify-between pt-2 border-t border-white/10">
                                    <!-- Simulación de Código de Barras -->
                                    <div class="flex space-x-[1.5px] items-end h-8 bg-white/5 p-1 rounded">
                                        <div class="w-[1.5px] h-full bg-white"></div>
                                        <div class="w-[3px] h-full bg-white"></div>
                                        <div class="w-[1.5px] h-full bg-white"></div>
                                        <div class="w-[2px] h-[80%] bg-white"></div>
                                        <div class="w-[1.5px] h-full bg-white"></div>
                                        <div class="w-[3px] h-[90%] bg-white"></div>
                                        <div class="w-[1.5px] h-full bg-white"></div>
                                        <div class="w-[2px] h-full bg-white"></div>
                                        <div class="w-[1.5px] h-[70%] bg-white"></div>
                                        <div class="w-[3px] h-full bg-white"></div>
                                        <div class="w-[1.5px] h-[90%] bg-white"></div>
                                        <div class="w-[2px] h-full bg-white"></div>
                                    </div>

                                    <!-- Cédula de Identidad -->
                                    <div class="text-right">
                                        <span class="block text-[8px] text-white/50 font-bold uppercase tracking-widest leading-none">CÉDULA</span>
                                        <span class="text-sm font-extrabold tracking-wider leading-none mt-0.5 block">{{ $user->cedula ?? 'V-00000000' }}</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Leyenda del Carnet -->
                            <p class="text-sm text-slate-400 mt-3 font-semibold uppercase tracking-wider text-center">
                                Credencial Digital Temporal del Investigador
                            </p>
                        </div>

                        <!-- Columna Derecha: Detalles en Grid Moderno -->
                        <div class="lg:col-span-7 space-y-6">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- Roles Activos -->
                                <div class="bg-slate-50/50 border border-slate-200/60 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-500 uppercase tracking-wider mb-2.5">Roles Institucionales</span>
                                        <div class="flex flex-wrap gap-2">
                                            @forelse($userRoles as $role)
                                                <span class="bg-unimar-blue/5 text-unimar-blue border border-unimar-blue/15 px-3 py-1 rounded-lg text-sm font-bold uppercase tracking-wider">
                                                    {{ $role }}
                                                </span>
                                            @empty
                                                <span class="text-base text-slate-400 font-medium">Ningún rol asignado</span>
                                            @endforelse
                                        </div>
                                    </div>
                                    <span class="text-sm text-slate-400 mt-4 font-medium">Roles asignados por el administrador</span>
                                </div>

                                <!-- Fecha de Ingreso -->
                                <div class="bg-slate-50/50 border border-slate-200/60 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de Registro</span>
                                        <p class="text-base font-bold text-slate-700 mt-1.5">
                                            {{ $user->created_at->translatedFormat('d \d\e F \d\e Y') }}
                                        </p>
                                    </div>
                                    <span class="text-sm text-slate-400 mt-4 font-medium">Ingreso al sistema científico</span>
                                </div>

                                <!-- Documento de Identidad -->
                                <div class="bg-slate-50/50 border border-slate-200/60 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-500 uppercase tracking-wider mb-1.5">Documento de Identidad</span>
                                        <p class="text-base font-bold text-slate-700 mt-1.5">
                                            {{ $user->cedula ?? 'No registrada' }}
                                        </p>
                                    </div>
                                    <span class="text-sm text-slate-400 mt-4 font-medium">Cédula del Investigador</span>
                                </div>

                                <!-- Teléfono -->
                                <div class="bg-slate-50/50 border border-slate-200/60 rounded-2xl p-6 flex flex-col justify-between shadow-sm">
                                    <div>
                                        <span class="block text-sm font-bold text-slate-500 uppercase tracking-wider mb-1.5">Contacto Telefónico</span>
                                        <p class="text-base font-bold text-slate-700 mt-1.5">
                                            {{ $user->telefono ?? 'No registrado' }}
                                        </p>
                                    </div>
                                    <span class="text-sm text-slate-400 mt-4 font-medium">Número con formato internacional</span>
                                </div>
                            </div>

                            <!-- Nota de Privacidad Académica -->
                            <div class="p-6 bg-blue-50/40 border border-blue-100 rounded-2xl flex items-start space-x-3.5 shadow-sm">
                                <svg class="w-6 h-6 text-unimar-blue shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <h4 class="text-base font-bold text-unimar-blue">Seguridad de la Ficha Institucional</h4>
                                    <p class="text-sm text-slate-550 mt-1.5 leading-relaxed font-medium">
                                        La información de sus roles y documentos es auditada constantemente. Si detecta alguna discrepancia en sus datos académicos, por favor comuníquese directamente con la Coordinación de Investigación del Decanato.
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-dashboard-layout>
