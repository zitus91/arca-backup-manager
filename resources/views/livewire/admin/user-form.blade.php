<div class="space-y-5">
    {{-- Name --}}
    <div class="form-control">
        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('users.name') }}</span></label>
        <input type="text" wire:model="name" class="input input-bordered rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('users.name_placeholder') }}" />
        @error('name') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Email --}}
    <div class="form-control">
        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('users.email') }}</span></label>
        <input type="email" wire:model="email" class="input input-bordered rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('users.email_placeholder') }}" />
        @error('email') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Password --}}
    <div class="form-control">
        <label class="label pb-1">
            <span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('users.password') }}</span>
            @if ($userId)
                <span class="label-text-alt text-[10px] text-base-content/30">{{ __('users.password_optional') }}</span>
            @endif
        </label>
        <input type="password" wire:model="password" class="input input-bordered rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ $userId ? __('users.password_leave_blank') : __('users.password_placeholder') }}" />
        @error('password') <p class="text-error text-xs mt-1">{{ $message }}</p> @enderror
    </div>

    {{-- Password Confirmation --}}
    <div class="form-control">
        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('users.password_confirm') }}</span></label>
        <input type="password" wire:model="password_confirmation" class="input input-bordered rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('users.password_confirm_placeholder') }}" />
    </div>

    {{-- Submit --}}
    <div class="flex justify-end pt-2">
        <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary btn-sm rounded-xl px-6 gap-2 shadow-lg shadow-primary/20">
            <span wire:loading.remove wire:target="save">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
            </span>
            <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
            {{ $userId ? __('users.update') : __('users.create') }}
        </button>
    </div>
</div>
