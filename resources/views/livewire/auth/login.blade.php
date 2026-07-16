<div class="min-h-screen bg-base-300 flex items-center justify-center p-4 font-[Inter]">
    <div class="w-full max-w-sm">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <img src="{{ asset('/images/logo.svg') }}" alt="Arca Logo" class="w-16 h-16 mx-auto rounded-2xl shadow-lg shadow-primary/20 mb-4" />
            <h1 class="text-2xl font-bold tracking-tight">Arca</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('auth.login_subtitle') }}</p>
        </div>

        {{-- Login Card --}}
        <div class="card bg-base-100 border border-base-content/5 shadow-xl">
            <div class="card-body p-6">
                {{-- Messaggio di errore generale --}}
                @if ($errorMessage)
                    <div class="alert alert-error rounded-xl text-sm mb-2 shadow">
                        <svg xmlns="http://www.w3.org/2000/svg" class="stroke-current shrink-0 h-5 w-5" fill="none" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        <span>{{ $errorMessage }}</span>
                    </div>
                @endif

                <form wire:submit="login" class="space-y-4">
                    {{-- Email --}}
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('auth.email') }}</span>
                        </label>
                        <input type="email" wire:model="email" class="input input-bordered w-full rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="admin@example.com" autofocus />
                        @error('email')
                            <label class="label pb-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                        @enderror
                    </div>

                    {{-- Password --}}
                    <div class="form-control">
                        <label class="label pb-1">
                            <span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('auth.password') }}</span>
                        </label>
                        <input type="password" wire:model="password" class="input input-bordered w-full rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="••••••••" />
                        @error('password')
                            <label class="label pb-0"><span class="label-text-alt text-error">{{ $message }}</span></label>
                        @enderror
                    </div>

                    {{-- Remember --}}
                    <div class="form-control">
                        <label class="label cursor-pointer justify-start gap-3">
                            <input type="checkbox" wire:model="remember" class="checkbox checkbox-primary checkbox-sm rounded-lg" />
                            <span class="label-text text-sm">{{ __('auth.remember') }}</span>
                        </label>
                    </div>

                    {{-- Submit --}}
                    <button type="submit" class="btn btn-primary w-full rounded-xl" wire:loading.attr="disabled">
                        <span wire:loading.remove>{{ __('auth.login') }}</span>
                        <span wire:loading class="loading loading-spinner loading-sm"></span>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
