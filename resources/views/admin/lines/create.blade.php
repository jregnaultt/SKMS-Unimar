<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nueva Línea de Investigación') }}
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
                                Registrar Línea de Investigación
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Asocia la nueva línea a un programa académico maestro.
                            </p>
                        </div>

                        <form action="{{ route('admin.lines.store') }}" method="POST" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Academic Program Select -->
                                <div class="col-span-1">
                                    <x-input-label for="academic_program_id" :value="__('Programa Académico')" />
                                    <select id="academic_program_id" name="academic_program_id" required class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                                        <option value="">Selecciona un programa...</option>
                                        @foreach($programs as $program)
                                            <option value="{{ $program->id }}" {{ old('academic_program_id') == $program->id ? 'selected' : '' }}>
                                                {{ $program->name }} ({{ $program->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('academic_program_id')" />
                                </div>

                                <!-- Name -->
                                <div class="col-span-2">
                                    <x-input-label for="name" :value="__('Nombre de la Línea')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="p.ej. Inteligencia Artificial y Sistemas Expertos" />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Descripción')" />
                                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Describe brevemente el alcance de esta línea de investigación...">{{ old('description') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>

                            <!-- Active State Switch -->
                            <div class="flex items-center space-x-3">
                                <input type="hidden" name="is_active" value="0">
                                <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300 select-none">
                                    ¿Línea activa para trabajos de investigación?
                                </label>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <a href="{{ route('admin.lines.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold uppercase tracking-widest transition-all duration-200">
                                    Cancelar
                                </a>
                                <x-primary-button>
                                    {{ __('Registrar Línea') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
