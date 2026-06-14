<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Reclamaciones de Tesis') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Notifications -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border-l-4 border-emerald-500 rounded-r-lg shadow-sm text-emerald-800 dark:text-emerald-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-6">
                    <div class="mb-6">
                        <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                            Solicitudes Pendientes de Liberación
                        </h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            Evalúa y aprueba (libera) o rechaza las reclamaciones realizadas por los investigadores para vincularse a tesis históricas.
                        </p>
                    </div>

                    @if ($claims->isEmpty())
                        <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                            <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No hay solicitudes pendientes</h3>
                            <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Todas las reclamaciones de tesis han sido procesadas.</p>
                        </div>
                    @else
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                <thead class="bg-gray-50 dark:bg-gray-700/50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Investigador</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Trabajo Reclamado</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Rol Solicitado</th>
                                        <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha Solicitud</th>
                                        <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                    @foreach ($claims as $claim)
                                        <tr x-data="{ showRejectForm: false }">
                                            <td class="px-6 py-4">
                                                <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $claim->user->name }}</div>
                                                <div class="text-xs text-gray-500 dark:text-gray-400">{{ $claim->user->email }}</div>
                                                <div class="text-xs text-gray-400 mt-0.5">Cédula: {{ $claim->user->cedula }}</div>
                                            </td>
                                            <td class="px-6 py-4 max-w-md">
                                                <div class="text-sm font-bold text-gray-900 dark:text-gray-100 line-clamp-1" title="{{ $claim->production->title }}">
                                                    {{ $claim->production->title }}
                                                </div>
                                                <div class="text-xs text-gray-500 mt-1">
                                                    <span>Autores PDF: <strong>{{ $claim->production->authors ?? 'N/A' }}</strong></span>
                                                    <span class="mx-1">•</span>
                                                    <span>Tutor PDF: <strong>{{ $claim->production->tutor ?? 'N/A' }}</strong></span>
                                                </div>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap">
                                                <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full {{ $claim->role === 'author' ? 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-300' : 'bg-purple-100 text-purple-800 dark:bg-purple-900/30 dark:text-purple-300' }}">
                                                    {{ $claim->role === 'author' ? 'Autor' : 'Tutor' }}
                                                </span>
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                                {{ $claim->created_at->format('d/m/Y H:i') }}
                                            </td>
                                            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                <div class="flex items-center justify-end space-x-2">
                                                    <!-- Approve Button -->
                                                    <form action="{{ route('admin.claims.approve', $claim) }}" method="POST" onsubmit="return confirm('¿Estás seguro de aprobar esta reclamación y vincular oficialmente al usuario?')">
                                                        @csrf
                                                        <button type="submit" class="px-3 py-1.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-xs font-semibold shadow-sm hover:shadow transition-all duration-200">
                                                            Liberar
                                                        </button>
                                                    </form>

                                                    <!-- Reject Toggle Button -->
                                                    <button @click="showRejectForm = !showRejectForm" type="button" class="px-3 py-1.5 bg-gray-100 hover:bg-gray-200 dark:bg-gray-700 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold transition-all duration-200">
                                                        Rechazar
                                                    </button>
                                                </div>

                                                <!-- Inline Reject Form (Toggled via Alpine) -->
                                                <div x-show="showRejectForm" x-transition class="mt-3 p-3 bg-gray-50 dark:bg-gray-700/30 rounded-lg text-left border border-gray-200 dark:border-gray-700/60 max-w-sm ml-auto">
                                                    <form action="{{ route('admin.claims.reject', $claim) }}" method="POST">
                                                        @csrf
                                                        <label class="block text-xs font-bold text-gray-700 dark:text-gray-300 mb-1">Motivo del Rechazo:</label>
                                                        <textarea name="rejection_reason" required rows="2" class="w-full text-xs rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Escribe el motivo del rechazo..."></textarea>
                                                        
                                                        <div class="mt-2 flex justify-end space-x-2">
                                                            <button type="button" @click="showRejectForm = false" class="px-2 py-1 bg-white border border-gray-300 dark:border-gray-600 dark:bg-gray-800 text-gray-700 dark:text-gray-300 rounded text-[10px] font-semibold">
                                                                Cancelar
                                                            </button>
                                                            <button type="submit" class="px-2 py-1 bg-rose-600 hover:bg-rose-700 text-white rounded text-[10px] font-semibold">
                                                                Confirmar Rechazo
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

                        <!-- Pagination -->
                        <div class="mt-4">
                            {{ $claims->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
