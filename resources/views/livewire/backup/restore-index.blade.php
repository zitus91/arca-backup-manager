<div class="space-y-6" wire:poll.15s>
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('restore.title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('restore.subtitle') }}</p>
    </div>

    {{-- Restore History --}}
    @if ($restoreLogs->isNotEmpty())
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4">
                <h2 class="text-sm font-semibold text-base-content/70 uppercase tracking-wider mb-3">{{ __('restore.history') }}</h2>
                <div class="overflow-x-auto">
                    <table class="table table-sm">
                        <thead>
                            <tr class="border-b border-base-content/5">
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_backup') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_type') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_status') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_restored_to') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_started') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_duration') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_user') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('restore.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($restoreLogs as $rLog)
                                <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="rlog-{{ $rLog->id }}">
                                    <td class="text-sm font-medium">{{ $rLog->backupLog->job->name ?? '-' }}</td>
                                    <td>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium
                                            {{ $rLog->restore_type === 'full' ? 'bg-primary/10 text-primary' : ($rLog->restore_type === 'db_only' ? 'bg-info/10 text-info' : 'bg-accent/10 text-accent') }}">
                                            {{ __('restore.type_' . $rLog->restore_type) }}
                                        </span>
                                    </td>
                                    <td>
                                        @switch($rLog->status)
                                            @case('running')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                                    <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                                    {{ __('restore.status_running') }}
                                                </span>
                                                @break
                                            @case('success')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                                    {{ __('restore.status_success') }}
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                    {{ __('restore.status_failed') }}
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                                    {{ __('restore.status_pending') }}
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-xs text-base-content/60">
                                        @if ($rLog->restored_db_name)
                                            <span class="font-mono">{{ $rLog->restored_db_name }}</span>
                                        @endif
                                        @if ($rLog->restored_path)
                                            <span class="font-mono">{{ $rLog->restored_path }}</span>
                                        @endif
                                        @if (! $rLog->restored_db_name && ! $rLog->restored_path)
                                            -
                                        @endif
                                    </td>
                                    <td class="text-xs text-base-content/60 tabular-nums">{{ $rLog->created_at->format('d/m/Y H:i:s') }}</td>
                                    <td class="text-xs text-base-content/60 tabular-nums">{{ $rLog->formatted_duration }}</td>
                                    <td class="text-xs text-base-content/60">{{ $rLog->user->name ?? '-' }}</td>
                                    <td>
                                        <div class="flex justify-end">
                                            <button wire:click="openDetail({{ $rLog->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('restore.detail') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    @endif

    {{-- Filters --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-4">
            <h2 class="text-sm font-semibold text-base-content/70 uppercase tracking-wider mb-3">{{ __('restore.available_backups') }}</h2>
            <div class="flex flex-wrap items-end gap-4">
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('restore.filter_job') }}</span></label>
                    <select wire:model.live="filterJobId" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('restore.all') }}</option>
                        @foreach ($jobs as $job)
                            <option value="{{ $job->id }}">{{ $job->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('restore.filter_date_from') }}</span></label>
                    <input type="date" wire:model.live="filterDateFrom" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" />
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('restore.filter_date_to') }}</span></label>
                    <input type="date" wire:model.live="filterDateTo" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" />
                </div>
            </div>
        </div>
    </div>

    {{-- Available Backups Table --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="table">
                <thead>
                    <tr class="border-b border-base-content/5">
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_job') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_source') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_destination') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_types') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_date') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_file') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('restore.col_size') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('restore.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($backups as $backup)
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="backup-{{ $backup->id }}">
                            <td>
                                <span class="font-medium text-sm">{{ $backup->job->name ?? '-' }}</span>
                            </td>
                            <td class="text-sm text-base-content/70">{{ $backup->job->source->name ?? '-' }}</td>
                            <td class="text-sm text-base-content/70">
                                <span class="inline-flex items-center gap-1">
                                    {{ $backup->job->destination->name ?? '-' }}
                                    <span class="text-[10px] opacity-50">({{ $backup->job->destination->type ?? '' }})</span>
                                </span>
                            </td>
                            <td>
                                <div class="flex flex-wrap gap-1">
                                    @php
                                        $sourceConfig = $backup->job->source->config ?? [];
                                        $types = array_intersect(array_keys($sourceConfig), ['mysql', 'mongodb', 'filesystem']);
                                    @endphp
                                    @foreach ($types as $type)
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase
                                            {{ $type === 'mysql' ? 'bg-blue-500/10 text-blue-400' : ($type === 'mongodb' ? 'bg-green-500/10 text-green-400' : 'bg-orange-500/10 text-orange-400') }}">
                                            {{ $type }}
                                        </span>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-xs text-base-content/60 tabular-nums">{{ $backup->started_at->format('d/m/Y H:i') }}</td>
                            <td class="text-xs text-base-content/60 font-mono">{{ \Illuminate\Support\Str::limit($backup->file_name, 30) }}</td>
                            <td class="text-xs text-base-content/60 tabular-nums">{{ $backup->formatted_size }}</td>
                            <td>
                                <div class="flex justify-end">
                                    <button wire:click="openRestoreModal({{ $backup->id }})" class="btn btn-ghost btn-xs rounded-lg gap-1 text-primary hover:bg-primary/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                                        </svg>
                                        {{ __('restore.restore_btn') }}
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('restore.no_backups') }}</p>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($backups->hasPages())
            <div class="px-6 py-4 border-t border-base-content/5">
                {{ $backups->links() }}
            </div>
        @endif
    </div>

    {{-- Restore Modal --}}
    @if ($showRestoreModal && $selectedBackupLogId)
        <div class="modal modal-open">
            <div class="modal-box max-w-2xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                {{-- Modal Header --}}
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                            </svg>
                        </div>
                        <h3 class="font-bold text-lg">{{ __('restore.modal_title') }}</h3>
                    </div>
                    <button wire:click="closeRestoreModal" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                @if (! $showConfirmation)
                    {{-- Step 1: Select restore type --}}
                    <div class="px-6 py-5 space-y-5">
                        {{-- Backup Info --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_job') }}</p>
                                <p class="text-sm font-semibold">{{ $selectedBackupInfo['job_name'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_source') }}</p>
                                <p class="text-sm">{{ $selectedBackupInfo['source_name'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_date') }}</p>
                                <p class="text-sm tabular-nums">{{ $selectedBackupInfo['backup_date'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_size') }}</p>
                                <p class="text-sm tabular-nums">{{ $selectedBackupInfo['file_size'] ?? '-' }}</p>
                            </div>
                        </div>

                        {{-- Backup Contents --}}
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-2">{{ __('restore.backup_contains') }}</p>
                            <div class="space-y-2">
                                @if ($selectedBackupInfo['has_mysql'] ?? false)
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-blue-500/10 text-blue-400">MySQL</span>
                                        <span class="text-base-content/70">{{ implode(', ', $selectedBackupInfo['mysql_databases'] ?? []) }}</span>
                                        <span class="text-base-content/40 text-xs ml-auto">→ {{ implode(', ', array_map(fn($db) => $db . '_restored_' . now()->format('Ymd_His'), $selectedBackupInfo['mysql_databases'] ?? [])) }}</span>
                                    </div>
                                @endif
                                @if ($selectedBackupInfo['has_mongodb'] ?? false)
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-green-500/10 text-green-400">MongoDB</span>
                                        <span class="text-base-content/70">{{ implode(', ', $selectedBackupInfo['mongodb_databases'] ?? []) }}</span>
                                        <span class="text-base-content/40 text-xs ml-auto">→ {{ implode(', ', array_map(fn($db) => $db . '_restored_' . now()->format('Ymd_His'), $selectedBackupInfo['mongodb_databases'] ?? [])) }}</span>
                                    </div>
                                @endif
                                @if ($selectedBackupInfo['has_filesystem'] ?? false)
                                    <div class="flex items-center gap-2 text-sm">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-orange-500/10 text-orange-400">Files</span>
                                        <span class="text-base-content/70 font-mono text-xs">{{ implode(', ', $selectedBackupInfo['filesystem_paths'] ?? []) }}</span>
                                        <span class="text-base-content/40 text-xs ml-auto">→ {{ implode(', ', array_map(fn($p) => rtrim($p, '/') . '_restored_' . now()->format('Ymd_His'), $selectedBackupInfo['filesystem_paths'] ?? [])) }}</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        {{-- Restore Type Selection --}}
                        @php
                            $hasDb = ($selectedBackupInfo['has_mysql'] ?? false) || ($selectedBackupInfo['has_mongodb'] ?? false);
                            $hasFs = $selectedBackupInfo['has_filesystem'] ?? false;
                            $canChoose = $hasDb && $hasFs;
                        @endphp

                        @if ($canChoose)
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-3">{{ __('restore.select_type') }}</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="restoreType" value="full" class="peer hidden" />
                                        <div class="border-2 rounded-xl p-4 text-center transition-all
                                            peer-checked:border-primary peer-checked:bg-primary/5
                                            border-base-content/10 hover:border-base-content/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
                                            <p class="text-sm font-semibold">{{ __('restore.type_full') }}</p>
                                            <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_full_desc') }}</p>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="restoreType" value="db_only" class="peer hidden" />
                                        <div class="border-2 rounded-xl p-4 text-center transition-all
                                            peer-checked:border-primary peer-checked:bg-primary/5
                                            border-base-content/10 hover:border-base-content/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.375 19.5h17.25m-17.25 0a1.125 1.125 0 01-1.125-1.125M3.375 19.5h7.5c.621 0 1.125-.504 1.125-1.125m-9.75 0V5.625m0 12.75v-1.5c0-.621.504-1.125 1.125-1.125m18.375 2.625V5.625m0 12.75c0 .621-.504 1.125-1.125 1.125m1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125m0 3.75h-7.5A1.125 1.125 0 0112 18.375m9.75-12.75c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125m19.5 0v1.5c0 .621-.504 1.125-1.125 1.125M2.25 5.625v1.5c0 .621.504 1.125 1.125 1.125m0 0h17.25m-17.25 0h7.5c.621 0 1.125.504 1.125 1.125M3.375 8.25c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125M12 10.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 10.875c0 .621.504 1.125 1.125 1.125m-2.25 0c.621 0 1.125.504 1.125 1.125M10.875 12h-7.5c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m17.25-3.75h-7.5c-.621 0-1.125.504-1.125 1.125m8.625-1.125c.621 0 1.125.504 1.125 1.125v1.5c0 .621-.504 1.125-1.125 1.125m-17.25 0h7.5m-7.5 0c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125m0-3.75c.621 0 1.125.504 1.125 1.125M12 13.875v-1.5m0 1.5c0 .621-.504 1.125-1.125 1.125M12 13.875c0 .621.504 1.125 1.125 1.125" /></svg>
                                            <p class="text-sm font-semibold">{{ __('restore.type_db_only') }}</p>
                                            <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_db_only_desc') }}</p>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" wire:model="restoreType" value="files_only" class="peer hidden" />
                                        <div class="border-2 rounded-xl p-4 text-center transition-all
                                            peer-checked:border-primary peer-checked:bg-primary/5
                                            border-base-content/10 hover:border-base-content/20">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                                            <p class="text-sm font-semibold">{{ __('restore.type_files_only') }}</p>
                                            <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_files_only_desc') }}</p>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        @endif

                        {{-- Warning --}}
                        <div class="rounded-xl border border-warning/20 bg-warning/[0.03] p-4">
                            <div class="flex gap-3">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                <div>
                                    <p class="text-sm font-medium text-warning">{{ __('restore.warning_title') }}</p>
                                    <p class="text-xs text-warning/70 mt-1">{{ __('restore.warning_desc') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Step 1 --}}
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                        <button wire:click="closeRestoreModal" class="btn btn-sm rounded-xl">{{ __('restore.cancel') }}</button>
                        <button wire:click="confirmRestore" class="btn btn-primary btn-sm rounded-xl gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            {{ __('restore.continue') }}
                        </button>
                    </div>
                @else
                    {{-- Step 2: Confirmation --}}
                    <div class="px-6 py-5 space-y-5">
                        <div class="rounded-xl border border-error/20 bg-error/[0.03] p-5 text-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-error/60 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                            <h4 class="text-lg font-bold text-error mb-2">{{ __('restore.confirm_title') }}</h4>
                            <p class="text-sm text-base-content/60">{{ __('restore.confirm_desc') }}</p>
                        </div>

                        <div class="bg-base-200/50 border border-base-content/5 rounded-xl p-4 space-y-2">
                            <div class="flex justify-between text-sm">
                                <span class="text-base-content/50">{{ __('restore.info_job') }}</span>
                                <span class="font-medium">{{ $selectedBackupInfo['job_name'] ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-base-content/50">{{ __('restore.col_type') }}</span>
                                <span class="font-medium">{{ __('restore.type_' . $restoreType) }}</span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-base-content/50">{{ __('restore.info_date') }}</span>
                                <span class="font-medium tabular-nums">{{ $selectedBackupInfo['backup_date'] ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    {{-- Footer Step 2 --}}
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                        <button wire:click="$set('showConfirmation', false)" class="btn btn-sm rounded-xl">{{ __('restore.back') }}</button>
                        <button wire:click="executeRestore" class="btn btn-error btn-sm rounded-xl gap-2" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="executeRestore">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" /></svg>
                            </span>
                            <span wire:loading wire:target="executeRestore" class="loading loading-spinner loading-xs"></span>
                            {{ __('restore.execute') }}
                        </button>
                    </div>
                @endif
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeRestoreModal"></div>
        </div>
    @endif

    {{-- Detail Modal --}}
    @if ($showDetail && $detailLog)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">{{ __('restore.detail_title') }}</h3>
                    <button wire:click="closeDetail" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_backup') }}</p>
                            <p class="text-sm font-semibold">{{ $detailLog->backupLog->job->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_status') }}</p>
                            @switch($detailLog->status)
                                @case('success')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success"><span class="w-1.5 h-1.5 rounded-full bg-success"></span>{{ __('restore.status_success') }}</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error"><span class="w-1.5 h-1.5 rounded-full bg-error"></span>{{ __('restore.status_failed') }}</span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning"><span class="loading loading-spinner" style="width:10px;height:10px"></span>{{ __('restore.status_running') }}</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50"><span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>{{ __('restore.status_pending') }}</span>
                            @endswitch
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_type') }}</p>
                            <p class="text-sm">{{ __('restore.type_' . $detailLog->restore_type) }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_user') }}</p>
                            <p class="text-sm">{{ $detailLog->user->name ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_started') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->started_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.detail_finished') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->finished_at?->format('d/m/Y H:i:s') ?? '-' }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.col_duration') }}</p>
                            <p class="text-sm tabular-nums">{{ $detailLog->formatted_duration }}</p>
                        </div>
                        @if ($detailLog->restored_db_name)
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.detail_db') }}</p>
                                <p class="text-sm font-mono">{{ $detailLog->restored_db_name }}</p>
                            </div>
                        @endif
                        @if ($detailLog->restored_path)
                            <div class="col-span-2">
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.detail_path') }}</p>
                                <p class="text-sm font-mono break-all">{{ $detailLog->restored_path }}</p>
                            </div>
                        @endif
                    </div>

                    @if ($detailLog->error_message)
                        <div class="rounded-xl border border-error/20 bg-error/[0.03] p-4">
                            <p class="text-xs font-medium text-error uppercase tracking-wider mb-2">{{ __('restore.detail_error') }}</p>
                            <p class="text-sm text-error/80">{{ $detailLog->error_message }}</p>
                        </div>
                    @endif

                    @if ($detailLog->meta)
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-2">{{ __('restore.detail_meta') }}</p>
                            <pre class="bg-base-200/50 border border-base-content/5 p-4 rounded-xl text-xs overflow-x-auto font-mono">{{ json_encode($detailLog->meta, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    @endif
                </div>

                <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                    <button wire:click="closeDetail" class="btn btn-sm rounded-xl">{{ __('restore.close') }}</button>
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeDetail"></div>
        </div>
    @endif
</div>
