<section class="space-y-6">
    <header class="mb-6">
        <h2 class="text-xl font-bold text-slate-800 tracking-tight">
            Información del Perfil
        </h2>
        <p class="text-xs text-slate-500 mt-1">
            Actualiza los datos personales y de contacto asociados a tu cuenta de investigador.
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="space-y-5">
        @csrf
        @method('patch')

        <!-- Nombre Completo -->
        <div>
            <label for="name" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Nombre Completo</label>
            <input id="name" 
                   name="name" 
                   type="text" 
                   value="{{ old('name', $user->name) }}" 
                   required 
                   autofocus 
                   autocomplete="name" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" />
            <x-input-error class="mt-1.5 text-xs text-rose-600" :messages="$errors->get('name')" />
        </div>

        <!-- Cédula de Identidad (Solo Lectura / Auditoría) -->
        <div>
            <label for="cedula_display" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Cédula de Identidad (No modificable)</label>
            <div class="relative">
                <input id="cedula_display" 
                       type="text" 
                       value="{{ $user->cedula }}" 
                       disabled 
                       class="block w-full rounded-xl border-slate-200/50 bg-slate-100 text-slate-500 text-sm py-2.5 px-4 cursor-not-allowed font-semibold" />
                <span class="absolute right-4 top-3 text-slate-400">
                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path>
                    </svg>
                </span>
            </div>
            <p class="text-[10px] text-slate-400 mt-1 font-medium">Por motivos de auditoría académica, la cédula solo puede ser modificada por administración.</p>
        </div>

        <!-- Correo Electrónico -->
        <div>
            <label for="email" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Correo Institucional</label>
            <input id="email" 
                   name="email" 
                   type="email" 
                   value="{{ old('email', $user->email) }}" 
                   required 
                   autocomplete="username" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" />
            <x-input-error class="mt-1.5 text-xs text-rose-600" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-3 p-3 bg-amber-50 border border-amber-200/60 rounded-xl">
                    <p class="text-xs text-amber-800 font-medium">
                        Tu dirección de correo no está verificada.
                        <button form="send-verification" class="text-unimar-blue hover:underline font-bold ml-1">
                            Haz clic aquí para reenviar el correo de verificación.
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="mt-2 text-xs font-bold text-emerald-600">
                            Se ha enviado un nuevo enlace de verificación a tu correo.
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <!-- Teléfono de Contacto -->
        <div>
            <label for="telefono" class="block text-xs font-bold text-slate-600 uppercase tracking-wider mb-1.5">Teléfono de Contacto</label>
            <input id="telefono" 
                   name="telefono" 
                   type="tel" 
                   value="{{ old('telefono', $user->telefono) }}" 
                   required 
                   placeholder="+584141234567" 
                   class="block w-full rounded-xl border-slate-200/80 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-sm py-2.5 px-4 focus:border-unimar-blue focus:ring focus:ring-unimar-blue/10 transition-all duration-150" />
            <x-input-error class="mt-1.5 text-xs text-rose-600" :messages="$errors->get('telefono')" />
        </div>

        <!-- Botón de Guardado e Indicador -->
        <div class="flex items-center space-x-4 pt-4">
            <button type="submit" class="py-2.5 px-6 bg-unimar-blue hover:bg-unimar-blue/95 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-unimar-blue text-sm">
                Guardar Cambios
            </button>

            @if (session('status') === 'profile-updated')
                <div x-data="{ show: true }"
                     x-show="show"
                     x-transition:leave="transition ease-in duration-500"
                     x-transition:leave-start="opacity-100"
                     x-transition:leave-end="opacity-0"
                     x-init="setTimeout(() => show = false, 3000)"
                     class="flex items-center space-x-1.5 text-emerald-600">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    <span class="text-xs font-bold">Cambios guardados con éxito.</span>
                </div>
            @endif
        </div>
    </form>
</section>
