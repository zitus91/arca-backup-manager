<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('backup-source.title') }}</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('backup-source.subtitle') }}</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm gap-2 rounded-xl shadow-lg shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('backup-source.add') }}
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
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.filter_type') }}</span></label>
                    <select wire:model.live="filterType" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-source.all') }}</option>
                        <option value="mysql">MySQL</option>
                        <option value="mongodb">MongoDB</option>
                        <option value="filesystem">Filesystem</option>
                    </select>
                </div>
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.filter_status') }}</span></label>
                    <select wire:model.live="filterStatus" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-source.all') }}</option>
                        <option value="active">{{ __('backup-source.active') }}</option>
                        <option value="inactive">{{ __('backup-source.inactive') }}</option>
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
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-source.col_name') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-source.col_sources') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-source.col_ssh') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-source.col_details') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-source.col_status') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('backup-source.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($sources as $source)
                        @php
                            $config = $source->config ?? [];
                            $types = $source->enabled_types;
                        @endphp
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="source-{{ $source->id }}">
                            {{-- Name --}}
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-base-content/5 flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/40" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5l-2.25-1.313M21 7.5v2.25m0-2.25l-2.25 1.313M3 7.5l2.25-1.313M3 7.5l2.25 1.313M3 7.5v2.25m9 3l2.25-1.313M12 12.75l-2.25-1.313M12 12.75V15m0 6.75l2.25-1.313M12 21.75V19.5m0 2.25l-2.25-1.313m0-16.875L12 2.25l2.25 1.313M21 14.25v2.25l-2.25 1.313m-13.5 0L3 16.5v-2.25" /></svg>
                                    </div>
                                    <span class="font-medium text-sm">{{ $source->name }}</span>
                                </div>
                            </td>
                            {{-- Sources Badges --}}
                            <td>
                                <div class="flex flex-wrap gap-1.5">
                                    @if ($source->hasType('mysql'))
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-primary/10 text-primary">MySQL</span>
                                    @endif
                                    @if ($source->hasType('mongodb'))
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-success/10 text-success">MongoDB</span>
                                    @endif
                                    @if ($source->hasType('filesystem'))
                                        <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold bg-warning/10 text-warning">Filesystem</span>
                                    @endif
                                </div>
                            </td>
                            {{-- SSH --}}
                            <td>
                                @if ($source->host)
                                    <div class="flex items-center gap-1.5">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-secondary flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                        <div>
                                            <div class="text-xs font-semibold text-secondary">{{ $source->host->name }}</div>
                                            <div class="text-[10px] text-base-content/40 font-mono">{{ $source->host->config['user'] }}&#64;{{ $source->host->config['host'] }}:{{ $source->host->config['port'] }}</div>
                                        </div>
                                    </div>
                                @else
                                    <span class="text-[10px] text-base-content/20 italic">—</span>
                                @endif
                            </td>
                            {{-- Details --}}
                            <td>
                                <div class="space-y-1 text-xs text-base-content/60">
                                    @if (isset($config['mysql']))
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-primary/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                            <span class="text-primary/70 font-medium">{{ $config['mysql']['host'] ?? '' }}:{{ $config['mysql']['port'] ?? 3306 }}</span>
                                            <span class="text-base-content/30">&middot;</span>
                                            <span>{{ count($config['mysql']['databases'] ?? []) }} db</span>
                                        </div>
                                    @endif
                                    @if (isset($config['mongodb']))
                                        <div class="flex items-center gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-success/60" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                            <span class="text-success/70 font-medium">{{ $config['mongodb']['host'] ?? '' }}:{{ $config['mongodb']['port'] ?? 27017 }}</span>
                                            <span class="text-base-content/30">&middot;</span>
                                            <span>{{ count($config['mongodb']['databases'] ?? []) }} db</span>
                                        </div>
                                    @endif
                                    @if (isset($config['filesystem']))
                                        @php
                                            $fsPaths = $config['filesystem']['paths'] ?? ($config['filesystem']['path'] ? [$config['filesystem']['path']] : []);
                                        @endphp
                                        <div class="flex items-start gap-1.5">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-warning/60 mt-0.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                                            <div class="flex flex-col">
                                                @foreach ($fsPaths as $fp)
                                                    <span class="text-warning/70 font-mono text-[10px]">{{ $fp }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endif
                                    @if (empty($types))
                                        <span class="text-base-content/30 italic">—</span>
                                    @endif
                                </div>
                            </td>
                            {{-- Status --}}
                            <td>
                                @if ($source->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                        {{ __('backup-source.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                        {{ __('backup-source.inactive') }}
                                    </span>
                                @endif
                            </td>
                            {{-- Actions --}}
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    <button wire:click="openEdit({{ $source->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-source.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $source->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg text-error/70 hover:text-error hover:bg-error/10 tooltip tooltip-left" data-tip="{{ __('backup-source.delete') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('backup-source.empty') }}</p>
                                    <button wire:click="openCreate" class="btn btn-primary btn-sm mt-4 rounded-xl">{{ __('backup-source.add') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">
                        {{ $editId ? __('backup-source.edit_title') : __('backup-source.create_title') }}
                    </h3>
                    <button wire:click="closeForm" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5 overflow-y-auto max-h-[calc(85vh-5rem)]">
                    <livewire:backup.backup-source-form :sourceId="$editId" :key="'form-'.($editId ?? 'new')" />
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeForm"></div>
        </div>
    @endif

    {{-- Confirm Delete Modal --}}
    <x-confirm-modal
        :show="$confirmingDeleteId !== null"
        :message="__('backup-source.confirm_delete')"
    />
</div>
