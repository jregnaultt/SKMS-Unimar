<section class="space-y-4">
    <header class="mb-4">
        <h2 class="text-2xl font-bold text-rose-800 tracking-tight flex items-center space-x-2.5">
            <svg class="w-6 h-6 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
            </svg>
            <span>Eliminar Cuenta</span>
        </h2>
        <p class="text-base text-rose-650 mt-1.5 font-medium leading-relaxed">
            Una vez que se elimine tu cuenta, todos sus recursos y datos se borrarán de forma permanente. Por favor, descarga cualquier información que desees conservar antes de proceder.
        </p>
    </header>

    <div class="pt-2">
        <button type="button" 
                x-data="" 
                x-on:click.prevent="$dispatch('open-modal', 'confirm-user-deletion')" 
                class="py-3 px-8 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 text-base flex items-center space-x-2.5">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
            </svg>
            <span>Eliminar mi cuenta definitivamente</span>
        </button>
    </div>

    <x-modal name="confirm-user-deletion" :show="$errors->userDeletion->isNotEmpty()" focusable>
        <form method="post" action="{{ route('profile.destroy') }}" class="p-8 sm:p-10 space-y-6">
            @csrf
            @method('delete')

            <div>
                <h3 class="text-2xl font-bold text-slate-800 flex items-center space-x-2.5">
                    <svg class="w-6 h-6 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>¿Estás seguro de que deseas eliminar tu cuenta?</span>
                </h3>
                <p class="text-base text-slate-500 mt-2.5 leading-relaxed font-medium">
                    Esta acción no se puede deshacer. Por favor, introduce tu contraseña para confirmar que deseas eliminar de forma permanente tu cuenta de investigador y todos tus datos asociados.
                </p>
            </div>

            <div>
                <label for="delete_password" class="block text-base font-bold text-slate-655 uppercase tracking-wider mb-2">Contraseña de Confirmación</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-slate-400">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <input id="delete_password" 
                           name="password" 
                           type="password" 
                           required 
                           placeholder="Introduce tu contraseña actual" 
                           class="block w-full md:w-3/4 rounded-xl border-slate-200 bg-slate-50/50 text-slate-700 placeholder-slate-400 text-base py-3 pl-12 pr-4 focus:border-rose-500 focus:ring focus:ring-rose-500/10 transition-all duration-150 h-12" />
                </div>
                <x-input-error :messages="$errors->userDeletion->get('password')" class="mt-1.5 text-sm text-rose-600" />
            </div>

            <div class="flex items-center justify-end space-x-4 pt-6 border-t border-slate-100">
                <button type="button" 
                        x-on:click="$dispatch('close')" 
                        class="py-3 px-8 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-xl border border-slate-200 transition-all duration-150 text-base focus:outline-none h-12">
                    Cancelar
                </button>

                <button type="submit" 
                        class="py-3 px-8 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-xl transition-all duration-150 shadow-sm hover:shadow-md text-base focus:outline-none h-12 flex items-center space-x-2.5">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                    </svg>
                    <span>Confirmar Eliminación</span>
                </button>
            </div>
        </form>
    </x-modal>
</section>
