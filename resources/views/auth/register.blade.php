<x-guest-layout>
    <!-- Encabezado de la Tarjeta -->
    <div class="text-center mb-8">
        <h2 class="text-2xl font-bold text-slate-800 tracking-tight font-sans">Registro de Investigador</h2>
        <p class="text-xs text-slate-500 mt-1.5 font-medium">Crea tu cuenta para gestionar tu producción científica</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Nombre Completo -->
        <div>
            <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre Completo</label>
            <input id="name" 
                   type="text" 
                   name="name" 
                   value="{{ old('name') }}" 
                   required 
                   autofocus 
                   autocomplete="name" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="Juan Pérez" />
            <x-input-error :messages="$errors->get('name')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Correo Electrónico -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Correo Institucional</label>
            <input id="email" 
                   type="email" 
                   name="email" 
                   value="{{ old('email') }}" 
                   required 
                   autocomplete="username" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                   placeholder="juan.perez@unimar.edu.ve" />
            <x-input-error :messages="$errors->get('email')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Cédula de Identidad -->
        <div x-data="{ prefix: '{{ old('cedula') ? Str::before(old('cedula'), '-') : 'V' }}', number: '{{ old('cedula') ? Str::after(old('cedula'), '-') : '' }}' }">
            <label for="cedula_number" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Cédula de Identidad</label>
            <div class="flex rounded-xl overflow-hidden border border-slate-200/80 focus-within:border-unimar-blue focus-within:ring focus-within:ring-unimar-blue/10 transition-all duration-150 bg-slate-50/50">
                <select x-model="prefix" class="border-0 bg-transparent text-slate-700 text-sm py-2.5 pl-4 pr-8 focus:ring-0 cursor-pointer font-bold">
                    <option value="V">V</option>
                    <option value="E">E</option>
                </select>
                <input type="hidden" name="cedula" :value="number ? prefix + '-' + number : ''">
                <input id="cedula_number" 
                       x-model="number" 
                       type="text" 
                       required 
                       placeholder="12345678" 
                       class="block w-full border-0 bg-transparent text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:ring-0" />
            </div>
            <x-input-error :messages="$errors->get('cedula')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Teléfono de Contacto -->
        <div>
            <label for="telefono" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Teléfono de Contacto</label>
            <input id="telefono" 
                   type="tel" 
                   name="telefono" 
                   value="{{ old('telefono') }}" 
                   required 
                   placeholder="+584141234567" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" />
            <p class="text-[10px] text-slate-400 mt-1 font-medium">Debe incluir el código de país (ej. +58)</p>
            <x-input-error :messages="$errors->get('telefono')" class="mt-1.5 text-xs text-rose-600" />
        </div>

        <!-- Contraseña y Confirmación (Grid en escritorio) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Contraseña -->
            <div>
                <label for="password" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Contraseña</label>
                <input id="password" 
                       type="password" 
                       name="password" 
                       required 
                       autocomplete="new-password" 
                       class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                <x-input-error :messages="$errors->get('password')" class="mt-1.5 text-xs text-rose-600" />
            </div>

            <!-- Confirmar Contraseña -->
            <div>
                <label for="password_confirmation" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Confirmar Contraseña</label>
                <input id="password_confirmation" 
                       type="password" 
                       name="password_confirmation" 
                       required 
                       autocomplete="new-password" 
                       class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" 
                       placeholder="&bull;&bull;&bull;&bull;&bull;&bull;&bull;&bull;" />
                <x-input-error :messages="$errors->get('password_confirmation')" class="mt-1.5 text-xs text-rose-600" />
            </div>
        </div>

        <!-- Botón de Acción Principal -->
        <div class="pt-4">
            <button type="submit" class="w-full flex justify-center items-center py-3 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-sm">
                Crear Cuenta
            </button>
        </div>
    </form>

    <!-- Enlace de Inicio de Sesión -->
    <div class="mt-8 pt-6 border-t border-slate-100 text-center">
        <p class="text-xs text-slate-500 font-medium">
            ¿Ya tienes una cuenta de investigador? 
            <a href="{{ route('login') }}" class="text-unimar-blue hover:text-unimar-blue/80 font-bold hover:underline ml-1 transition">
                Inicia sesión aquí
            </a>
        </p>
    </div>
</x-guest-layout>
