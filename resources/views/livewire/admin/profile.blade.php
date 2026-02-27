<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('users.profile_title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('users.profile_subtitle') }}</p>
    </div>

    {{-- Profile Card --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-6">
            {{-- Header with avatar --}}
            <div class="flex items-center gap-5 pb-6 border-b border-base-content/5">
                <div class="w-16 h-16 rounded-2xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-lg shadow-primary/20 flex-shrink-0">
                    <span class="text-2xl font-bold text-primary-content">{{ strtoupper(substr($name, 0, 2)) }}</span>
                </div>
                <div>
                    <h2 class="text-lg font-semibold">{{ __('users.profile_info') }}</h2>
                    <p class="text-sm text-base-content/40 mt-0.5">{{ __('users.profile_info_desc') }}</p>
                </div>
            </div>

            @if ($profileSaved)
                <div class="alert bg-success/10 text-success border border-success/20 rounded-xl mt-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-sm">{{ __('users.profile_saved') }}</span>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 pt-5">
                <div class="form-control">
                    <label class="label pb-1.5">
                        <span class="label-text text-xs font-semibold text-base-content/50 uppercase tracking-wider">{{ __('users.name') }}</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" /></svg>
                        </span>
                        <input type="text" wire:model="name" class="input input-bordered w-full rounded-xl bg-base-200/30 border-base-content/10 focus:border-primary focus:bg-base-100 transition-colors pl-10" />
                    </div>
                    @error('name') <p class="text-error text-xs mt-1.5 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-1.5">
                        <span class="label-text text-xs font-semibold text-base-content/50 uppercase tracking-wider">{{ __('users.email') }}</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" /></svg>
                        </span>
                        <input type="email" wire:model="email" class="input input-bordered w-full rounded-xl bg-base-200/30 border-base-content/10 focus:border-primary focus:bg-base-100 transition-colors pl-10" />
                    </div>
                    @error('email') <p class="text-error text-xs mt-1.5 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-2 border-t border-base-content/5">
                <button wire:click="updateProfile" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-xl px-6 gap-2 shadow-lg shadow-primary/20">
                    <span wire:loading.remove wire:target="updateProfile">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                    </span>
                    <span wire:loading wire:target="updateProfile" class="loading loading-spinner loading-xs"></span>
                    {{ __('users.save_profile') }}
                </button>
            </div>
        </div>
    </div>

    {{-- Change Password Card --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-6">
            {{-- Header --}}
            <div class="flex items-center gap-5 pb-6 border-b border-base-content/5">
                <div class="w-16 h-16 rounded-2xl bg-warning/10 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                    </svg>
                </div>
                <div>
                    <h2 class="text-lg font-semibold">{{ __('users.change_password') }}</h2>
                    <p class="text-sm text-base-content/40 mt-0.5">{{ __('users.change_password_desc') }}</p>
                </div>
            </div>

            @if ($passwordChanged)
                <div class="alert bg-success/10 text-success border border-success/20 rounded-xl mt-5">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <span class="text-sm">{{ __('users.password_changed') }}</span>
                </div>
            @endif

            <div class="space-y-5 pt-5">
                {{-- Current Password --}}
                <div class="form-control max-w-md">
                    <label class="label pb-1.5">
                        <span class="label-text text-xs font-semibold text-base-content/50 uppercase tracking-wider">{{ __('users.current_password') }}</span>
                    </label>
                    <div class="relative">
                        <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/30">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" /></svg>
                        </span>
                        <input type="password" wire:model="current_password" class="input input-bordered w-full rounded-xl bg-base-200/30 border-base-content/10 focus:border-primary focus:bg-base-100 transition-colors pl-10" placeholder="{{ __('users.current_password_placeholder') }}" />
                    </div>
                    @error('current_password') <p class="text-error text-xs mt-1.5 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                </div>

                {{-- New Password row --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                    <div class="form-control">
                        <label class="label pb-1.5">
                            <span class="label-text text-xs font-semibold text-base-content/50 uppercase tracking-wider">{{ __('users.new_password') }}</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                            </span>
                            <input type="password" wire:model="new_password" class="input input-bordered w-full rounded-xl bg-base-200/30 border-base-content/10 focus:border-primary focus:bg-base-100 transition-colors pl-10" placeholder="{{ __('users.new_password_placeholder') }}" />
                        </div>
                        @error('new_password') <p class="text-error text-xs mt-1.5 flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd" /></svg>{{ $message }}</p> @enderror
                    </div>

                    <div class="form-control">
                        <label class="label pb-1.5">
                            <span class="label-text text-xs font-semibold text-base-content/50 uppercase tracking-wider">{{ __('users.confirm_new_password') }}</span>
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-base-content/30">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" /></svg>
                            </span>
                            <input type="password" wire:model="new_password_confirmation" class="input input-bordered w-full rounded-xl bg-base-200/30 border-base-content/10 focus:border-primary focus:bg-base-100 transition-colors pl-10" placeholder="{{ __('users.confirm_new_password_placeholder') }}" />
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex justify-end pt-4 mt-2 border-t border-base-content/5">
                <button wire:click="updatePassword" wire:loading.attr="disabled" class="btn btn-warning btn-sm rounded-xl px-6 gap-2">
                    <span wire:loading.remove wire:target="updatePassword">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" /></svg>
                    </span>
                    <span wire:loading wire:target="updatePassword" class="loading loading-spinner loading-xs"></span>
                    {{ __('users.update_password') }}
                </button>
            </div>
        </div>
    </div>
</div>
