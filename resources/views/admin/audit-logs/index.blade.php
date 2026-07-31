@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-8 max-w-8xl mx-auto pb-12" x-data="{
        openDrawer: false,
        logId: null,
        loading: false,
        logData: null,
        showFilters: {{ request()->anyFilled(['search', 'user_id', 'action_type', 'date_from', 'date_to', 'academic_period_id', 'tutor']) ? 'true' : 'false' }},
        fetchLogDetails(id) {
            this.openDrawer = true;
            this.loading = true;
            this.logId = id;
            this.logData = null;

            fetch('/admin/audit-logs/' + id)
                .then(res => res.json())
                .then(data => {
                    this.logData = data;
                    this.loading = false;
                })
                .catch(err => {
                    console.error(err);
                    this.loading = false;
                });
        },
        getDiff(log) {
            if (!log) return [];
            let diffs = [];
            let oldVals = log.old_values || {};
            let newVals = log.new_values || {};

            // Get unique set of all keys
            let keys = Array.from(new Set([...Object.keys(oldVals), ...Object.keys(newVals)]));

            let formatVal = (val) => {
                if (val === null || val === undefined) return 'null';
                if (typeof val === 'boolean') return val ? 'true' : 'false';
                if (typeof val === 'object') return JSON.stringify(val);
                return String(val);
            };

            keys.forEach(key => {
                let oldVal = oldVals[key];
                let newVal = newVals[key];

                if (oldVals.hasOwnProperty(key) && newVals.hasOwnProperty(key)) {
                    if (oldVal !== newVal) {
                        diffs.push({ key: key, type: 'removed', val: formatVal(oldVal) });
                        diffs.push({ key: key, type: 'added', val: formatVal(newVal) });
                    } else {
                        // Unchanged fields - optional to show, let's include them for completeness but styled neutrally
                        diffs.push({ key: key, type: 'unchanged', val: formatVal(oldVal) });
                    }
                } else if (oldVals.hasOwnProperty(key)) {
                    diffs.push({ key: key, type: 'removed', val: formatVal(oldVal) });
                } else if (newVals.hasOwnProperty(key)) {
                    diffs.push({ key: key, type: 'added', val: formatVal(newVal) });
                }
            });
            return diffs;
        }
    }">

        <!-- Encabezado de la Página -->
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 border-b border-slate-200 pb-5">
            <div>
                <h1 class="text-3xl font-bold text-slate-800 tracking-tight font-sans">Bitácora de Auditoría</h1>
                <p class="text-base text-slate-500 mt-1 font-medium">Historial cronológico de acciones de seguridad, modificaciones de datos y transacciones del sistema</p>
            </div>

            <!-- Acciones y Búsqueda -->
            <div class="flex flex-col sm:flex-row items-center gap-3 w-full md:w-auto">
                <button type="button" @click="showFilters = !showFilters" :class="showFilters ? 'bg-slate-200 text-slate-800 border-slate-300' : 'bg-slate-100 text-slate-700 border-slate-200'" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 h-11 px-4 rounded-xl text-sm font-bold border transition uppercase tracking-wider whitespace-nowrap cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                    </svg>
                    <span>Filtros</span>
                </button>

                <a href="{{ route('admin.audit-logs.export', request()->query()) }}" class="w-full sm:w-auto inline-flex items-center justify-center gap-2 h-11 px-4 bg-emerald-600 hover:bg-emerald-700 text-white rounded-xl text-sm font-bold shadow transition uppercase tracking-wider whitespace-nowrap cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                    </svg>
                    Exportar Excel
                </a>
            </div>
        </div>

        <!-- Alertas Flash del Sistema -->
        @if (session('success'))
            <div class="p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-sm text-emerald-800 transition duration-300">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="font-semibold text-base">{{ session('success') }}</span>
                </div>
            </div>
        @endif

        <!-- Collapsible Filters Card -->
        <div x-show="showFilters" 
             x-transition:enter="transition ease-out duration-250"
             x-transition:enter-start="opacity-0 -translate-y-2"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 -translate-y-2"
             class="bg-white border border-slate-200 rounded-2xl p-5 shadow-sm space-y-4"
             style="display: none;">
            <form action="{{ route('admin.audit-logs.index') }}" method="GET" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 items-end">
                    
                    <!-- Search Input -->
                    <div class="space-y-1.5">
                        <label for="filter-search" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">Búsqueda Rápida</label>
                        <input type="text" 
                               id="filter-search"
                               name="search" 
                               value="{{ request('search') }}" 
                               placeholder="Usuario, IP, etc..." 
                               class="w-full h-10 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700">
                    </div>

                    <!-- Action Type Select -->
                    <div class="space-y-1.5">
                        <label for="filter-action" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">Acción</label>
                        <select id="filter-action" name="action_type" class="w-full h-10 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="">Todas las acciones</option>
                            @foreach($actions as $act)
                                @php
                                    $tempLog = new \App\Models\AuditLog();
                                    $tempLog->action = $act;
                                    $label = $tempLog->getActionLabel();
                                @endphp
                                <option value="{{ $act }}" {{ request('action_type') === $act ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User Select -->
                    <div class="space-y-1.5">
                        <label for="filter-user" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">Usuario / Actor</label>
                        <select id="filter-user" name="user_id" class="w-full h-10 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="">Todos los usuarios</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>
                                    {{ $u->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Academic Period Select -->
                    <div class="space-y-1.5">
                        <label for="filter-period" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">Período de Tesis</label>
                        <select id="filter-period" name="academic_period_id" class="w-full h-10 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="">Todos los períodos</option>
                            @foreach($periods as $p)
                                <option value="{{ $p->id }}" {{ request('academic_period_id') == $p->id ? 'selected' : '' }}>
                                    {{ $p->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Tutor Select -->
                    <div class="space-y-1.5">
                        <label for="filter-tutor" class="block text-xs font-bold text-slate-650 uppercase tracking-wider">Tutor de Tesis</label>
                        <select id="filter-tutor" name="tutor" class="w-full h-10 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700 cursor-pointer">
                            <option value="">Todos los tutores</option>
                            @foreach($tutors as $t)
                                <option value="{{ $t }}" {{ request('tutor') === $t ? 'selected' : '' }}>
                                    {{ $t }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Buttons -->
                    <div class="flex gap-2 h-10">
                        <button type="submit" class="flex-1 bg-[#0d4d98] hover:bg-[#09356b] text-white rounded-xl text-xs font-bold uppercase tracking-wider transition shadow-sm flex items-center justify-center cursor-pointer">
                            Filtrar
                        </button>
                        @if(request()->anyFilled(['search', 'user_id', 'action_type', 'date_from', 'date_to', 'academic_period_id', 'tutor']))
                            <a href="{{ route('admin.audit-logs.index') }}" class="px-3 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-xl text-xs font-bold transition flex items-center justify-center cursor-pointer" title="Limpiar Filtros">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Second Row: Date range filters -->
                <div class="flex flex-wrap items-center gap-4 pt-3 border-t border-slate-100/80">
                    <span class="text-xs font-bold text-slate-500 uppercase tracking-wider">Rango de Fechas:</span>
                    <div class="flex items-center space-x-2">
                        <input type="date" 
                               name="date_from" 
                               value="{{ request('date_from') }}" 
                               class="h-9 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700 cursor-pointer" 
                               placeholder="Desde">
                        <span class="text-xs font-bold text-slate-400">hasta</span>
                        <input type="date" 
                               name="date_to" 
                               value="{{ request('date_to') }}" 
                               class="h-9 text-xs rounded-xl border-slate-200 focus:ring-unimar-blue/20 focus:border-unimar-blue bg-slate-50/50 text-slate-700" 
                               placeholder="Hasta">
                    </div>
                </div>
            </form>
        </div>

        <!-- Grid para la tabla y el panel deslizable -->
        <div class="relative flex flex-col lg:flex-row gap-8 items-start">

            <!-- Tabla de Logs de Auditoría -->
            <div id="admin-audit-logs-table-card" class="w-full transition-all duration-350 bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden"
                 :class="openDrawer ? 'lg:w-2/3' : 'w-full'">

                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800 font-sans">Historial de Transacciones</h3>
                    <p class="text-base text-slate-500 mt-0.5 font-medium">Detalle forense de las operaciones realizadas por los usuarios en la plataforma</p>
                </div>

                @if ($logs->isEmpty())
                    <div class="py-16 text-center text-slate-400 border-t border-slate-100">
                        <svg class="mx-auto h-12 w-12 text-slate-300 mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                        <h4 class="text-base font-semibold text-slate-700">No se encontraron registros de auditoría</h4>
                        <p class="text-base text-slate-500 mt-1">Intenta modificar los términos de tu búsqueda rápida.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="w-full text-left border-collapse min-w-[800px]">
                            <thead>
                                <tr class="bg-slate-50 border-b border-slate-200 text-base font-bold uppercase tracking-wider text-slate-500">
                                    <th class="p-4 pl-6">Fecha / Hora</th>
                                    <th class="p-4">Usuario</th>
                                    <th class="p-4">Acción</th>
                                    <th class="p-4">Dirección IP</th>
                                    <th class="p-4 pr-6 text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 text-base text-slate-700">
                                @foreach ($logs as $log)
                                    @php
                                        $actionLower = strtolower($log->action);
                                        if (str_contains($actionLower, 'crear') || str_contains($actionLower, 'create') || str_contains($actionLower, 'store') || str_contains($actionLower, 'guardar')) {
                                            $actionClass = 'bg-emerald-50 text-emerald-800 border-emerald-200';
                                            $actionIcon = '<svg class="w-3 h-3 mr-1 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>';
                                        } elseif (str_contains($actionLower, 'edit') || str_contains($actionLower, 'actualizar') || str_contains($actionLower, 'update') || str_contains($actionLower, 'modificar')) {
                                            $actionClass = 'bg-blue-50 text-unimar-blue border-blue-200';
                                            $actionIcon = '<svg class="w-3 h-3 mr-1 text-unimar-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>';
                                        } elseif (str_contains($actionLower, 'eliminar') || str_contains($actionLower, 'delete') || str_contains($actionLower, 'destroy') || str_contains($actionLower, 'borrar')) {
                                            $actionClass = 'bg-rose-50 text-rose-800 border-rose-200';
                                            $actionIcon = '<svg class="w-3 h-3 mr-1 text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>';
                                        } else {
                                            $actionClass = 'bg-slate-50 text-slate-700 border-slate-200';
                                            $actionIcon = '<svg class="w-3 h-3 mr-1 text-slate-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
                                        }
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition duration-150"
                                        :class="logId == {{ $log->id }} ? 'bg-slate-50 border-l-4 border-unimar-blue' : ''">
                                        <td class="p-4 pl-6 whitespace-nowrap text-base text-slate-500 font-medium">
                                            <div class="flex items-center space-x-2">
                                                <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                                </svg>
                                                <span>{{ $log->created_at->format('d/m/Y H:i:s') }}</span>
                                            </div>
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <div class="font-bold text-slate-800">
                                                {{ $log->user->name ?? 'Sistema / Visitante' }}
                                            </div>
                                            @if($log->user)
                                                <div class="text-base text-slate-400 font-medium mt-0.5">
                                                    {{ $log->user->email }}
                                                </div>
                                            @endif
                                        </td>
                                        <td class="p-4 whitespace-nowrap">
                                            <span class="px-2.5 py-0.5 inline-flex items-center text-xs font-bold rounded-xl border {{ $actionClass }}">
                                                {!! $actionIcon !!}
                                                {{ $log->getActionLabel() }}
                                            </span>
                                        </td>
                                        <td class="p-4 whitespace-nowrap font-mono text-base text-slate-500 font-medium">
                                            {{ $log->ip_address ?? 'N/A' }}
                                        </td>
                                        <td class="p-4 pr-6 whitespace-nowrap text-right text-base font-medium">
                                            <button type="button"
                                                    @click="fetchLogDetails({{ $log->id }})"
                                                    class="inline-flex items-center px-3.5 py-2.5 bg-white border border-slate-200 hover:bg-slate-50 hover:border-slate-300 text-slate-700 rounded-xl text-base font-bold transition shadow-sm focus:outline-none h-11 cursor-pointer">
                                                Inspeccionar
                                            </button>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($logs->hasPages())
                        <div class="bg-slate-50 px-6 py-4 border-t border-slate-200">
                            {{ $logs->appends(request()->query())->links() }}
                        </div>
                    @endif
                @endif
            </div>

            <!-- Panel Lateral Deslizable (Side-Drawer) -->
            <div x-show="openDrawer"
                 x-transition:enter="transition ease-out duration-300"
                 x-transition:enter-start="translate-x-full opacity-0"
                 x-transition:enter-end="translate-x-0 opacity-100"
                 x-transition:leave="transition ease-in duration-200"
                 x-transition:leave-start="translate-x-0 opacity-100"
                 x-transition:leave-end="translate-x-full opacity-0"
                 class="w-full lg:w-1/3 bg-white border border-slate-200 shadow-xl rounded-2xl overflow-hidden sticky top-6 shrink-0"
                 style="display: none;">

                <!-- Cabecera del Drawer -->
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800 font-sans">Detalles de Auditoría</h3>
                        <p class="text-base text-slate-550 font-bold uppercase tracking-wider mt-0.5">ID del Registro: <span class="font-mono text-unimar-blue font-bold" x-text="logId"></span></p>
                    </div>
                    <button type="button" @click="openDrawer = false" class="text-slate-400 hover:text-slate-600 transition cursor-pointer">
                        <svg aria-hidden="true" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <!-- Contenido del Drawer -->
                <div class="p-6 space-y-6 overflow-y-auto max-h-[calc(100vh-220px)]">
                    <!-- Loading State -->
                    <div x-show="loading" class="flex flex-col items-center justify-center py-16 space-y-3">
                        <svg aria-hidden="true" class="animate-spin h-8 w-8 text-unimar-blue" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                        </svg>
                        <span class="text-base font-bold text-slate-600">Cargando detalles...</span>
                    </div>

                    <!-- Detail Content -->
                    <div x-show="!loading && logData" class="space-y-4">
                        <!-- Meta Grid -->
                        <div class="space-y-3 p-4 bg-slate-50 rounded-2xl border border-slate-200/60 text-base text-slate-700">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <span class="block text-base font-bold uppercase tracking-wider text-slate-550">Acción Realizada</span>
                                    <span class="block font-bold text-slate-800 mt-1" x-text="logData?.action_label || logData?.action"></span>
                                </div>
                                <div>
                                    <span class="block text-base font-bold uppercase tracking-wider text-slate-550">Dirección IP</span>
                                    <span class="block font-mono font-bold text-slate-800 mt-1" x-text="logData?.ip_address ?? 'N/A'"></span>
                                </div>
                            </div>
                            <div class="pt-3 border-t border-slate-200/60">
                                <span class="block text-base font-bold uppercase tracking-wider text-slate-550">Usuario Responsable</span>
                                <span class="block font-semibold text-slate-800 mt-1" x-text="logData?.user?.name ? `${logData.user.name} (${logData.user.email})` : 'Sistema / Desconocido'"></span>
                            </div>
                            <div class="pt-3 border-t border-slate-200/60">
                                <span class="block text-base font-bold uppercase tracking-wider text-slate-550">Entidad / Modelo Afectado</span>
                                <span class="block font-mono text-slate-800 mt-1" x-text="logData?.auditable_type ? `${logData.auditable_type} # ${logData.auditable_id}` : 'N/A'"></span>
                            </div>
                            <div class="pt-3 border-t border-slate-200/60">
                                <span class="block text-base font-bold uppercase tracking-wider text-slate-550">Fecha y Hora</span>
                                <span class="block font-semibold text-slate-800 mt-1" x-text="logData?.created_at ? new Date(logData.created_at).toLocaleString('es-VE') : 'N/A'"></span>
                            </div>
                        </div>

                        <!-- Visual Diff -->
                        <div class="space-y-3">
                            <label class="block text-base font-bold text-slate-600 uppercase tracking-wider">Comparativa de Cambios (Visual Diff)</label>

                            <div class="border border-slate-200 rounded-2xl overflow-hidden bg-slate-50/50">
                                <div class="bg-slate-100/80 px-4 py-2 border-b border-slate-200 text-base font-bold text-slate-550 uppercase tracking-wider font-sans flex justify-between">
                                    <span>Campo</span>
                                    <span>Cambio Realizado</span>
                                </div>
                                <div class="p-3 font-mono text-[11px] divide-y divide-slate-100/60 max-h-80 overflow-y-auto bg-white">
                                    <template x-for="item in getDiff(logData)" :key="item.key + '-' + item.type">
                                        <div class="py-2 px-2.5 rounded-xl flex flex-col transition-colors"
                                             :class="{
                                                 'bg-rose-50 text-rose-700 font-medium': item.type === 'removed',
                                                 'bg-emerald-50 text-emerald-700 font-medium': item.type === 'added',
                                                 'text-slate-555 bg-slate-50/30': item.type === 'unchanged'
                                             }">
                                            <div class="font-bold truncate text-[9px] uppercase tracking-wider text-slate-550 mb-1" x-text="item.key"></div>
                                            <div class="break-all whitespace-pre-wrap flex items-start leading-relaxed">
                                                <span class="mr-1.5 font-bold shrink-0 text-base" x-text="item.type === 'removed' ? '−' : (item.type === 'added' ? '+' : ' ')"></span>
                                                <span x-text="item.val"></span>
                                            </div>
                                        </div>
                                    </template>
                                    <template x-if="getDiff(logData).length === 0">
                                        <div class="text-center py-6 text-slate-550 italic text-[11px] font-semibold">
                                            No se registraron modificaciones de valores en este log.
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pie del Drawer -->
                <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex justify-end">
                    <button type="button"
                            @click="openDrawer = false"
                            class="py-3 px-5 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                        Cerrar Detalles
                    </button>
                </div>
            </div>

        </div>

    </div>
</x-dashboard-layout>
