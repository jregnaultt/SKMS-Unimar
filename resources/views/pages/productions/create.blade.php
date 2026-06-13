<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Subir Producción Científica') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="documentUpload">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg border border-gray-100 dark:border-gray-700">
                <div class="p-6 text-gray-900 dark:text-gray-100">
                    
                    <!-- Form Header -->
                    <div class="mb-8 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h3 class="text-lg font-medium text-indigo-600 dark:text-indigo-400">1. Carga del Documento (PDF o Word)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Sube tu trabajo de grado en formato PDF o Word (DOCX). Nuestro sistema de inteligencia artificial extraerá automáticamente los metadatos para facilitarte el trabajo.
                        </p>
                    </div>

                    <!-- File Upload Section -->
                    <div class="mb-10">
                        <label class="flex justify-center w-full h-32 px-4 transition bg-white dark:bg-gray-800 border-2 border-gray-300 dark:border-gray-600 border-dashed rounded-md appearance-none cursor-pointer hover:border-indigo-400 focus:outline-none"
                               :class="{'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20': isUploading}">
                            <span class="flex items-center space-x-2">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-gray-600 dark:text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12" />
                                </svg>
                                <span class="font-medium text-gray-600 dark:text-gray-400" x-text="file ? file.name : 'Selecciona o arrastra el archivo PDF o Word (DOCX)'"></span>
                            </span>
                            <input type="file" name="file_upload" class="hidden" accept="application/pdf, application/vnd.openxmlformats-officedocument.wordprocessingml.document" @change="handleFileSelect">
                        </label>

                        <!-- Upload Progress & Status -->
                        <div x-show="isUploading || statusMessage" x-transition class="mt-4" style="display: none;">
                            <div class="flex items-center justify-between mb-1">
                                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400" x-text="statusMessage"></span>
                                <span class="text-sm font-medium text-indigo-600 dark:text-indigo-400" x-show="isUploading" x-text="uploadProgress + '%'"></span>
                            </div>
                            <div class="w-full bg-gray-200 rounded-full h-2.5 dark:bg-gray-700" x-show="isUploading">
                                <div class="bg-indigo-600 h-2.5 rounded-full transition-all duration-300" :style="'width: ' + uploadProgress + '%'"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Metadata Section (Populated Automatically) -->
                    <div class="mb-4 border-b border-gray-200 dark:border-gray-700 pb-4">
                        <h3 class="text-lg font-medium text-indigo-600 dark:text-indigo-400">2. Metadatos Extraídos (Dublin Core)</h3>
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                            Verifica y corrige la información extraída automáticamente.
                        </p>
                    </div>

                    <form method="POST" action="#">
                        @csrf
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                <input type="text" id="title" x-model="metadata.title" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Abstract -->
                            <div class="md:col-span-2">
                                <label for="abstract" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resumen</label>
                                <textarea id="abstract" x-model="metadata.abstract" rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out"></textarea>
                            </div>

                            <!-- Authors -->
                            <div>
                                <label for="authors" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Autor(es)</label>
                                <input type="text" id="authors" x-model="metadata.authors" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Tutor -->
                            <div>
                                <label for="tutor" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tutor</label>
                                <input type="text" id="tutor" x-model="metadata.tutor" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Keywords -->
                            <div class="md:col-span-2">
                                <label for="keywords" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Palabras Clave</label>
                                <input type="text" id="keywords" x-model="metadata.keywords" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                                <p class="mt-1 text-xs text-gray-500">Separadas por comas.</p>
                            </div>

                        </div>

                        <!-- Submit Button -->
                        <div class="mt-8 flex justify-end">
                            <button type="button" class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md shadow-sm text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 ease-in-out transform hover:-translate-y-0.5">
                                Guardar Producción y Enviar a Revisión
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
