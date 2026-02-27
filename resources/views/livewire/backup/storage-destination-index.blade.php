<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('backup-storage-destination.title') }}</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('backup-storage-destination.subtitle') }}</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm gap-2 rounded-xl shadow-lg shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('backup-storage-destination.add') }}
        </button>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="alert bg-success/10 text-success border border-success/20 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
            <span class="text-sm">{{ session('message') }}</span>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-storage-destination.filter_type') }}</span></label>
                    <select wire:model.live="filterType" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-storage-destination.all') }}</option>
                        <option value="s3">S3</option>
                        <option value="ftp">FTP</option>
                        <option value="local">{{ __('backup-storage-destination.local') }}</option>
                    </select>
                </div>
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-storage-destination.filter_status') }}</span></label>
                    <select wire:model.live="filterStatus" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-storage-destination.all') }}</option>
                        <option value="active">{{ __('backup-storage-destination.active') }}</option>
                        <option value="inactive">{{ __('backup-storage-destination.inactive') }}</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    {{-- Table --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr class="border-b border-base-content/5">
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-storage-destination.col_name') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-storage-destination.col_type') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-storage-destination.col_details') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-storage-destination.col_status') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('backup-storage-destination.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($destinations as $destination)
                        @php $config = $destination->config ?? []; @endphp
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="dest-{{ $destination->id }}">
                            {{-- Name --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-base-content/5 flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" /></svg>
                                    </div>
                                    <span class="font-medium text-sm">{{ $destination->name }}</span>
                                </div>
                            </td>
                            {{-- Type Badge --}}
                            <td>
                                @if ($destination->type === 's3')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-info/10 text-info">S3</span>
                                @elseif ($destination->type === 'ftp')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-warning/10 text-warning">FTP</span>
                                @elseif ($destination->type === 'local')
                                    <span class="inline-flex px-2.5 py-0.5 rounded-md text-[10px] font-semibold bg-success/10 text-success">{{ __('backup-storage-destination.local') }}</span>
                                @endif
                            </td>
                            {{-- Details --}}
                            <td>
                                <div class="text-xs text-base-content/60">
                                    @if ($destination->type === 's3')
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-info/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                                            @if (!empty($config['endpoint']))
                                                <span class="text-info/70 font-mono text-[10px]">{{ $config['endpoint'] }}</span>
                                                <span class="text-base-content/30">&middot;</span>
                                            @endif
                                            <span class="text-info/70 font-medium">{{ $config['bucket'] ?? '' }}</span>
                                            <span class="text-base-content/30">&middot;</span>
                                            <span>{{ $config['region'] ?? '' }}</span>
                                        </div>
                                    @elseif ($destination->type === 'ftp')
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-warning/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                                            <span class="text-warning/70 font-medium">{{ $config['host'] ?? '' }}:{{ $config['port'] ?? 21 }}</span>
                                            <span class="text-base-content/30">&middot;</span>
                                            <span class="font-mono text-[10px]">{{ $config['root_path'] ?? '/' }}</span>
                                        </div>
                                    @elseif ($destination->type === 'local')
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-success/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                                            <span class="text-success/70 font-mono text-[10px]">{{ $config['path'] ?? '' }}</span>
                                        </div>
                                    @else
                                        <span class="text-base-content/30 italic">—</span>
                                    @endif
                                </div>
                            </td>
                            {{-- Status --}}
                            <td>
                                @if ($destination->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                        {{ __('backup-storage-destination.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                        {{ __('backup-storage-destination.inactive') }}
                                    </span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="testConnection({{ $destination->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-storage-destination.test') }}"
                                        wire:loading.attr="disabled" wire:target="testConnection({{ $destination->id }})">
                                        <span wire:loading wire:target="testConnection({{ $destination->id }})" class="loading loading-spinner loading-xs"></span>
                                        <span wire:loading.remove wire:target="testConnection({{ $destination->id }})">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                                        </span>
                                    </button>
                                    <button wire:click="openEdit({{ $destination->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-storage-destination.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button wire:click="delete({{ $destination->id }})" wire:confirm="{{ __('backup-storage-destination.confirm_delete') }}" class="btn btn-ghost btn-xs btn-square rounded-lg text-error/70 hover:text-error hover:bg-error/10 tooltip tooltip-left" data-tip="{{ __('backup-storage-destination.delete') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('backup-storage-destination.empty') }}</p>
                                    <button wire:click="openCreate" class="btn btn-primary btn-sm mt-4 rounded-xl">{{ __('backup-storage-destination.add') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        @if ($destinations->hasPages())
            <div class="px-6 py-4 border-t border-base-content/5">
                {{ $destinations->links() }}
            </div>
        @endif
    </div>

    {{-- Test Connection Toast --}}
    @if ($testResult === 'success')
        <div class="toast toast-end toast-bottom z-50">
            <div class="alert bg-success/10 text-success border border-success/20 rounded-xl shadow-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                <span class="text-sm font-medium">{{ __('backup-storage-destination.test_success') }}</span>
            </div>
        </div>
    @elseif ($testResult === 'failed')
        <div class="toast toast-end toast-bottom z-50">
            <div class="alert bg-error/10 text-error border border-error/20 rounded-xl shadow-xl">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                <span class="text-sm font-medium">{{ __('backup-storage-destination.test_failed') }}</span>
            </div>
        </div>
    @endif

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">
                        {{ $editId ? __('backup-storage-destination.edit_title') : __('backup-storage-destination.create_title') }}
                    </h3>
                    <button wire:click="closeForm" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5 overflow-y-auto max-h-[calc(85vh-5rem)]">
                    <livewire:backup.storage-destination-form :destinationId="$editId" :key="'form-'.($editId ?? 'new')" />
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeForm"></div>
        </div>
    @endif
</div>
