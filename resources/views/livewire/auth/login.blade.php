<div class="min-h-screen bg-base-300 flex items-center justify-center p-4 font-[Inter]">
    <div class="w-full max-w-sm">
        {{-- Logo --}}
        <div class="text-center mb-8">
            <div class="w-16 h-16 mx-auto rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-lg shadow-primary/20 mb-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                </svg>
            </div>
            <h1 class="text-2xl font-bold tracking-tight">Backup Manager</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('auth.login_subtitle') }}</p>
        </div>

        {{-- Login Card --}}
        <div class="card bg-base-100 border border-base-content/5 shadow-xl">
            <div class="card-body p-6">
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
