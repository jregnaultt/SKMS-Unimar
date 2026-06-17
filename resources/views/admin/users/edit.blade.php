<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Editar Roles de Usuario') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="flex flex-col md:flex-row gap-6">
                <!-- Sidebar -->
                @include('admin.shared.sidebar')

                <!-- Main Content -->
                <div class="flex-1 bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-xl border border-gray-100 dark:border-gray-700">
                    <div class="p-6">
                        <div class="mb-6">
                            <h3 class="text-lg font-bold text-gray-900 dark:text-gray-100">
                                Modificar Roles del Usuario
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Selecciona los roles del sistema que pertenecerán a <strong>{{ $user->name }}</strong> ({{ $user->email }}).
                            </p>
                        </div>

                        <form action="{{ route('admin.users.update', $user) }}" method="POST" class="space-y-6">
                            @csrf
                            @method('PUT')

                            <div class="space-y-4">
                                <span class="block text-sm font-bold text-gray-700 dark:text-gray-300">Roles Disponibles</span>
                                
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    @foreach($roles as $role)
                                        <label class="flex items-start p-3 bg-gray-50 dark:bg-gray-900/50 hover:bg-gray-100 dark:hover:bg-gray-900 border border-gray-200 dark:border-gray-700 rounded-xl cursor-pointer transition-colors duration-150 select-none">
                                            <input type="checkbox" name="roles[]" value="{{ $role->name }}" class="mt-1 rounded dark:bg-gray-800 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ $user->hasRole($role->name) ? 'checked' : '' }}>
                                            <div class="ml-3">
                                                <span class="block text-sm font-bold text-gray-900 dark:text-gray-100">{{ $role->name }}</span>
                                                @php
                                                    $descriptions = [
                                                        'Super Admin' => 'Control absoluto del sistema. Configuración global, copias de seguridad, gestión avanzada.',
                                                        'Coordinador' => 'Gestión de programas académicos, líneas de investigación, períodos, reportes y publicación final de documentos.',
                                                        'Tutor' => 'Revisión técnica de producciones de estudiantes asignados. Registro de comentarios y aprobaciones.',
                                                        'Jurado' => 'Evaluación final y dictamen formal del veredicto del trabajo de grado.',
                                                        'Estudiante' => 'Carga de trabajos científicos, respuesta a comentarios y seguimiento de progreso académico.',
                                                    ];
                                                @endphp
                                                <span class="block text-xs text-gray-500 mt-0.5">{{ $descriptions[$role->name] ?? 'Permisos estándar del rol.' }}</span>
                                            </div>
                                        </label>
                                    @endforeach
                                </div>
                                <x-input-error class="mt-2" :messages="$errors->get('roles')" />
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <a href="{{ route('admin.users.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold uppercase tracking-widest transition-all duration-200">
                                    Cancelar
                                </a>
                                <x-primary-button>
                                    {{ __('Actualizar Roles') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
