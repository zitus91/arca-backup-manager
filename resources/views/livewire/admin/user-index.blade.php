<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('users.title') }}</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('users.subtitle') }}</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm gap-2 rounded-xl shadow-lg shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('users.add') }}
        </button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <div class="alert bg-success/10 text-success border border-success/20 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="text-sm">{{ session('message') }}</span>
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert bg-error/10 text-error border border-error/20 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
            <span class="text-sm">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Search --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-4">
            <div class="form-control w-full sm:w-72">
                <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('users.search') }}</span></label>
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="text" wire:model.live.debounce.300ms="search" placeholder="{{ __('users.search_placeholder') }}" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary pl-9 w-full" />
                </div>
            </div>
        </div>
    </div>

    {{-- Users Table --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr class="border-b border-base-content/5">
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('users.col_user') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('users.col_email') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('users.col_created') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('users.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($users as $user)
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="user-{{ $user->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <span class="text-xs font-bold text-primary">{{ strtoupper(substr($user->name, 0, 2)) }}</span>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium">{{ $user->name }}</p>
                                        @if ($user->id === auth()->id())
                                            <span class="text-[10px] font-semibold text-primary bg-primary/10 px-1.5 py-0.5 rounded">{{ __('users.you') }}</span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm text-base-content/60">{{ $user->email }}</td>
                            <td class="text-xs text-base-content/40 tabular-nums">{{ $user->created_at->format('d/m/Y H:i') }}</td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEdit({{ $user->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('users.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" /></svg>
                                    </button>
                                    @if ($user->id !== auth()->id())
                                        <button wire:click="delete({{ $user->id }})" wire:confirm="{{ __('users.confirm_delete') }}" class="btn btn-ghost btn-xs btn-square rounded-lg text-error/70 hover:text-error hover:bg-error/10 tooltip tooltip-left" data-tip="{{ __('users.delete') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('users.empty') }}</p>
                                    <button wire:click="openCreate" class="btn btn-primary btn-sm mt-4 rounded-xl">{{ __('users.add') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="px-6 py-4 border-t border-base-content/5">
                {{ $users->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="modal modal-open">
            <div class="modal-box max-w-lg bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">
                        {{ $editId ? __('users.edit_title') : __('users.create_title') }}
                    </h3>
                    <button wire:click="closeForm" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <livewire:admin.user-form :userId="$editId" :key="'form-'.($editId ?? 'new')" />
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeForm"></div>
        </div>
    @endif
</div>
