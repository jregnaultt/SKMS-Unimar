@php
    $currentUser = auth()->user();
    $currentUserRoles = $currentUser->roles->pluck('name')->toArray();
    $activeRole = session('active_dashboard_role') ?? ($currentUserRoles[0] ?? 'Estudiante');
@endphp

<x-dashboard-layout :roles="$currentUserRoles" :activeRole="$activeRole">
    <div class="space-y-6 max-w-8xl mx-auto pb-12">

        <!-- Breadcrumb / Volver -->
        <div>
            <a href="{{ route('admin.users.index') }}" class="inline-flex items-center text-base font-bold text-slate-555 hover:text-unimar-blue transition uppercase tracking-wider">
                <svg aria-hidden="true" class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                Volver a Usuarios
            </a>
        </div>

        <!-- Tarjeta del Formulario -->
        <div class="bg-white border border-slate-200/80 shadow-sm rounded-2xl overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                <h3 class="text-lg font-bold text-slate-800 font-sans">Modificar Roles del Usuario</h3>
                <p class="text-base text-slate-550 font-bold uppercase tracking-wider mt-0.5">Asigna y revoca los roles institucionales del usuario <strong>{{ $user->name }}</strong> ({{ $user->email }})</p>
            </div>

            <form action="{{ route('admin.users.update', $user) }}" method="POST" class="p-8 space-y-6">
                @csrf
                @method('PUT')

                <div class="space-y-4">
                    <label class="block text-base font-bold text-slate-600 uppercase tracking-wider">Roles del Sistema</label>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach($roles as $role)
                            <label class="flex items-start p-4 bg-white border border-slate-200 rounded-xl hover:bg-slate-50/50 cursor-pointer transition select-none">
                                <input type="checkbox"
                                       name="roles[]"
                                       value="{{ $role->name }}"
                                       {{ $user->hasRole($role->name) ? 'checked' : '' }}
                                       class="mt-1 rounded border-slate-300 text-unimar-blue focus:ring-unimar-blue/10 w-4 h-4 cursor-pointer" />
                                <div class="ml-3 text-base">
                                    <span class="block font-bold text-slate-700">{{ $role->name }}</span>
                                    @php
                                        $descriptions = [
                                            'Super Admin' => 'Control absoluto del sistema. Configuración global, copias de seguridad, gestión avanzada.',
                                            'Coordinador' => 'Gestión de programas académicos, líneas de investigación, períodos, reportes y publicación final de documentos.',
                                            'Tutor' => 'Revisión técnica de producciones de estudiantes asignados. Registro de comentarios y aprobaciones.',
                                            'Jurado' => 'Evaluación final y dictamen formal del veredicto del trabajo de grado.',
                                            'Estudiante' => 'Carga de trabajos científicos, respuesta a comentarios y seguimiento de progreso académico.',
                                        ];
                                    @endphp
                                    <span class="block text-base text-slate-550 font-semibold mt-1 leading-relaxed">{{ $descriptions[$role->name] ?? 'Permisos estándar del rol.' }}</span>
                                </div>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Botones de Acción -->
                <div class="flex items-center justify-end space-x-3 pt-6 border-t border-slate-100">
                    <a href="{{ route('admin.users.index') }}"
                       class="py-3 px-5 bg-white hover:bg-slate-50 border border-slate-200 text-slate-700 font-bold rounded-xl text-base uppercase tracking-wider transition focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                        Cancelar
                    </a>
                    <button type="submit"
                            class="py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl text-base uppercase tracking-wider transition shadow-sm hover:shadow-md focus:outline-none h-11 inline-flex items-center justify-center cursor-pointer">
                        Actualizar Roles
                    </button>
                </div>
            </form>
        </div>

    </div>
</x-dashboard-layout>
