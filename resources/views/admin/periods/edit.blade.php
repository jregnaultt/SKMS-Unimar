@php
    $user = auth()->user();
    $userRoles = $user->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($userRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$userRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-8xl mx-auto pb-12">

        <!-- Breadcrumb / Volver -->
        <div>
            <a href="{{ route('admin.periods.index') }}" class="inline-flex items-center text-base font-bold text-slate-500 hover:text-unimar-blue transition uppercase tracking-wider">
                <svg aria-hidden="true" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a Períodos Académicos
            </a>
        </div>

        <!-- Encabezado con Tabs -->
        <div class="bg-white border border-slate-200/80 shadow-base rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-col md:flex-row md:items-center md:justify-between space-y-4 md:space-y-0">
                <div>
                    <h3 class="text-2xl font-bold text-slate-800 font-sans">Gestión del Período: {{ $period->name }}</h3>
                    <p class="text-base text-slate-500 font-semibold uppercase tracking-wider mt-0.5">Vigencia: {{ $period->start_date?->format('d/m/Y') }} al {{ $period->end_date?->format('d/m/Y') }}</p>
                </div>
                <div>
                    @if($period->is_active)
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-base font-bold bg-emerald-100 text-emerald-800">
                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-emerald-500"></span> Activo
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-base font-bold bg-slate-100 text-slate-800">
                            <span class="w-1.5 h-1.5 mr-1.5 rounded-full bg-slate-400"></span> Inactivo
                        </span>
                    @endif
                </div>
            </div>

            <!-- Navegación de Pestañas (Tabs) -->
            <div class="border-b border-slate-200 bg-white">
                <nav class="flex -mb-px px-6 space-x-6 overflow-x-auto" aria-label="Tabs">
                    <a href="{{ route('admin.periods.edit', [$period, 'tab' => 'info']) }}"
                       class="py-4 px-1 border-b-2 font-bold text-base uppercase tracking-wider whitespace-nowrap transition {{ $tab === 'info' ? 'border-unimar-blue text-unimar-blue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        Configuración
                    </a>
                    <a href="{{ route('admin.periods.edit', [$period, 'tab' => 'tutors']) }}"
                       class="py-4 px-1 border-b-2 font-bold text-base uppercase tracking-wider whitespace-nowrap transition {{ $tab === 'tutors' ? 'border-unimar-blue text-unimar-blue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        Tutores por Materia
                    </a>
                    <a href="{{ route('admin.periods.edit', [$period, 'tab' => 'enrollments']) }}"
                       class="py-4 px-1 border-b-2 font-bold text-base uppercase tracking-wider whitespace-nowrap transition {{ $tab === 'enrollments' ? 'border-unimar-blue text-unimar-blue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        Inscripciones y Tutorados
                    </a>
                    <a href="{{ route('admin.periods.edit', [$period, 'tab' => 'milestones']) }}"
                       class="py-4 px-1 border-b-2 font-bold text-base uppercase tracking-wider whitespace-nowrap transition {{ $tab === 'milestones' ? 'border-unimar-blue text-unimar-blue' : 'border-transparent text-slate-500 hover:text-slate-700 hover:border-slate-300' }}">
                        Cronograma (Actividades)
                    </a>
                </nav>
            </div>

            <!-- Alertas Flash -->
            @if (session('success'))
                <div class="m-6 p-4 bg-emerald-50 border-l-4 border-emerald-500 rounded-r-xl shadow-base text-emerald-800 transition duration-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-bold text-base">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="m-6 p-4 bg-rose-50 border-l-4 border-rose-500 rounded-r-xl shadow-base text-rose-800 transition duration-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-bold text-base">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            <!-- CONTENIDO DE LAS PESTAÑAS -->
            <div class="p-8">

                <!-- 1. TAB: CONFIGURACIÓN (INFO) -->
                @if($tab === 'info')
                    <form action="{{ route('admin.periods.update', $period) }}" method="POST" class="space-y-6">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div>
                                <label for="name" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre del Período</label>
                                <input type="text" id="name" name="name" value="{{ old('name', $period->name) }}" required class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium h-11" />
                                @error('name') <p class="text-base text-rose-600 font-bold mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="start_date" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Fecha de Inicio</label>
                                <input type="date" id="start_date" name="start_date" value="{{ old('start_date', $period->start_date ? $period->start_date->format('Y-m-d') : '') }}" required class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium h-11" />
                                @error('start_date') <p class="text-base text-rose-600 font-bold mt-1">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <label for="end_date" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Fecha de Finalización</label>
                                <input type="date" id="end_date" name="end_date" value="{{ old('end_date', $period->end_date ? $period->end_date->format('Y-m-d') : '') }}" required class="block w-full px-4 py-3 bg-slate-50/50 border border-slate-200 rounded-xl text-base focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition duration-150 text-slate-700 font-medium h-11" />
                                @error('end_date') <p class="text-base text-rose-600 font-bold mt-1">{{ $message }}</p> @enderror
                            </div>
                        </div>

                        <div class="p-4 bg-slate-50 rounded-2xl border border-slate-200/60">
                            <label class="flex items-start cursor-pointer select-none">
                                <input type="hidden" name="is_active" value="0">
                                <input type="checkbox" id="is_active" name="is_active" value="1" {{ old('is_active', $period->is_active) ? 'checked' : '' }} class="mt-0.5 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                <div class="ml-3 text-base">
                                    <span class="block font-bold text-slate-700">Establecer período académico como activo</span>
                                    <span class="block text-slate-500 font-semibold mt-0.5">Si está inactivo, no se podrán realizar entregas de trabajos científicos.</span>
                                </div>
                            </label>
                        </div>

                        <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                            <button type="submit" class="py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-base h-11 inline-flex items-center cursor-pointer">
                                Guardar Cambios
                            </button>
                        </div>
                    </form>
                @endif

                <!-- 2. TAB: TUTORES POR MATERIA -->
                @if($tab === 'tutors')
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- Formulario de Asociación -->
                        <div class="lg:col-span-1 bg-slate-50 border border-slate-200/60 rounded-2xl p-6">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider mb-4">Habilitar Tutor en Materia</h4>

                            <form action="{{ route('admin.periods.tutors.store', $period) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="subject_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Unidad Curricular (Materia)</label>
                                    <select name="subject_id" id="subject_id" required class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                        <option value="">Selecciona materia...</option>
                                        @foreach($subjects as $subj)
                                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="tutor_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tutor Académico</label>
                                    <x-advanced-select 
                                        name="tutor_id" 
                                        placeholder="Seleccione tutor..." 
                                        endpoint="/admin/users/search?role=Tutor"
                                    />
                                </div>
                                <button type="submit" class="w-full mt-4 py-3 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-base h-11 inline-flex items-center justify-center cursor-pointer">
                                    Habilitar Tutor
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Asignaciones Activas -->
                        <div class="lg:col-span-2 space-y-4">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider">Tutores Activos en este Período</h4>

                            @if($activeTutors->isEmpty())
                                <div class="p-8 text-center bg-slate-50 border border-dashed border-slate-200 rounded-2xl">
                                    <p class="text-base text-slate-550 font-semibold">No se han registrado tutores para materias en este período académico.</p>
                                </div>
                            @else
                                <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-base">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Materia</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Tutor</th>
                                                <th class="px-6 py-3 text-right text-base font-bold text-slate-550 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($activeTutors as $item)
                                                <tr class="hover:bg-slate-50/40 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-slate-800">{{ $item->subject->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-base text-slate-650 font-medium">
                                                        <div>{{ $item->tutor->name }}</div>
                                                        <div class="text-base text-slate-400 font-semibold">{{ $item->tutor->email }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-base">
                                                        <form action="{{ route('admin.periods.tutors.destroy', [$period, $item->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas deshabilitar a este tutor para esta materia en este período?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold uppercase tracking-wider text-base cursor-pointer">
                                                                Deshabilitar
                                                            </button>              </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- 3. TAB: INSCRIPCIONES -->
                @if($tab === 'enrollments')
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8" x-data="{
                        selectedSubject: '',
                        subjectTutors: {{ json_encode($subjectTutors) }},
                        filteredTutors: [],
                        updateTutors() {
                            if (!this.selectedSubject || !this.subjectTutors[this.selectedSubject]) {
                                this.filteredTutors = [];
                            } else {
                                this.filteredTutors = this.subjectTutors[this.selectedSubject];
                            }
                        }
                    }">

                        <!-- Formulario de Inscripción -->
                        <div class="lg:col-span-1 bg-slate-50 border border-slate-200/60 rounded-2xl p-6">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider mb-4">Inscribir Nuevo Estudiante</h4>

                            <form action="{{ route('admin.periods.enrollments.store', $period) }}" method="POST" class="space-y-4">
                                @csrf
                                <div>
                                    <label for="student_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Estudiante</label>
                                    <x-advanced-select 
                                        name="student_id" 
                                        placeholder="Seleccione estudiante..." 
                                        endpoint="/admin/users/search?role=Estudiante"
                                    />
                                </div>
                                <div>
                                    <label for="enrollment_subject_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Materia (Unidad Curricular)</label>
                                    <select name="subject_id" id="enrollment_subject_id" x-model="selectedSubject" @change="updateTutors()" required class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                        <option value="">Selecciona materia...</option>
                                        @foreach($subjects as $subj)
                                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="enrollment_tutor_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tutor Asignado</label>
                                    <x-advanced-select 
                                        name="tutor_id" 
                                        placeholder="-- Selecciona Tutor Habilitado --"
                                        optionsWatch="filteredTutors"
                                    />
                                    <p class="text-base text-slate-500 font-semibold mt-1">Solo se muestran los tutores habilitados previamente en esta materia.</p>
                                </div>
                                <button type="submit" class="w-full mt-4 py-3 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-base h-11 inline-flex items-center justify-center cursor-pointer">
                                    Inscribir Alumno
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Estudiantes Inscritos -->
                        <div class="lg:col-span-2 space-y-4">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider">Estudiantes Inscritos en este Período</h4>

                            @if($enrollments->isEmpty())
                                <div class="p-8 text-center bg-slate-50 border border-dashed border-slate-200 rounded-2xl">
                                    <p class="text-base text-slate-500 font-semibold">No hay estudiantes inscritos en este período académico todavía.</p>
                                </div>
                            @else
                                <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-base">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Estudiante</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Materia</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Tutor Asignado</th>
                                                <th class="px-6 py-3 text-right text-base font-bold text-slate-550 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($enrollments as $enr)
                                                <tr class="hover:bg-slate-50/40 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-slate-800">
                                                        <div>{{ $enr->student->name }}</div>
                                                        <div class="text-base text-slate-400 font-semibold">{{ $enr->student->email }}</div>
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-base text-slate-650 font-bold">{{ $enr->subject->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-base text-slate-600 font-medium">
                                                        @if($enr->tutor)
                                                            {{ $enr->tutor->name }}
                                                        @else
                                                            <span class="text-rose-500 font-bold uppercase tracking-wider text-base bg-rose-50 px-2 py-0.5 rounded-md">Sin Asignar</span>
                                                        @endif
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-base">
                                                        <form action="{{ route('admin.periods.enrollments.destroy', [$period, $enr->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar la inscripción de este estudiante?');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold uppercase tracking-wider text-base cursor-pointer">
                                                                Eliminar
                                                            </button>                     </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

                <!-- 4. TAB: CRONOGRAMA DE ACTIVIDADES (HITOS) -->
                @if($tab === 'milestones')
                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">

                        <!-- Formulario de Hitos -->
                        <div class="lg:col-span-1 bg-slate-50 border border-slate-200/60 rounded-2xl p-6">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider mb-4">Programar Actividad</h4>

                            <form action="{{ route('admin.periods.milestones.store', $period) }}" 
                                  method="POST" 
                                  class="space-y-4"
                                  x-data="{
                                      subjectId: '',
                                      tutorId: '',
                                      selectedId: '',
                                      selectedName: '',
                                      students: [],
                                      loadingStudents: false,
                                      async fetchTutorStudents() {
                                          if (!this.subjectId || !this.tutorId) {
                                              this.students = [];
                                              return;
                                          }
                                          this.loadingStudents = true;
                                          try {
                                              let res = await fetch(`/admin/periods/{{ $period->id }}/students-under-tutor?subject_id=${this.subjectId}&tutor_id=${this.tutorId}`);
                                              let data = await res.json();
                                              this.students = data.map(s => ({ ...s, checked: true }));
                                          } catch (e) {
                                              console.error(e);
                                          } finally {
                                              this.loadingStudents = false;
                                          }
                                      }
                                  }">
                                @csrf
                                <div>
                                    <label for="milestone_subject_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Unidad Curricular (Materia)</label>
                                    <select name="subject_id" id="milestone_subject_id" required x-model="subjectId" @change="fetchTutorStudents()" class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                        <option value="">Selecciona materia...</option>
                                        @foreach($subjects as $subj)
                                            <option value="{{ $subj->id }}">{{ $subj->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div>
                                    <label for="milestone_title" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Título de la Actividad</label>
                                    <input type="text" name="title" id="milestone_title" required placeholder="Ej: Entrega de Capítulo 1" class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                </div>
                                <div>
                                    <label for="type" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Tipo de Actividad</label>
                                    <select name="type" id="type" required class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                        <option value="delivery">Entrega de Avance / Documento</option>
                                        <option value="pre_defense">Pre-Defensa</option>
                                        <option value="defense">Defensa Académica</option>
                                        <option value="system_defense">Defensa de Sistema</option>
                                    </select>
                                </div>
                                <div>
                                    <label for="milestone_tutor_id" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Grupo de Tutor (Opcional)</label>
                                    <div @change="tutorId = $event.detail.value.length ? $event.detail.value[0].id : ''; fetchTutorStudents()">
                                        <x-advanced-select 
                                            name="tutor_id" 
                                            placeholder="Todos los Estudiantes (Global)..." 
                                            endpoint="/admin/users/search?role=Tutor"
                                            disabled="selectedId !== ''"
                                        />
                                    </div>
                                    <p class="text-base text-slate-500 font-semibold mt-1">Si seleccionas un tutor, esta fecha límite aplicará solo a sus alumnos tutorados.</p>
                                </div>

                                <!-- Listado de Alumnos del Tutor para Exclusión -->
                                <div x-show="tutorId && subjectId && students.length > 0" class="bg-white border border-slate-200 rounded-xl p-4 space-y-2 mt-2 shadow-sm" style="display: none;">
                                    <label class="block text-sm font-bold text-slate-700 uppercase tracking-wider">Enviar a los siguientes alumnos:</label>
                                    <div class="space-y-2 max-h-40 overflow-y-auto pr-1">
                                        <template x-for="student in students" :key="student.id">
                                            <div class="flex items-start">
                                                <input type="checkbox" 
                                                       :id="'student_checkbox_' + student.id"
                                                       x-model="student.checked"
                                                       class="rounded border-slate-350 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer mt-0.5 mr-2.5" />
                                                
                                                <template x-if="!student.checked">
                                                    <input type="hidden" name="excluded_students[]" :value="student.id" />
                                                </template>
                                                
                                                <label :for="'student_checkbox_' + student.id" class="text-sm text-slate-750 font-bold leading-tight cursor-pointer select-none">
                                                    <span x-text="student.name"></span>
                                                    <span class="block text-xs text-slate-500 font-semibold mt-0.5" x-text="student.email + ' (C.I. ' + (student.cedula || 'N/A') + ')'"></span>
                                                </label>
                                            </div>
                                        </template>
                                    </div>
                                    <p class="text-xs text-slate-500 font-semibold leading-normal mt-2">Desmarca a los estudiantes a quienes NO deseas enviarles esta actividad.</p>
                                </div>
                                <div x-show="tutorId && subjectId && students.length === 0 && !loadingStudents" class="p-3 text-center bg-slate-100/60 rounded-xl text-sm text-slate-500 font-semibold border border-slate-200/50" style="display: none;">
                                    No hay estudiantes inscritos con este tutor en esta materia.
                                </div>
                                <div x-show="loadingStudents" class="flex items-center justify-center p-3 space-x-2 text-sm text-slate-500 font-semibold" style="display: none;">
                                    <svg class="animate-spin h-4 w-4 text-unimar-blue" fill="none" viewBox="0 0 24 24">
                                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                    </svg>
                                    <span>Buscando alumnos tutorados...</span>
                                </div>

                                <div @change="selectedId = $event.detail.value.length ? $event.detail.value[0].id : ''; selectedName = $event.detail.value.length ? $event.detail.value[0].text : ''">
                                    <label class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Estudiante Específico (Opcional)</label>
                                    <x-advanced-select 
                                        name="student_id" 
                                        placeholder="Buscar por Nombre, Cédula o Correo..." 
                                        endpoint="/admin/users/search?role=Estudiante"
                                        disabled="tutorId !== ''"
                                    />
                                    <p class="text-base text-slate-500 font-semibold mt-1">Si seleccionas un estudiante, la actividad aplicará únicamente a él.</p>
                                </div>
                                <div>
                                    <label for="scheduled_date" class="block text-base font-bold text-slate-600 uppercase tracking-wider mb-1.5">Fecha y Hora Límite</label>
                                    <input type="datetime-local" name="scheduled_date" id="scheduled_date" required class="block w-full rounded-xl border-slate-200 bg-white text-slate-700 text-base py-2.5 px-3 focus:outline-none focus:ring-2 focus:ring-unimar-blue/50 h-11">
                                </div>
                                <div class="flex flex-col space-y-2.5 pt-2">
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="notify_tutor" value="1" checked class="rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer mr-2.5" />
                                        <span class="text-base font-bold text-slate-700 uppercase tracking-wider">Avisar al Tutor</span>
                                    </label>
                                    <label class="flex items-center cursor-pointer select-none">
                                        <input type="checkbox" name="notify_jury" value="1" class="rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer mr-2.5" />
                                        <span class="text-base font-bold text-slate-700 uppercase tracking-wider">Avisar a Jurados Asignados</span>
                                    </label>
                                </div>
                                <button type="submit" class="w-full mt-4 py-3 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-base h-11 inline-flex items-center justify-center cursor-pointer">
                                    Programar Actividad
                                </button>
                            </form>
                        </div>

                        <!-- Lista de Hitos Registrados -->
                        <div class="lg:col-span-2 space-y-4">
                            <h4 class="text-base font-bold text-slate-800 uppercase tracking-wider">Actividades Programadas</h4>

                            @if($milestones->isEmpty())
                                <div class="p-8 text-center bg-slate-50 border border-dashed border-slate-200 rounded-2xl">
                                    <p class="text-base text-slate-500 font-semibold">No se han registrado actividades para este período todavía.</p>
                                </div>
                            @else
                                <div class="bg-white border border-slate-200/80 rounded-2xl overflow-hidden shadow-base">
                                    <table class="min-w-full divide-y divide-slate-100">
                                        <thead class="bg-slate-50">
                                            <tr>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Materia</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Actividad</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Alcance / Grupo</th>
                                                <th class="px-6 py-3 text-left text-base font-bold text-slate-550 uppercase tracking-wider">Fecha Límite</th>
                                                <th class="px-6 py-3 text-right text-base font-bold text-slate-550 uppercase tracking-wider">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-slate-100 bg-white">
                                            @foreach($milestones as $mil)
                                                <tr class="hover:bg-slate-50/40 transition-colors">
                                                    <td class="px-6 py-4 whitespace-nowrap text-base font-bold text-slate-800">{{ $mil->subject->name }}</td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-base text-slate-700 font-medium">
                                                         <div class="font-bold">{{ $mil->title }}</div>
                                                         <div class="flex items-center space-x-2.5 mt-1 text-xs font-semibold text-slate-400">
                                                             <span class="uppercase tracking-wider">
                                                                 @if($mil->type === 'delivery') Entrega
                                                                 @elseif($mil->type === 'pre_defense') Pre-Defensa
                                                                 @elseif($mil->type === 'defense') Defensa
                                                                 @elseif($mil->type === 'system_defense') Defensa Sistema
                                                                 @endif
                                                             </span>
                                                             <span>•</span>
                                                             <span class="flex items-center">
                                                                 <span class="w-1.5 h-1.5 rounded-full mr-1 {{ $mil->notify_tutor ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                                                 Aviso Tutor
                                                             </span>
                                                             <span class="flex items-center">
                                                                 <span class="w-1.5 h-1.5 rounded-full mr-1 {{ $mil->notify_jury ? 'bg-emerald-500' : 'bg-slate-300' }}"></span>
                                                                 Aviso Jurados
                                                             </span>
                                                         </div>
                                                     </td>
                                                     <td class="px-6 py-4 whitespace-nowrap text-base text-slate-650 font-medium">
                                                         @if($mil->student)
                                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-bold bg-amber-50 text-amber-700 border border-amber-200">
                                                                 Estudiante: {{ $mil->student->name }}
                                                             </span>
                                                         @elseif($mil->tutor)
                                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-bold bg-unimar-blue/5 text-unimar-blue border border-unimar-blue/10">
                                                                 Grupo de {{ $mil->tutor->name }}
                                                             </span>
                                                             @if($mil->excluded_student_ids && count($mil->excluded_student_ids) > 0)
                                                                 <div class="text-xs font-semibold text-rose-600 mt-1 max-w-xs whitespace-normal">
                                                                     Excluidos: {{ \App\Models\User::whereIn('id', $mil->excluded_student_ids)->pluck('name')->implode(', ') }}
                                                                 </div>
                                                             @endif
                                                         @else
                                                             <span class="inline-flex items-center px-2 py-0.5 rounded text-base font-bold bg-indigo-50 text-indigo-700 border border-indigo-100">
                                                                 Global (Todos)
                                                             </span>
                                                         @endif
                                                     </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-base text-slate-600 font-bold">
                                                        {{ $mil->scheduled_date ? $mil->scheduled_date->format('d/m/Y h:i A') : 'No asignada' }}
                                                    </td>
                                                    <td class="px-6 py-4 whitespace-nowrap text-right text-base">
                                                        <form action="{{ route('admin.periods.milestones.destroy', [$period, $mil->id]) }}" method="POST" onsubmit="return confirm('¿Seguro que deseas eliminar esta actividad? Se quitará de todos los estudiantes correspondientes.');">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="text-rose-600 hover:text-rose-800 font-bold uppercase tracking-wider text-base cursor-pointer">
                                                                Eliminar
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif

            </div>
        </div>

    </div>
</x-dashboard-layout>
