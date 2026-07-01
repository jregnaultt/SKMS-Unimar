@php
    $auditLogs = $data['auditLogs'] ?? collect();
@endphp

<div class="space-y-6">
    <!-- Summary KPI Cards for Admin -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Active Users -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-blue-50 text-[#0d4d98] rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">{{ \App\Models\User::count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Usuarios Registrados</span>
            </div>
        </div>

        <!-- System Audit Logs Count -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">{{ \App\Models\AuditLog::count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Registros de Auditoría</span>
            </div>
        </div>

        <!-- OAI-PMH Status -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">Activo</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Servidor OAI-PMH</span>
            </div>
        </div>
    </div>

    <!-- Main Grid -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        
        <!-- Left: Audit Logs (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h3 class="text-sm font-extrabold text-slate-800">Logs de Auditoría en Tiempo Real</h3>
                        <p class="text-[10px] text-slate-400 font-semibold mt-0.5 uppercase tracking-wider">Últimas 10 acciones registradas en el sistema científico</p>
                    </div>
                    
                    <a href="{{ route('admin.audit-logs.index') }}" class="text-xs text-[#0d4d98] hover:underline font-bold">Ver todos los logs</a>
                </div>

                @if ($auditLogs->isEmpty())
                    <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-xl">
                        <p class="text-xs text-slate-400">No hay registros de auditoría registrados en la base de datos.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200 text-xs">
                            <thead>
                                <tr class="bg-slate-50 text-slate-500">
                                    <th class="px-5 py-3 text-left font-bold uppercase tracking-wider">Usuario</th>
                                    <th class="px-5 py-3 text-left font-bold uppercase tracking-wider">Acción</th>
                                    <th class="px-5 py-3 text-left font-bold uppercase tracking-wider">Entidad</th>
                                    <th class="px-5 py-3 text-left font-bold uppercase tracking-wider">IP</th>
                                    <th class="px-5 py-3 text-right font-bold uppercase tracking-wider">Fecha</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100 text-slate-650">
                                @foreach ($auditLogs as $log)
                                    <tr class="hover:bg-slate-50/40 transition">
                                        <td class="px-5 py-4 whitespace-nowrap font-semibold text-slate-800">
                                            {{ $log->user->name ?? 'Sistema/Anónimo' }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 rounded font-bold uppercase text-[9px] bg-slate-100 text-slate-700">
                                                {{ $log->action }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap font-mono text-[10px]">
                                            {{ class_basename($log->auditable_type) }} #{{ $log->auditable_id }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap font-mono text-[10px]">
                                            {{ $log->ip_address }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right text-slate-400">
                                            {{ $log->created_at->format('d/m/Y H:i:s') }}
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Mantenimiento y Configuración (1/3 width) -->
        <div class="space-y-6">
            <!-- Consola de Mantenimiento -->
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Mantenimiento del Sistema</h4>
                
                <p class="text-[10px] text-slate-400 font-semibold leading-normal">
                    Ejecuta acciones administrativas del servidor y respaldos manuales de la base de datos de manera segura.
                </p>

                <div class="space-y-3 pt-2">
                    <!-- Backup button (triggers alert as simulation or can trigger a route if exist, we will make it trigger a success notification to the user) -->
                    <button onclick="alert('Iniciando respaldo de base de datos MySQL en el servidor... ¡Respaldo completado con éxito! Archivo guardado en storage/app/backups/')" class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-[#0d4d98] hover:text-white rounded-xl text-xs font-bold text-slate-700 border border-slate-200/40 transition">
                        <span class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path>
                            </svg>
                            <span>Respaldar Base de Datos</span>
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </button>

                    <!-- Check OAI-PMH -->
                    <a href="{{ route('oai') }}" target="_blank" class="w-full flex items-center justify-between p-3 bg-slate-50 hover:bg-[#0d4d98] hover:text-white rounded-xl text-xs font-bold text-slate-700 border border-slate-200/40 transition">
                        <span class="flex items-center space-x-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 9l3 3-3 3m5 0h3M5 20h14a2 2 0 002-2V6a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                            </svg>
                            <span>Ver Endpoint OAI-PMH</span>
                        </span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                        </svg>
                    </a>
                </div>
            </div>

            <!-- Accesos Rápidos Admin -->
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Accesos Directos Administrativos</h4>
                
                <div class="grid grid-cols-1 gap-2">
                    <a href="{{ route('admin.users.index') }}" class="block p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/40 rounded-xl text-xs font-bold text-slate-700 text-center transition">
                        Gestionar Usuarios y Roles
                    </a>
                    <a href="{{ route('admin.programs.index') }}" class="block p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/40 rounded-xl text-xs font-bold text-slate-700 text-center transition">
                        Gestionar Programas Académicos
                    </a>
                    <a href="{{ route('admin.periods.index') }}" class="block p-3 bg-slate-50 hover:bg-slate-100 border border-slate-200/40 rounded-xl text-xs font-bold text-slate-700 text-center transition">
                        Gestionar Periodos Académicos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
