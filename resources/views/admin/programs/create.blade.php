<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Nuevo Programa Académico') }}
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
                                Registrar Programa Académico
                            </h3>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                Ingresa la información detallada para habilitar un nuevo programa académico.
                             </p>
                        </div>

                        <form action="{{ route('admin.programs.store') }}" method="POST" class="space-y-6">
                            @csrf

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                <!-- Code -->
                                <div class="col-span-1">
                                    <x-input-label for="code" :value="__('Código del Programa')" />
                                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full uppercase" :value="old('code')" required placeholder="p.ej. ING-SYS" />
                                    <x-input-error class="mt-2" :messages="$errors->get('code')" />
                                </div>

                                <!-- Name -->
                                <div class="col-span-2">
                                    <x-input-label for="name" :value="__('Nombre Completo')" />
                                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required placeholder="p.ej. Ingeniería de Sistemas" />
                                    <x-input-error class="mt-2" :messages="$errors->get('name')" />
                                </div>
                            </div>

                            <!-- Description -->
                            <div>
                                <x-input-label for="description" :value="__('Descripción')" />
                                <textarea id="description" name="description" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 text-gray-900 dark:text-gray-100 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" placeholder="Información opcional sobre el programa o carrera...">{{ old('description') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('description')" />
                            </div>

                            <!-- Active State Switch -->
                            <div class="flex items-center space-x-3">
                                <input type="hidden" name="is_active" value="0">
                                <input id="is_active" name="is_active" type="checkbox" value="1" class="rounded dark:bg-gray-900 border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500" {{ old('is_active', '1') ? 'checked' : '' }}>
                                <label for="is_active" class="text-sm font-semibold text-gray-700 dark:text-gray-300 select-none">
                                    ¿Programa activo para el registro de tesis?
                                </label>
                            </div>

                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 dark:border-gray-700">
                                <a href="{{ route('admin.programs.index') }}" class="px-4 py-2 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 dark:hover:bg-gray-600 text-gray-800 dark:text-gray-200 rounded-lg text-xs font-semibold uppercase tracking-widest transition-all duration-200">
                                    Cancelar
                                </a>
                                <x-primary-button>
                                    {{ __('Registrar Programa') }}
                                </x-primary-button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
