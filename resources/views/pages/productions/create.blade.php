<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Subir Producción Científica') }}
        </h2>
    </x-slot>

    <div class="py-12" x-data="documentUpload({{ $researchLines->toJson() }})">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

            <!-- Alerts -->
            @if (session('success'))
                <div class="p-4 bg-emerald-50 dark:bg-emerald-950/20 border-l-4 border-emerald-500 rounded-r-lg shadow-sm text-emerald-800 dark:text-emerald-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('success') }}</span>
                    </div>
                </div>
            @endif

            @if (session('error'))
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 mr-3 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        <span class="font-medium text-sm">{{ session('error') }}</span>
                    </div>
                </div>
            @endif

            @if ($errors->any())
                <div class="p-4 bg-rose-50 dark:bg-rose-950/20 border-l-4 border-rose-500 rounded-r-lg shadow-sm text-rose-800 dark:text-rose-300">
                    <div class="font-semibold text-sm mb-2">Por favor, corrige los siguientes errores:</div>
                    <ul class="list-disc list-inside text-xs space-y-1">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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

                    <form method="POST" action="{{ route('productions.store') }}" @submit="submitForm($event)">
                        @csrf
                        
                        <!-- Hidden inputs for file reference and button action -->
                        <input type="hidden" name="file_id" :value="fileId">
                        <input type="hidden" name="action" :value="action">

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            
                            <!-- Title -->
                            <div class="md:col-span-2">
                                <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Título</label>
                                <input type="text" name="title" id="title" x-model="metadata.title" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Abstract -->
                            <div class="md:col-span-2">
                                <label for="abstract" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Resumen</label>
                                <textarea name="abstract" id="abstract" x-model="metadata.abstract" required rows="4" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out"></textarea>
                            </div>

                            <!-- Authors -->
                            <div>
                                <label for="authors" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Autor(es)</label>
                                <input type="text" name="authors" id="authors" x-model="metadata.authors" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Tutor -->
                            <div>
                                <label for="tutor" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tutor</label>
                                <input type="text" name="tutor" id="tutor" x-model="metadata.tutor" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                            </div>

                            <!-- Academic Program -->
                            <div>
                                <label for="academic_program_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Programa Académico</label>
                                <select name="academic_program_id" id="academic_program_id" x-model="academicProgramId" @change="filterResearchLines" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                                    <option value="">Seleccione un programa...</option>
                                    @foreach($academicPrograms as $prog)
                                        <option value="{{ $prog->id }}">{{ $prog->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Research Line (Filtered dynamically based on selected program) -->
                            <div>
                                <label for="research_line_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Línea de Investigación</label>
                                <select name="research_line_id" id="research_line_id" x-model="researchLineId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                                    <option value="">Seleccione una línea...</option>
                                    <template x-for="line in filteredResearchLines" :key="line.id">
                                        <option :value="line.id" x-text="line.name" :selected="line.id == researchLineId"></option>
                                    </template>
                                </select>
                            </div>

                            <!-- Production Type -->
                            <div>
                                <label for="production_type_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tipo de Producción</label>
                                <select name="production_type_id" id="production_type_id" x-model="productionTypeId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                                    <option value="">Seleccione un tipo...</option>
                                    @foreach($productionTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Academic Period -->
                            <div>
                                <label for="academic_period_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Período Académico</label>
                                <select name="academic_period_id" id="academic_period_id" x-model="academicPeriodId" required class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:bg-gray-900 dark:border-gray-700 dark:text-gray-300 sm:text-sm transition duration-150 ease-in-out">
                                    <option value="">Seleccione un período...</option>
                                    @foreach($academicPeriods as $period)
                                        <option value="{{ $period->id }}">{{ $period->name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Keywords Tags/Chips Component (Commit 5) -->
                            <div class="md:col-span-2">
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Palabras Clave</label>
                                <div class="flex flex-wrap gap-2 p-2.5 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-900 min-h-[46px] focus-within:ring-1 focus-within:ring-indigo-500 focus-within:border-indigo-500">
                                    <template x-for="(tag, index) in keywordList" :key="index">
                                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-300 transition-all duration-200">
                                            <span x-text="tag"></span>
                                            <button type="button" @click="removeKeyword(index)" class="flex-shrink-0 ml-1.5 inline-flex items-center justify-center text-indigo-400 hover:text-indigo-600 focus:outline-none">
                                                <svg class="h-3 w-3" stroke="currentColor" fill="none" viewBox="0 0 8 8">
                                                    <path stroke-linecap="round" stroke-width="1.5" d="M1 1l6 6m0-6L1 7" />
                                                </svg>
                                            </button>
                                        </span>
                                    </template>
                                    <input type="text" placeholder="Añadir palabra..." x-model="newTag" @keydown.enter.prevent="addKeyword" @keydown.comma.prevent="addKeyword" @blur="addKeyword" class="flex-1 border-0 p-0 text-sm focus:ring-0 dark:bg-gray-900 dark:text-gray-300 min-w-[150px] bg-transparent">
                                </div>
                                <input type="hidden" name="keywords" :value="keywordList.join(',')">
                                <p class="mt-1.5 text-xs text-gray-500 dark:text-gray-400">Presiona Enter o Coma para añadir una palabra clave.</p>
                            </div>

                        </div>

                        <!-- Form Actions -->
                        <div class="mt-10 pt-6 border-t border-gray-250 dark:border-gray-700 flex items-center justify-end space-x-3">
                            <!-- Guardar como Borrador -->
                            <button type="submit" @click="action = 'draft'" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-semibold text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-800 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150">
                                Guardar como Borrador
                            </button>

                            <!-- Enviar a Revisión -->
                            <button type="submit" @click="action = 'submit'" class="inline-flex items-center px-6 py-2.5 border border-transparent text-sm font-bold rounded-lg shadow-md text-white bg-indigo-600 hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition duration-150 transform hover:-translate-y-0.5">
                                Guardar y Enviar a Revisión
                            </button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</x-app-layout>
