<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('backup-log.title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('backup-log.subtitle') }}</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-log.filter_job') }}</span></label>
                    <select wire:model.live="filterJobId" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-log.all') }}</option>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}">{{ $job->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-log.filter_status') }}</span></label>
                    <select wire:model.live="filterStatus" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-log.all') }}</option>
                        <option value="pending">{{ __('backup-log.status_pending') }}</option>
                        <option value="running">{{ __('backup-log.status_running') }}</option>
                        <option value="success">{{ __('backup-log.status_success') }}</option>
                        <option value="failed">{{ __('backup-log.status_failed') }}</option>
                        <option value="partial">{{ __('backup-log.status_partial') }}</option>
                    </select>
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-log.filter_date_from') }}</span></label>
                    <input type="date" wire:model.live="filterDateFrom" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" />
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-log.filter_date_to') }}</span></label>
                    <input type="date" wire:model.live="filterDateTo" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" />
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
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_job') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_source') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_destination') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_status') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_started') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_duration') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-log.col_size') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('backup-log.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="log-{{ $log->id }}">
                            <td>
                                <span class="font-medium text-sm">{{ $log->job->name ?? '-' }}</span>
                            </td>
                            <td class="text-sm text-base-content/70">{{ $log->job->source->name ?? '-' }}</td>
                            <td class="text-sm text-base-content/70">{{ $log->job->destination->name ?? '-' }}</td>
                            <td>
                                @switch($log->status)
                                    @case('running')
                                        <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                            <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                            {{ __('backup-log.status_running') }}
                                        </span>
                                        @break
                                    @case('success')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                            <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                            {{ __('backup-log.status_success') }}
                                        </span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error">
                                            <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                            {{ __('backup-log.status_failed') }}
                                        </span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">
                                            <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                            {{ __('backup-log.status_pending') }}
                                        </span>
                                        @break
                                    @case('partial')
                                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                            <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                            {{ __('backup-log.status_partial') }}
                                        </span>
                                        @break
                                @endswitch
                            </td>
                            <td class="text-xs text-base-content/60 tabular-nums">{{ $log->started_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-xs text-base-content/60 tabular-nums">{{ $log->formatted_duration }}</td>
                            <td class="text-xs text-base-content/60 tabular-nums">{{ $log->formatted_size }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    @if ($log->status === 'success' && $log->storage_path)
                                        <a href="{{ route('backup.logs.download', $log) }}" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-log.download') }}" target="_blank">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                        </a>
                                    @endif
                                    <button wire:click="openDetail({{ $log->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-log.detail') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('backup-log.empty') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($logs->hasPages())
            <div class="px-6 py-4 border-t border-base-content/5">
                {{ $logs->links() }}
            </div>
        @endif
    </div>

    {{-- Detail Modal --}}
    @if ($showDetail && $detailLog)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">{{ __('backup-log.detail_title') }}</h3>
                    <button wire:click="closeDetail" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                {{-- Modal Body --}}
                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_job') }}</p>
                            <p class="text-sm font-semibold">{{ $detailLog->job->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_status') }}</p>
                            @switch($detailLog->status)
                                @case('success')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                        {{ __('backup-log.status_success') }}
                                    </span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error">
                                        <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                        {{ __('backup-log.status_failed') }}
                                    </span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                        <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                        {{ __('backup-log.status_running') }}
                                    </span>
                                    @break
                                @case('pending')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">
                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                        {{ __('backup-log.status_pending') }}
                                    </span>
                                    @break
                                @case('partial')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                        <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                        {{ __('backup-log.status_partial') }}
                                    </span>
                                    @break
                            @endswitch
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_started') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->started_at->format('d/m/Y H:i:s') }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.detail_finished') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_duration') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->formatted_duration }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_size') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->formatted_size }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.detail_file') }}</p>
                            <p class="text-sm">{{ $detailLog->file_name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.detail_path') }}</p>
                            <p class="text-sm break-all">{{ $detailLog->storage_path ?? '-' }}</p>
                        </div>
                    </div>

                    @if ($detailLog->error_message)
                        <div class="rounded-xl border border-error/20 bg-error/[0.03] p-4">
                            <p class="text-xs font-medium text-error uppercase tracking-wider mb-2">{{ __('backup-log.detail_error') }}</p>
                            <p class="text-sm text-error/80">{{ $detailLog->error_message }}</p>
                        </div>
                    @endif

                    @if ($detailLog->meta)
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-2">{{ __('backup-log.detail_meta') }}</p>
                            <pre class="bg-base-200/50 border border-base-content/5 p-4 rounded-xl text-xs overflow-x-auto font-mono">{{ json_encode($detailLog->meta, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>

                {{-- Modal Footer --}}
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                    @if ($detailLog->status === 'success' && $detailLog->storage_path)
                        <a href="{{ route('backup.logs.download', $detailLog) }}" class="btn btn-primary btn-sm rounded-xl gap-2" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('backup-log.download') }}
                        </a>
                    @endif
                    <button wire:click="closeDetail" class="btn btn-sm rounded-xl">{{ __('backup-log.close') }}</button>
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeDetail"></div>
        </div>
    @endif
</div>
