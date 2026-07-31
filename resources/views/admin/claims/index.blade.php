@php
    $currentUser = auth()->user();
    $currentUserRoles = $currentUser->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($currentUserRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$currentUserRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-8xl mx-auto pb-12">

        <!-- Encabezado de la Página -->        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Reclamaciones de Tesis</h1>
                <p class="text-base text-slate-550 font-bold uppercase tracking-wider mt-1">Gestiona y evalúa las solicitudes de vinculación e incorporación de investigadores a trabajos de grado históricos</p>
            </div>
        </div>

        <!-- Alertas Flash del Sistema -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        @if (session('error'))
            <div class="p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-sm text-rose-800 transition duration-300">
                <div class="flex items-center">
                    <svg aria-hidden="true" class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('error') }}</span>
                </div>
            </div>
        @endif

        <!-- Tabla de Solicitudes -->
        <div id="admin-claims-table-card" class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Solicitudes Pendientes de Liberación</h3>
                <p class="text-base text-slate-550 mt-0.5 font-bold uppercase tracking-wider">Vincula formalmente a los autores en el catálogo de producción científica tras su debida verificación</p>
            </div>

            @if ($claims->isEmpty())
                <div class="py-16 text-center text-slate-550 border-t border-slate-100">
                    <svg aria-hidden="true" class="mx-auto h-12 w-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                    </svg>
                    <h4 class="text-base font-bold text-slate-700 uppercase tracking-wider">No hay solicitudes pendientes de liberación</h4>
                    <p class="text-base text-slate-550 mt-1 font-bold">Todas las reclamaciones de obras han sido procesadas correctamente.</p>
                </div>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse min-w-[700px]">
                        <thead>
                            <tr class="bg-slate-50 border-b border-slate-200 text-base font-bold uppercase tracking-wider text-slate-650">
                                <th class="p-4 pl-6">Investigador</th>
                                <th class="p-4">Trabajo Reclamado</th>
                                <th class="p-4">Rol Solicitado</th>
                                <th class="p-4">Fecha Solicitud</th>
                                <th class="p-4 pr-6 text-right">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 text-base text-slate-700">
                            @foreach ($claims as $claim)
                                <tr class="hover:bg-slate-50/50 transition duration-150" x-data="{ showRejectForm: false }">
                                    <!-- Investigador -->
                                    <td class="p-4 pl-6 whitespace-nowrap">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-9 h-9 rounded-full bg-unimar-blue/10 border border-unimar-blue/20 flex items-center justify-center text-unimar-blue font-bold text-base shrink-0">
                                                {{ strtoupper(substr($claim->user->name, 0, 2)) }}
                                            </div>
                                            <div>
                                                <span class="block font-bold text-slate-800 leading-tight">
                                                    {{ $claim->user->name }}
                                                </span>
                                                <span class="block text-base text-slate-550 font-bold mt-0.5">
                                                    {{ $claim->user->email }}
                                                </span>
                                                <span class="block text-base text-slate-600 font-bold font-mono mt-0.5">
                                                    C.I: {{ $claim->user->cedula ?? 'No registrada' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>

                                    <!-- Trabajo Reclamado -->
                                    <td class="p-4 max-w-md">
                                        <div class="font-bold text-slate-800 line-clamp-1" title="{{ $claim->production->title }}">
                                            {{ $claim->production->title }}
                                        </div>
                                        <div class="text-base text-slate-550 font-bold mt-0.5 leading-snug uppercase tracking-wider">
                                            <span>Autores PDF: <strong class="text-slate-700 font-black">{{ $claim->production->authors ?? 'N/A' }}</strong></span>
                                            <span class="mx-1.5">•</span>
                                            <span>Tutor PDF: <strong class="text-slate-700 font-black">{{ $claim->production->tutor ?? 'N/A' }}</strong></span>
                                        </div>
                                    </td>

                                    <!-- Rol Solicitado -->
                                    <td class="p-4 whitespace-nowrap">
                                        <span class="px-2.5 py-0.5 inline-flex text-base font-bold rounded-xl border {{ $claim->role === 'author' ? 'bg-blue-50 text-unimar-blue border-blue-200' : 'bg-purple-50 text-purple-850 border-purple-200' }}">
                                            {{ $claim->role === 'author' ? 'Autor / Creador' : 'Tutor Académico' }}
                                        </span>
                                    </td>

                                    <!-- Fecha Solicitud -->
                                    <td class="p-4 whitespace-nowrap text-base text-slate-550 font-bold font-mono">
                                        {{ $claim->created_at->format('d/m/Y H:i') }}
                                    </td>

                                    <!-- Acciones -->
                                    <td class="p-4 pr-6 whitespace-nowrap text-right text-base font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <!-- Botón de Aprobación / Liberación -->
                                            <form action="{{ route('admin.claims.approve', $claim) }}" method="POST" onsubmit="return confirm('¿Estás seguro de aprobar esta reclamación de tesis? El investigador será vinculado formalmente como colaborador.')" class="inline">
                                                @csrf
                                                <button type="submit" class="py-3 px-3.5 bg-emerald-600 hover:bg-emerald-700 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow h-11 cursor-pointer flex items-center justify-center">
                                                    Liberar
                                                </button>
                                            </form>

                                            <!-- Botón Toggle de Rechazo -->
                                            <button @click="showRejectForm = !showRejectForm"
                                                    type="button"
                                                    class="py-3 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-xl text-base uppercase tracking-wider transition h-11 cursor-pointer inline-flex items-center justify-center">
                                                Rechazar
                                            </button>
                                        </div>

                                        <!-- Formulario de Rechazo Inline (Alpine.js) -->
                                        <div x-show="showRejectForm"
                                             x-transition:enter="transition ease-out duration-200"
                                             x-transition:enter-start="opacity-0 transform translate-y-1"
                                             x-transition:enter-end="opacity-100 transform translate-y-0"
                                             class="mt-3 p-4 bg-slate-50 border border-slate-200 rounded-2xl text-left max-w-sm ml-auto space-y-3 shadow-md"
                                             style="display: none;">
                                            <form action="{{ route('admin.claims.reject', $claim) }}" method="POST">
                                                @csrf
                                                <div>
                                                    <label class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Motivo del Rechazo</label>
                                                    <textarea name="rejection_reason"
                                                              required
                                                              rows="2"
                                                              placeholder="Especifica el motivo de la denegación..."
                                                              class="block w-full px-3 py-2.5 bg-white border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/50 transition duration-155 text-slate-750 font-medium placeholder-slate-400"></textarea>
                                                </div>

                                                <div class="flex justify-end space-x-2">
                                                    <button type="button"
                                                            @click="showRejectForm = false"
                                                            class="py-2 px-3.5 bg-white border border-slate-200 hover:bg-slate-50 text-slate-700 font-bold rounded-lg text-base uppercase tracking-wider transition h-10 inline-flex items-center justify-center cursor-pointer">
                                                        Cancelar
                                                    </button>
                                                    <button type="submit"
                                                            class="py-2 px-3.5 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg text-base uppercase tracking-wider transition h-10 inline-flex items-center justify-center cursor-pointer">
                                                        Confirmar
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($claims->hasPages())
                    <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                        {{ $claims->links() }}
                    </div>
                @endif
            @endif
        </div>

    </div>
</x-dashboard-layout>
