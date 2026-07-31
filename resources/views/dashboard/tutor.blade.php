@php
    $productions = $data['productions'] ?? collect();
    $defensas = $data['defensas'] ?? collect();
    $roleLabel = $data['roleLabel'] ?? 'Evaluador';
    $suggestedProductions = $data['suggestedProductions'] ?? collect();
@endphp

<div class="space-y-5">
    <!-- Suggested Productions (Sugerencias de vinculación) -->
    @if ($suggestedProductions->isNotEmpty())
        <div class="bg-gradient-to-r from-blue-500/10 via-indigo-500/5 to-transparent border border-blue-100 rounded-2xl p-4 md:p-5 shadow-sm">
            <div class="flex items-center space-x-2.5 mb-4">
                <span class="flex h-2.5 w-2.5 relative">
                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-blue-400 opacity-75"></span>
                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-blue-500"></span>
                </span>
                <h3 class="text-base font-bold text-slate-800">Trabajos científicos sugeridos</h3>
            </div>
            <p class="text-sm text-slate-600 mb-4">
                Hemos encontrado tesis históricas que podrían ser de tu tutoría. Reclama tu vinculación oficial para que aparezcan en tu panel de control.
            </p>
            <div class="grid gap-4 md:grid-cols-2">
                @foreach ($suggestedProductions as $prod)
                    <div class="bg-white border border-slate-100 rounded-xl p-5 shadow-[0_4px_20px_rgba(13,77,152,0.02)] flex flex-col justify-between hover:-translate-y-0.5 hover:shadow-md transition-all duration-200">
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <span class="px-2.5 py-0.5 text-sm font-bold rounded-full bg-slate-100 text-slate-700 uppercase tracking-wider">
                                    {{ $prod->productionType->name ?? 'Tesis' }}
                                </span>
                                <span class="text-sm text-slate-550 font-semibold">
                                    {{ $prod->academicPeriod->name ?? '' }}
                                </span>
                            </div>
                            <h4 class="text-sm font-bold text-slate-800 line-clamp-2 mb-3">{{ $prod->title }}</h4>
                            <div class="space-y-1 text-sm text-slate-600">
                                <p>Autores: <strong class="text-slate-700">{{ $prod->authors }}</strong></p>
                                <p>Tutor: <strong class="text-slate-700">{{ $prod->tutor }}</strong></p>
                            </div>
                        </div>
                        <div class="mt-4 pt-3 border-t border-slate-100 flex items-center justify-end space-x-2">
                            <form id="claim-form-{{ $prod->id }}" action="{{ route('claims.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="production_id" value="{{ $prod->id }}">
                                <input type="hidden" name="role" value="tutor">
                                <button type="button" onclick="confirmClaim('{{ $prod->id }}', 'tutor')" class="px-4 py-2.5 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-lg text-sm font-bold transition">
                                    Reclamar Tutoría
                                </button>
                            </form>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
    <!-- Header Summary Cards -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- Total Assigned -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 hover:shadow-md transition-shadow duration-200">
            <div class="p-3.5 bg-[#0d4d98]/10 text-[#0d4d98] rounded-2xl">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800 leading-none">{{ $productions->count() }}</span>
                <span class="text-sm text-slate-500 font-bold uppercase tracking-wider block mt-1.5">Trabajos Asignados</span>
            </div>
        </div>

        <!-- Pending Review -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 hover:shadow-md transition-shadow duration-200">
            <div class="p-3.5 bg-amber-50 text-amber-600 rounded-2xl border border-amber-100/50">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800 leading-none">
                    {{ $productions->where('workflow_state', 'under_review')->count() }}
                </span>
                <span class="text-sm text-slate-500 font-bold uppercase tracking-wider block mt-1.5">Pendientes de Revisión</span>
            </div>
        </div>

        <!-- Scheduled Defenses -->
        <div class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] flex items-center space-x-4 hover:shadow-md transition-shadow duration-200">
            <div class="p-3.5 bg-emerald-50 text-emerald-600 rounded-2xl border border-emerald-100/50">
                <svg aria-hidden="true" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                </svg>
            </div>
            <div>
                <span class="block text-3xl font-extrabold text-slate-800 leading-none">{{ $defensas->count() }}</span>
                <span class="text-sm text-slate-500 font-bold uppercase tracking-wider block mt-1.5">Hitos/Actividades</span>
            </div>
        </div>
    </div>

    <!-- Main Section Split -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
        
        <!-- Left: Bandeja de Evaluaciones Activas (2/3 width) -->
        <div class="lg:col-span-2 space-y-6">
            <div @if($roleLabel === 'Tutor') id="tutor-assigned-table" @else id="juror-assigned-table" @endif class="bg-white border border-slate-100 rounded-2xl p-5 md:p-6 shadow-[0_10px_30px_rgba(13,77,152,0.03)]">
                <div class="mb-5">
                    <h3 class="text-lg font-extrabold text-slate-800">
                        {{ $roleLabel === 'Tutor' ? 'Bandeja de Tutorías Activas' : 'Bandeja de Evaluaciones (Jurado)' }}
                    </h3>
                    <p class="text-sm text-slate-500 font-semibold mt-0.5 uppercase tracking-wider">
                        {{ $roleLabel === 'Tutor' ? 'Lista de tesis y proyectos científicos bajo tu supervisión y tutoría' : 'Lista de trabajos científicos asignados para tu evaluación final como jurado' }}
                    </p>
                </div>

                @if ($productions->isEmpty())
                    <div class="text-center py-12 border-2 border-dashed border-slate-100 rounded-2xl max-w-lg mx-auto my-6 p-6">
                        <div class="w-12 h-12 bg-slate-50 rounded-xl flex items-center justify-center mx-auto text-slate-400 mb-4 border border-slate-100">
                            <svg aria-hidden="true" class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path>
                            </svg>
                        </div>
                        <h4 class="text-sm font-bold text-slate-700">Sin asignaciones registradas</h4>
                        <p class="mt-1.5 text-sm text-slate-500 leading-relaxed">
                            Actualmente no estás registrado como {{ $roleLabel === 'Tutor' ? 'tutor' : 'jurado' }} en ningún trabajo científico activo en este periodo.
                        </p>
                    </div>
                @else
                    <div class="overflow-x-auto border border-slate-100 rounded-xl">
                        <table class="min-w-full divide-y divide-slate-150 min-w-[650px]">
                            <thead class="bg-slate-50/75">
                                <tr>
                                    <th class="px-4 py-3.5 text-left text-sm font-bold text-slate-500 uppercase tracking-wider">Título de la Obra</th>
                                    <th class="px-4 py-3.5 text-left text-sm font-bold text-slate-500 uppercase tracking-wider">Estudiante</th>
                                    <th class="px-4 py-3.5 text-left text-sm font-bold text-slate-500 uppercase tracking-wider">Estado</th>
                                    <th class="px-4 py-3.5 text-right text-sm font-bold text-slate-500 uppercase tracking-wider">Acciones</th>
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
                                        
                                        // Initials for avatar
                                        $initials = '';
                                        $words = explode(' ', $studentName);
                                        foreach ($words as $w) {
                                            if (!empty($w)) {
                                                $initials .= mb_substr($w, 0, 1);
                                            }
                                            if (mb_strlen($initials) >= 2) break;
                                        }
                                        $initials = strtoupper($initials);
                                    @endphp
                                    <tr class="hover:bg-slate-50/30 transition duration-150">
                                        <td class="px-4 py-4">
                                            <div class="text-sm font-bold text-slate-800 max-w-sm truncate" title="{{ $prod->title }}">
                                                {{ $prod->title }}
                                            </div>
                                            <div class="flex items-center space-x-1.5 mt-1.5">
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md bg-slate-50 border border-slate-200/60 text-xs font-bold text-slate-500 uppercase tracking-wide">
                                                    {{ $prod->productionType->name ?? 'Tesis' }}
                                                </span>
                                                <span class="text-slate-300 text-sm">•</span>
                                                <span class="text-xs text-slate-505 font-medium text-slate-500">
                                                    {{ $prod->academicPeriod->name ?? '' }}
                                                </span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                            <div class="flex items-center space-x-2.5">
                                                <div class="w-8 h-8 rounded-full bg-[#0d4d98]/10 text-[#0d4d98] flex items-center justify-center text-sm font-bold shrink-0 border border-[#0d4d98]/5">
                                                    {{ $initials }}
                                                </div>
                                                <span class="text-base font-semibold text-slate-700">{{ $studentName }}</span>
                                            </div>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap">
                                             <span class="inline-flex items-center px-2.5 py-1 text-xs font-bold rounded-full border {{ $prod->getStatusColorClass() }}">
                                                 <span class="w-1.5 h-1.5 rounded-full mr-1.5 {{ $prod->getStatusBulletColorClass() }}"></span>
                                                 {{ $prod->getStatusLabel() }}
                                             </span>
                                        </td>
                                        <td class="px-4 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <a href="{{ $prod->show_url }}" class="btn-evaluate-production inline-flex items-center space-x-1.5 px-3.5 py-2 bg-[#0d4d98] hover:bg-[#0b3d78] text-white rounded-xl text-sm font-bold transition shadow-sm hover:shadow hover:-translate-y-0.5 duration-150">
                                                <svg aria-hidden="true" class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
        <div class="space-y-4"
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
                    <div>
                        <h4 class="text-sm font-extrabold text-slate-800">Calendario de Defensas</h4>
                        <p class="text-[11px] text-slate-500 font-medium tracking-wide">Cronograma mensual de sustentaciones</p>
                    </div>
                    
                    <!-- Navigation -->
                    <div class="flex items-center space-x-1.5">
                        <button @click="prevMonth()" aria-label="Mes anterior" class="p-1.5 hover:bg-slate-150 hover:bg-slate-100 border border-slate-200/60 rounded-xl text-slate-650 hover:text-slate-800 transition shadow-sm bg-white cursor-pointer">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"></path>
                            </svg>
                        </button>
                        <button @click="nextMonth()" aria-label="Mes siguiente" class="p-1.5 hover:bg-slate-150 hover:bg-slate-100 border border-slate-200/60 rounded-xl text-slate-655 hover:text-slate-800 transition shadow-sm bg-white cursor-pointer">
                            <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path>
                            </svg>
                        </button>
                    </div>
                </div>

                <!-- Month header -->
                <div class="text-center font-bold text-slate-700 capitalize text-sm bg-slate-50/50 py-1.5 rounded-lg border border-slate-100">
                    <span x-text="getMonthName()"></span>
                </div>

                <!-- Calendar grid -->
                <div class="grid grid-cols-7 gap-1 text-center text-sm">
                    <!-- Weekdays -->
                    <template x-for="day in ['D', 'L', 'M', 'M', 'J', 'V', 'S']">
                        <div class="font-extrabold text-slate-400 py-1 uppercase tracking-wider" x-text="day"></div>
                    </template>

                    <!-- Month Days -->
                    <template x-for="d in days">
                        <div class="relative py-1 flex items-center justify-center">
                            <button 
                                @click="selectDay(d)"
                                :disabled="!d.day"
                                :class="{
                                    'w-9 h-9 rounded-xl text-sm font-bold flex items-center justify-center transition-all focus:outline-none focus:ring-2 focus:ring-[#0d4d98]/40': true,
                                    'hover:bg-slate-100 text-slate-700 cursor-pointer': d.day && !d.hasDefense,
                                    'bg-amber-50 text-amber-800 border border-amber-300/80 ring-2 ring-amber-500/10 hover:bg-amber-100 cursor-pointer': d.hasDefense,
                                    'text-slate-300 pointer-events-none': !d.day
                                }"
                                x-text="d.day"
                            ></button>
                            <!-- Visual indicator dot on event days -->
                            <template x-if="d.hasDefense">
                                <span class="absolute bottom-1 w-1.5 h-1.5 rounded-full bg-amber-500"></span>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Defense Details Card (displayed when highlighted day clicked) -->
            <div x-show="selectedDefenses.length > 0" 
                 class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4" 
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 translate-y-2"
                 x-transition:enter-end="opacity-100 translate-y-0"
                 x-transition:leave="transition ease-in duration-150"
                 x-transition:leave-start="opacity-100 translate-y-0"
                 x-transition:leave-end="opacity-0 translate-y-2"
                 style="display: none;">
                <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                    <div>
                        <h5 class="text-sm font-extrabold text-slate-800">Detalles de la Defensa</h5>
                        <p class="text-[11px] text-slate-500 font-medium">Información sobre la evaluación fijada</p>
                    </div>
                    <button @click="selectedDefenses = []" aria-label="Cerrar detalles" class="p-2 -m-2 text-slate-400 hover:text-slate-600 rounded-xl cursor-pointer hover:bg-slate-50 transition">
                        <svg aria-hidden="true" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <template x-for="def in selectedDefenses">
                        <div class="p-3.5 bg-slate-50 border border-slate-100 rounded-xl space-y-2.5">
                            <div class="flex items-center justify-between">
                                <span class="px-2 py-0.5 text-xs font-bold rounded-md bg-amber-50 text-amber-700 border border-amber-200/50 uppercase tracking-wider" x-text="def.title"></span>
                                <span class="text-sm text-slate-600 font-bold bg-white border border-slate-100 px-2 py-0.5 rounded-md shadow-sm" x-text="def.time"></span>
                            </div>
                            <h6 class="text-base font-bold text-slate-800 leading-normal" x-text="def.production_title"></h6>
                            <div class="text-sm text-slate-500 border-t border-slate-200/50 pt-2 flex items-center space-x-1">
                                <span>Estudiante:</span>
                                <strong class="text-slate-750 font-extrabold" x-text="def.student"></strong>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <!-- Static information list if no day selected -->
            <div x-show="selectedDefenses.length === 0" class="bg-white border border-slate-100 rounded-2xl p-5 shadow-[0_10px_30px_rgba(13,77,152,0.03)] space-y-4">
                <div class="border-b border-slate-150 pb-3">
                    <h5 class="text-sm font-extrabold text-slate-800">Próximos Hitos / Entregas</h5>
                    <p class="text-xs text-slate-500 font-medium">Cronograma de eventos más cercanos</p>
                </div>
                
                @if ($defensas->isEmpty())
                    <p class="text-sm text-slate-500 text-center py-6">No hay hitos programados para este periodo.</p>
                @else
                    <div class="space-y-3">
                        @foreach ($defensas->take(3) as $def)
                            @php
                                $months = [
                                    '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr',
                                    '05' => 'May', '06' => 'Jun', '07' => 'Jul', '08' => 'Ago',
                                    '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
                                ];
                                $dateMonth = $months[$def->scheduled_date->format('m')] ?? $def->scheduled_date->format('M');
                                $dateDay = $def->scheduled_date->format('d');
                            @endphp
                            <div class="p-3.5 bg-slate-50/50 border border-slate-100 rounded-2xl flex items-center justify-between gap-4 hover:bg-slate-50 hover:shadow-sm transition duration-200">
                                <div class="flex items-center space-x-3.5">
                                    <!-- Mini calendar sheet -->
                                    <div class="w-11 h-12 bg-white border border-slate-200 rounded-xl flex flex-col items-center justify-center overflow-hidden shrink-0 shadow-sm">
                                        <div class="w-full bg-[#0d4d98] text-xs text-white font-bold uppercase py-0.5 text-center tracking-wider">
                                            {{ $dateMonth }}
                                        </div>
                                        <div class="text-slate-800 text-base font-extrabold leading-none py-1">
                                            {{ $dateDay }}
                                        </div>
                                    </div>
                                    <div class="space-y-0.5">
                                        <h6 class="text-sm font-bold text-slate-800 line-clamp-1" title="{{ $def->production->title }}">
                                            {{ $def->production->title }}
                                        </h6>
                                        <p class="text-xs text-slate-500">
                                            Estudiante: <strong class="text-slate-700 font-semibold">{{ $def->production->authors }}</strong>
                                        </p>
                                    </div>
                                </div>
                                <div class="text-right shrink-0">
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-lg bg-blue-50 text-[#0d4d98] text-sm font-bold border border-blue-100">
                                        {{ $def->scheduled_date->format('h:i A') }}
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
function confirmClaim(prodId, roleText) {
    const form = document.getElementById('claim-form-' + prodId);
    if (!form) return;

    if (typeof Swal !== 'undefined') {
        Swal.fire({
            title: '¿Confirmar reclamo?',
            text: `¿Estás seguro de que deseas reclamar el rol de ${roleText} para este trabajo?`,
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#0d4d98',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, reclamar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const btn = form.querySelector('button[type="button"]');
                if (btn) btn.disabled = true;
                form.submit();
            }
        });
    } else {
        if (confirm(`¿Estás seguro de que deseas reclamar el rol de ${roleText} para este trabajo?`)) {
            const btn = form.querySelector('button[type="button"]');
            if (btn) btn.disabled = true;
            form.submit();
        }
    }
}
</script>
