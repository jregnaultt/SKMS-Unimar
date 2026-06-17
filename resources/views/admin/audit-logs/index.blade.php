<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Logs de Auditoría') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="{ 
        open: false, 
        logId: null, 
        loading: false, 
        logData: null,
        fetchLogDetails(id) {
            this.open = true;
            this.loading = true;
            this.logId = id;
            this.logData = null;
            
            fetch(`/admin/audit-logs/${id}`)
                .then(res => res.json())
                .then(data => {
                    this.logData = data;
                    this.loading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                });
        }
    }">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar -->
                @include('admin.shared.sidebar')

                <!-- Main Content -->
                <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6">
                        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6 gap-4">
                            <div>
                                <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                    Bitácora de Auditoría de Seguridad
                                </h3>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                    Supervisa las acciones del sistema, cambios de estado y modificaciones de datos.
                                </p>
                            </div>

                            <!-- Search form -->
                            <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="w-full sm:w-72">
                                <div class="relative">
                                    <x-text-input type="text" name="search" placeholder="Buscar por usuario, acción o IP..." class="w-full text-xs" :value="request('search')" />
                                    <button type="submit" class="absolute right-2.5 top-2.5 text-gray-400 hover:text-indigo-600 dark:hover:text-indigo-400">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                        </svg>
                                    </button>
                                </div>
                            </form>
                        </div>

                        @if ($logs->isEmpty())
                            <div class="text-center py-12 border-2 border-dashed border-gray-200 dark:border-gray-700 rounded-xl">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <h3 class="mt-2 text-sm font-semibold text-gray-900 dark:text-gray-100">No se encontraron registros de auditoría</h3>
                                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">Intenta buscar con otros términos.</p>
                            </div>
                        @else
                            <div class="overflow-x-auto rounded-lg border border-gray-100 dark:border-gray-700">
                                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                                    <thead class="bg-gray-50 dark:bg-gray-700/50">
                                        <tr>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Fecha / Hora</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Usuario</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acción</th>
                                            <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">IP Address</th>
                                            <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 dark:text-gray-400 uppercase tracking-wider">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                                        @foreach ($logs as $log)
                                            <tr class="hover:bg-gray-50/50 dark:hover:bg-gray-700/30 transition-colors duration-150">
                                                <td class="px-6 py-4 whitespace-nowrap text-xs text-gray-600 dark:text-gray-300">
                                                    {{ $log->created_at->format('d/m/Y H:i:s') }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <div class="text-sm font-semibold text-gray-900 dark:text-gray-100">
                                                        {{ $log->user->name ?? 'Sistema / Visitante' }}
                                                    </div>
                                                    @if($log->user)
                                                        <div class="text-xs text-gray-500 dark:text-gray-400">
                                                            {{ $log->user->email }}
                                                        </div>
                                                    @endif
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap">
                                                    <span class="px-2.5 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300">
                                                        {{ $log->action }}
                                                    </span>
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-gray-500 dark:text-gray-400">
                                                    {{ $log->ip_address ?? 'N/A' }}
                                                </td>
                                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                                    <button @click="fetchLogDetails({{ $log->id }})" class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300 font-semibold">
                                                        Inspeccionar
                                                    </button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-4">
                                {{ $logs->appends(request()->query())->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Alpine Slide-over Modal -->
        <div x-show="open" 
             class="fixed inset-0 overflow-hidden z-50" 
             aria-labelledby="slide-over-title" 
             role="dialog" 
             aria-modal="true"
             style="display: none;">
            <div class="absolute inset-0 overflow-hidden">
                <!-- Backdrop -->
                <div x-show="open" 
                     x-transition:enter="ease-in-out duration-300"
                     x-transition:enter-start="opacity-0"
                     x-transition:enter-end="opacity-100"
                     x-transition:leave="ease-in-out duration-300"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     @click="open = false"
                     class="absolute inset-0 bg-gray-500 bg-opacity-75 dark:bg-opacity-80 backdrop-blur-sm transition-opacity" 
                     aria-hidden="true"></div>

                <div class="fixed inset-y-0 right-0 pl-10 max-w-full flex">
                    <!-- Panel Content -->
                    <div x-show="open" 
                         x-transition:enter="transform transition ease-in-out duration-300 sm:duration-300"
                         x-transition:enter-start="translate-x-full"
                         x-transition:enter-end="translate-x-0"
                         x-transition:leave="transform transition ease-in-out duration-300 sm:duration-300"
                         x-transition:leave-start="translate-x-0"
                         x-transition:leave-end="translate-x-full"
                         class="w-screen max-w-2xl bg-white dark:bg-gray-800 shadow-2xl flex flex-col border-l border-gray-200 dark:border-gray-700">
                        
                        <!-- Header -->
                        <div class="px-6 py-5 bg-gray-50 dark:bg-gray-900/50 border-b border-gray-100 dark:border-gray-700/80 flex items-center justify-between">
                            <div>
                                <h2 class="text-lg font-bold text-gray-900 dark:text-gray-100" id="slide-over-title">
                                    Detalle del Log de Auditoría
                                </h2>
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                                    ID de registro: <span class="font-mono text-indigo-600 dark:text-indigo-400" x-text="logId"></span>
                                </p>
                            </div>
                            <button @click="open = false" class="rounded-md text-gray-400 hover:text-gray-500 focus:outline-none focus:ring-2 focus:ring-indigo-500">
                                <span class="sr-only">Cerrar panel</span>
                                <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </button>
                        </div>

                        <!-- Content Body -->
                        <div class="flex-1 overflow-y-auto p-6 space-y-6">
                            <!-- Loading State -->
                            <div x-show="loading" class="flex flex-col items-center justify-center py-24 space-y-3">
                                <svg class="animate-spin h-8 w-8 text-indigo-600 dark:text-indigo-400" fill="none" viewBox="0 0 24 24">
                                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                </svg>
                                <span class="text-sm font-semibold text-gray-500 dark:text-gray-400">Cargando detalles de auditoría...</span>
                            </div>

                            <!-- Detail Content -->
                            <div x-show="!loading && logData" class="space-y-6">
                                <!-- Meta Grid -->
                                <div class="grid grid-cols-2 gap-4 bg-gray-50 dark:bg-gray-900/30 p-4 rounded-xl border border-gray-150 dark:border-gray-700/60 text-xs">
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Acción Realizada</span>
                                        <span class="block font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="logData?.action"></span>
                                    </div>
                                    <div>
                                        <span class="block text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Dirección IP</span>
                                        <span class="block font-mono font-bold text-gray-900 dark:text-gray-100 mt-1" x-text="logData?.ip_address ?? 'N/A'"></span>
                                    </div>
                                    <div class="col-span-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <span class="block text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Usuario Responsable</span>
                                        <span class="block font-semibold text-gray-900 dark:text-gray-100 mt-1" x-text="logData?.user?.name ? `${logData.user.name} (${logData.user.email})` : 'Sistema / Desconocido'"></span>
                                    </div>
                                    <div class="col-span-2 pt-2 border-t border-gray-200 dark:border-gray-700">
                                        <span class="block text-gray-500 dark:text-gray-400 font-bold uppercase tracking-wider">Modelo Afectado</span>
                                        <span class="block font-mono text-gray-900 dark:text-gray-100 mt-1" x-text="`${logData?.auditable_type} # ${logData?.auditable_id}`"></span>
                                    </div>
                                </div>

                                <!-- Value comparison -->
                                <div class="space-y-4">
                                    <h4 class="text-sm font-bold text-gray-900 dark:text-gray-100">Comparativa de Valores</h4>
                                    
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Old Values -->
                                        <div class="space-y-1.5">
                                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 block">Valores Anteriores:</span>
                                            <div class="p-3 bg-rose-50 dark:bg-rose-950/10 border border-rose-100 dark:border-rose-900/30 rounded-xl overflow-x-auto text-[11px] font-mono text-rose-800 dark:text-rose-400 min-h-32">
                                                <pre x-text="logData?.old_values ? JSON.stringify(logData.old_values, null, 2) : 'No registra valores anteriores'"></pre>
                                            </div>
                                        </div>

                                        <!-- New Values -->
                                        <div class="space-y-1.5">
                                            <span class="text-xs font-bold text-gray-500 dark:text-gray-400 block">Valores Nuevos:</span>
                                            <div class="p-3 bg-emerald-50 dark:bg-emerald-950/10 border border-emerald-100 dark:border-emerald-900/30 rounded-xl overflow-x-auto text-[11px] font-mono text-emerald-800 dark:text-emerald-400 min-h-32">
                                                <pre x-text="logData?.new_values ? JSON.stringify(logData.new_values, null, 2) : 'No registra nuevos valores'"></pre>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="px-6 py-4 bg-gray-50 dark:bg-gray-900/50 border-t border-gray-100 dark:border-gray-700/80 flex justify-end">
                            <button @click="open = false" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-xs font-semibold uppercase tracking-widest transition-all duration-200">
                                Cerrar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
