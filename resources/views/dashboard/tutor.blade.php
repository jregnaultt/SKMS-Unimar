@php
    $productions = $data['productions'] ?? collect();
    $defensas = $data['defensas'] ?? collect();
    $roleLabel = $data['roleLabel'] ?? 'Evaluador';
@endphp

<div class="space-y-8">
    <!-- Header Summary Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-[#0d4d98]/10 text-[#0d4d98] rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">{{ $productions->count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Trabajos Asignados</span>
            </div>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-amber-50 text-amber-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">
                    {{ $productions->where('workflow_state', 'under_review')->count() }}
                </span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Pendientes de Revisión</span>
            </div>
        </div>

        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4">
            <div class="p-3 bg-emerald-50 text-emerald-600 rounded-xl">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-2xl font-extrabold text-slate-800">{{ $defensas->count() }}</span>
                <span class="text-[10px] text-slate-400 font-bold uppercase tracking-wider">Defensas Programadas</span>
            </div>
        </div>
    </div>

    <!-- Main Section Split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Left: Bandeja de Evaluaciones Activas (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white border border-slate-100 rounded-2xl p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                <div class="mb-6">
                    <h3 class="text-sm font-extrabold text-slate-800">Bandeja de Evaluaciones Activas</h3>
                    <p class="text-[10px] text-slate-400 font-semibold mt-0.5 uppercase tracking-wider">Lista de tesis y proyectos científicos bajo tu supervisión</p>
                </div>

                @if ($productions->isEmpty())
                    <div class="text-center py-16 border-2 border-dashed border-slate-100 rounded-xl">
                        <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                        </svg>
                        <h4 class="mt-2 text-xs font-bold text-slate-700">No tienes asignaciones de evaluación</h4>
                        <p class="mt-1 text-[11px] text-slate-400">Actualmente no estás registrado como tutor o jurado en ningún trabajo científico activo.</p>
                    </div>
                @else
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-slate-200">
                            <thead>
                                <tr class="bg-slate-50">
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                    <th class="px-5 py-3 text-left text-[10px] font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-5 py-3 text-right text-[10px] font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
                                </tr>
                            </thead>
                            <tbody class="bg-white divide-y divide-slate-100">
                                @foreach ($productions as $prod)
                                    @php
                                        // Find student author
                                        $studentName = $prod->authors;
                                        foreach ($prod->users as $u) {
                                            if ($u->pivot->role === 'author') {
                                                $studentName = $u->name;
                                                break;
                                            }
                                        }
                                        
                                        $stateColors = [
                                            'draft' => 'bg-slate-100 text-slate-700',
                                            'under_review' => 'bg-amber-50 text-amber-800 border-amber-200/60',
                                            'needs_corrections' => 'bg-orange-50 text-orange-800 border-orange-200/60',
                                            'approved' => 'bg-emerald-50 text-emerald-800 border-emerald-200/60',
                                            'published' => 'bg-blue-50 text-blue-800 border-blue-200/60',
                                            'rejected' => 'bg-rose-50 text-rose-800 border-rose-200/60',
                                        ];
                                        $stateLabels = [
                                            'draft' => 'Borrador',
                                            'under_review' => 'En Revisión',
                                            'needs_corrections' => 'Requiere Correcciones',
                                            'approved' => 'Aprobado',
                                            'published' => 'Publicado',
                                            'rejected' => 'Rechazado',
                                        ];
                                    @endphp
                                    <tr class="hover:bg-slate-50/50 transition">
                                        <td class="px-5 py-4">
                                            <div class="text-xs font-bold text-slate-800 max-w-xs truncate" title="{{ $prod->title }}">
                                                {{ $prod->title }}
                                            </div>
                                            <div class="text-[10px] text-slate-400 mt-1">
                                                {{ $prod->productionType->name ?? 'Tesis' }} • {{ $prod->academicPeriod->name ?? '' }}
                                            </div>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-xs text-slate-600 font-medium">
                                            {{ $studentName }}
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap">
                                            <span class="px-2 py-0.5 inline-flex text-[10px] leading-5 font-bold rounded-full border {{ $stateColors[$prod->workflow_state] ?? 'bg-slate-100 text-slate-700' }}">
                                                {{ $stateLabels[$prod->workflow_state] ?? $prod->workflow_state }}
                                            </span>
                                        </td>
                                        <td class="px-5 py-4 whitespace-nowrap text-right text-xs font-medium">
                                            <a href="{{ route('productions.show', $prod) }}" class="inline-flex items-center space-x-1.5 px-3 py-1.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg font-bold transition shadow-sm hover:shadow">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                </svg>
                                                <span>Evaluar</span>
                                            </a>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        <!-- Right: Agenda de Defensas y Calendario (1/3 width) -->
        <div class="space-y-6"
             x-data="{
                 currentDate: new Date(),
                 days: [],
                 defensas: @js($defensas->map(fn($d) => [
                     'title' => $d->title,
                     'date' => $d->scheduled_date->format('Y-m-d'),
                     'time' => $d->scheduled_date->format('h:i A'),
                     'student' => $d->production->authors ?? 'Estudiante',
                     'production_title' => $d->production->title
                 ])),
                 selectedDefenses: [],
                 init() {
                     this.generateCalendar();
                 },
                 generateCalendar() {
                     let year = this.currentDate.getFullYear();
                     let month = this.currentDate.getMonth();
                     
                     let firstDayIndex = new Date(year, month, 1).getDay();
                     let lastDay = new Date(year, month + 1, 0).getDate();
                     
                     let tempDays = [];
                     
                     // Padding from previous month
                     for (let i = 0; i < firstDayIndex; i++) {
                         tempDays.push({ day: '', fullDate: '', hasDefense: false, defensesList: [] });
                     }
                     
                     // Days of current month
                     for (let d = 1; d <= lastDay; d++) {
                         let fullDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                         
                         // Check if there is a defense on this day
                         let dayDefenses = this.defensas.filter(def => def.date === fullDateStr);
                         
                         tempDays.push({
                             day: d,
                             fullDate: fullDateStr,
                             hasDefense: dayDefenses.length > 0,
                             defensesList: dayDefenses
                         });
                     }
                     
                     this.days = tempDays;
                 },
                 prevMonth() {
                     this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                     this.generateCalendar();
                 },
                 nextMonth() {
                     this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                     this.generateCalendar();
                 },
                 getMonthName() {
                     return this.currentDate.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
                 },
                 selectDay(d) {
                     if (d.hasDefense) {
                         this.selectedDefenses = d.defensesList;
                     } else {
                         this.selectedDefenses = [];
                     }
                 }
             }">
            
            <!-- Calendario Component -->
            <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h4 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Defensas de Grado</h4>
                    
                    <!-- Navigation -->
                    <div class="flex items-center space-x-1">
                        <button @click="prevMonth()" class="p-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button @click="nextMonth()" class="p-1 bg-slate-50 hover:bg-slate-100 border border-slate-200 rounded-lg text-slate-600 transition">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Month header -->
                <div class="text-center">
                    <span class="text-xs font-bold text-slate-700 capitalize" x-text="getMonthName()"></span>
                </div>

                <!-- Calendar grid -->
                <div class="grid grid-cols-7 gap-1 text-center text-[10px]">
                    <!-- Weekdays -->
                    <template x-for="day in ['D', 'L', 'M', 'M', 'J', 'V', 'S']">
                        <div class="font-bold text-slate-400 py-1" x-text="day"></div>
                    </template>

                    <!-- Month Days -->
                    <template x-for="d in days">
                        <div class="relative py-2 flex items-center justify-center">
                            <button 
                                @click="selectDay(d)"
                                :disabled="!d.day"
                                :class="{
                                    'w-7 h-7 rounded-full text-xs font-bold flex items-center justify-center transition-all': true,
                                    'hover:bg-slate-100 text-slate-700': d.day && !d.hasDefense,
                                    'bg-amber-100 text-[#0d4d98] border border-[#F5B800] ring-2 ring-amber-100/50 hover:bg-amber-250': d.hasDefense,
                                    'text-slate-300 pointer-events-none': !d.day
                                }"
                                x-text="d.day"
                            ></button>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Defense Details Card (displayed when highlighted day clicked) -->
            <div x-show="selectedDefenses.length > 0" class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4" x-transition style="display: none;">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <h5 class="text-xs font-bold text-slate-800">Detalles de la Defensa</h5>
                    <button @click="selectedDefenses = []" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-4">
                    <template x-for="def in selectedDefenses">
                        <div class="space-y-2 p-3 bg-slate-50 border border-slate-100 rounded-xl">
                            <div class="flex items-center justify-between">
                                <span class="px-2.5 py-0.5 text-[9px] font-bold rounded bg-amber-50 text-amber-700 uppercase tracking-wider" x-text="def.title"></span>
                                <span class="text-[9px] text-slate-400 font-bold" x-text="def.time"></span>
                            </div>
                            <h6 class="text-xs font-bold text-slate-800 leading-normal" x-text="def.production_title"></h6>
                            <div class="text-[10px] text-slate-500">
                                Estudiante: <strong class="text-slate-700" x-text="def.student"></strong>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Static information list if no day selected -->
            <div x-show="selectedDefenses.length === 0" class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <h5 class="text-xs font-extrabold text-slate-500 uppercase tracking-wider">Próximas Defensas</h5>
                
                @if ($defensas->isEmpty())
                    <p class="text-[10px] text-slate-400 text-center py-4">No hay defensas programadas para este periodo.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($defensas->take(3) as $def)
                            <div class="p-3 bg-slate-50/50 border border-slate-100 rounded-xl flex items-start justify-between gap-3">
                                <div class="space-y-1">
                                    <h6 class="text-xs font-bold text-slate-800 line-clamp-1">{{ $def->production->title }}</h6>
                                    <p class="text-[10px] text-slate-500">Estudiante: <strong class="text-slate-700">{{ $def->production->authors }}</strong></p>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="block text-[10px] font-bold text-[#0d4d98]">{{ $def->scheduled_date->format('d \d\e M') }}</span>
                                    <span class="text-[9px] text-slate-400">{{ $def->scheduled_date->format('h:i A') }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
