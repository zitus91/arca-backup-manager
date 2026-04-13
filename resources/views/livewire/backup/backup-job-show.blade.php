<div class="space-y-6">

    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-start sm:justify-between gap-4">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.backup.jobs') }}"
               class="btn btn-ghost btn-sm btn-square rounded-xl text-base-content/50 hover:text-base-content">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div>
                <div class="flex items-center gap-2 flex-wrap">
                    <h1 class="text-2xl font-bold tracking-tight">{{ $this->job->name }}</h1>
                    @if ($this->job->is_active)
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                            <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                            {{ __('backup-job-show.active') }}
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/40">
                            <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                            {{ __('backup-job-show.inactive') }}
                        </span>
                    @endif
                </div>
                <p class="text-base-content/50 text-sm mt-0.5">{{ __('backup-job-show.subtitle') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-shrink-0">
            <button wire:click="runNow"
                    class="btn btn-primary btn-sm gap-2 rounded-xl shadow-lg shadow-primary/20"
                    wire:loading.attr="disabled" wire:target="runNow">
                <span wire:loading wire:target="runNow" class="loading loading-spinner loading-xs"></span>
                <span wire:loading.remove wire:target="runNow">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                    </svg>
                </span>
                {{ __('backup-job-show.run_now') }}
            </button>
        </div>
    </div>

    {{-- Flash Message --}}
    @if (session()->has('message'))
        <div class="alert bg-success/10 text-success border border-success/20 rounded-xl">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span class="text-sm">{{ session('message') }}</span>
        </div>
    @endif

    {{-- Stat Cards --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
        {{-- Total Backups --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4">
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.stat_total') }}</p>
                <p class="text-3xl font-bold mt-1">{{ $this->stats['total'] }}</p>
                <div class="flex items-center gap-3 mt-2 text-xs text-base-content/50">
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                        {{ $this->stats['success'] }} ok
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                        {{ $this->stats['failed'] }} fail
                    </span>
                </div>
            </div>
        </div>

        {{-- Success Rate --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4">
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.stat_success_rate') }}</p>
                @if ($this->stats['success_rate'] !== null)
                    <p class="text-3xl font-bold mt-1 {{ $this->stats['success_rate'] >= 90 ? 'text-success' : ($this->stats['success_rate'] >= 70 ? 'text-warning' : 'text-error') }}">
                        {{ $this->stats['success_rate'] }}%
                    </p>
                    <div class="w-full bg-base-content/10 rounded-full h-1 mt-2">
                        <div class="h-1 rounded-full {{ $this->stats['success_rate'] >= 90 ? 'bg-success' : ($this->stats['success_rate'] >= 70 ? 'bg-warning' : 'bg-error') }}"
                             style="width: {{ $this->stats['success_rate'] }}%"></div>
                    </div>
                @else
                    <p class="text-3xl font-bold mt-1 text-base-content/30">{{ __('backup-job-show.no_data') }}</p>
                @endif
            </div>
        </div>

        {{-- Total Storage --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4">
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.stat_storage') }}</p>
                <p class="text-3xl font-bold mt-1">
                    {{ $this->stats['total_bytes'] > 0 ? $this->formatBytes($this->stats['total_bytes']) : __('backup-job-show.no_data') }}
                </p>
                <p class="text-xs text-base-content/40 mt-2">{{ __('backup-job-show.stat_avg_duration') }}:
                    {{ $this->stats['avg_duration'] ? $this->formatDuration($this->stats['avg_duration']) : __('backup-job-show.no_data') }}
                </p>
            </div>
        </div>

        {{-- Restores --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4">
                <p class="text-xs font-medium text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.stat_restores') }}</p>
                <p class="text-3xl font-bold mt-1">{{ $this->stats['total_restores'] }}</p>
                <p class="text-xs text-base-content/40 mt-2">
                    <span class="text-success font-medium">{{ $this->stats['success_restores'] }}</span>
                    {{ __('backup-job-show.stat_restores_ok') }}
                </p>
            </div>
        </div>
    </div>

    {{-- Details + Chart Grid --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4">

        {{-- Job Details --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-semibold text-sm">{{ __('backup-job-show.details') }}</h2>
                    <button wire:click="openEdit"
                            class="btn btn-ghost btn-xs gap-1 rounded-lg text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931z"/>
                        </svg>
                        {{ __('backup-job-show.edit') }}
                    </button>
                </div>
                <dl class="space-y-3">
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_source') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->job->source->name ?? '-' }}</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_destination') }}</dt>
                        <dd class="text-sm font-medium">{{ $this->job->destination->name ?? '-' }}</dd>
                    </div>
                    <div class="divider my-1"></div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_schedule') }}</dt>
                        <dd class="text-sm font-medium">
                            @php
                                $scheduleKey = 'schedule_' . $this->job->schedule_type;
                            @endphp
                            {{ __("backup-job-show.{$scheduleKey}") }}
                            @if ($this->job->schedule_type === 'cron' && $this->job->schedule_cron)
                                <code class="ml-1 text-xs bg-base-content/5 px-1.5 py-0.5 rounded font-mono">{{ $this->job->schedule_cron }}</code>
                            @endif
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_type') }}</dt>
                        <dd class="text-sm font-medium">
                            @if ($this->job->backup_type === 'incremental')
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-semibold bg-info/10 text-info">
                                    {{ __('backup-job-show.type_incremental') }}
                                </span>
                            @else
                                <span class="inline-flex items-center gap-1.5 px-2 py-0.5 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/60">
                                    {{ __('backup-job-show.type_full') }}
                                </span>
                            @endif
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_retention') }}</dt>
                        <dd class="text-sm font-medium">{{ __('backup-job-show.detail_retention_n', ['count' => $this->job->retention_count]) }}</dd>
                    </div>
                    <div class="divider my-1"></div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_last_run') }}</dt>
                        <dd class="text-sm font-medium">
                            {{ $this->job->last_run_at ? $this->job->last_run_at->format('d/m/Y H:i') : __('backup-job-show.detail_never') }}
                        </dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-base-content/40 font-medium">{{ __('backup-job-show.detail_next_run') }}</dt>
                        <dd class="text-sm font-medium">
                            {{ $this->job->next_run_at ? $this->job->next_run_at->format('d/m/Y H:i') : __('backup-job-show.detail_never') }}
                        </dd>
                    </div>
                </dl>
            </div>
        </div>

        {{-- Activity Chart --}}
        <div class="card bg-base-100 border border-base-content/5 rounded-xl lg:col-span-2">
            <div class="card-body p-5">
                <h2 class="font-semibold text-sm mb-4">{{ __('backup-job-show.chart_title') }}</h2>
                @php
                    $maxVal   = collect($this->chartData)->max(fn($d) => $d['success'] + $d['failed']) ?: 1;
                    $chartDays = collect($this->chartData)->map(fn($d) => array_merge($d, [
                        'total' => $d['success'] + $d['failed'],
                        'pct'   => ($d['success'] + $d['failed']) > 0
                            ? round((($d['success'] + $d['failed']) / $maxVal) * 100)
                            : 0,
                        'sPct'  => ($d['success'] + $d['failed']) > 0
                            ? round(($d['success'] / ($d['success'] + $d['failed'])) * 100)
                            : 0,
                    ]))->all();
                @endphp
                {{-- Barre --}}
                <div class="relative h-20" aria-label="{{ __('backup-job-show.chart_title') }}">
                    <div class="absolute inset-0 flex items-end justify-between gap-0.5">
                        @foreach ($chartDays as $day)
                            <div class="flex-1 flex flex-col-reverse rounded-t overflow-hidden"
                                 style="height: {{ max($day['pct'], 4) }}%"
                                 title="{{ $day['label'] }}: {{ $day['success'] }} ok, {{ $day['failed'] }} fail">
                                @if ($day['success'] > 0)
                                    <div class="w-full bg-success/70" style="height: {{ $day['sPct'] }}%"></div>
                                @endif
                                @if ($day['failed'] > 0)
                                    <div class="w-full bg-error/70" style="height: {{ 100 - $day['sPct'] }}%"></div>
                                @endif
                                @if ($day['total'] === 0)
                                    <div class="w-full bg-base-content/5 rounded-t" style="height: 100%"></div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                </div>
                {{-- Etichette --}}
                <div class="flex justify-between gap-0.5 mt-1">
                    @foreach ($chartDays as $day)
                        <div class="flex-1 text-center">
                            <span class="text-[9px] text-base-content/30 {{ $day['is_today'] ? 'font-bold text-primary/60' : '' }}">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
                <div class="flex items-center gap-4 mt-3">
                    <span class="flex items-center gap-1.5 text-xs text-base-content/50">
                        <span class="w-2.5 h-2.5 rounded-sm bg-success/70"></span>
                        {{ __('backup-job-show.chart_success') }}
                    </span>
                    <span class="flex items-center gap-1.5 text-xs text-base-content/50">
                        <span class="w-2.5 h-2.5 rounded-sm bg-error/70"></span>
                        {{ __('backup-job-show.chart_failed') }}
                    </span>
                </div>

                {{-- Backup Fissati --}}
                <div class="mt-5 pt-4 border-t border-base-content/5">
                    <div class="flex items-center justify-between mb-3">
                        <h3 class="text-xs font-semibold text-base-content/50 uppercase tracking-wider flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-warning" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" /></svg>
                            {{ __('backup-job-show.locked_backups_title') }}
                        </h3>
                        <span class="text-xs text-base-content/30">{{ $this->lockedLogs->count() }}</span>
                    </div>
                    @if ($this->lockedLogs->isEmpty())
                        <p class="text-xs text-base-content/30 text-center py-2">{{ __('backup-job-show.locked_backups_empty') }}</p>
                    @else
                        <div class="space-y-1.5">
                            @foreach ($this->lockedLogs as $locked)
                                <div class="flex items-center justify-between gap-2 px-2.5 py-2 rounded-lg bg-warning/[0.04] border border-warning/10">
                                    <div class="flex items-center gap-2 min-w-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-warning/70 flex-shrink-0" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" /></svg>
                                        <div class="min-w-0">
                                            <p class="text-xs font-medium tabular-nums">{{ $locked->started_at->format('d/m/Y H:i') }}</p>
                                            <p class="text-[10px] text-base-content/40 truncate">{{ $locked->formatted_size }} · {{ $locked->is_full ? __('backup-job-show.type_full') : __('backup-job-show.type_incremental') }}</p>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 flex-shrink-0">
                                        @if ($locked->storage_path)
                                            <a href="{{ route('admin.backup.logs.download', $locked->id) }}"
                                               class="btn btn-ghost btn-xs btn-square rounded-lg text-info/70 tooltip tooltip-left"
                                               data-tip="{{ __('backup-job-show.action_download') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/></svg>
                                            </a>
                                            <button wire:click="openRestoreModal({{ $locked->id }})"
                                                    class="btn btn-ghost btn-xs btn-square rounded-lg text-accent/70 tooltip tooltip-left"
                                                    data-tip="{{ __('backup-job-show.action_restore') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                            </button>
                                        @endif
                                        <button wire:click="toggleLock({{ $locked->id }})"
                                                class="btn btn-ghost btn-xs btn-square rounded-lg text-warning/70 tooltip tooltip-left"
                                                data-tip="{{ __('backup-job-show.action_unlock') }}">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                                        </button>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- Logs Tabs --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden flex flex-col h-[440px]">
        {{-- Tab Header --}}
        <div class="flex border-b border-base-content/5 px-4 flex-shrink-0">
            <button wire:click="setTab('backups')"
                    class="px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors
                           {{ $logsTab === 'backups' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content' }}">
                {{ __('backup-job-show.tab_backups') }}
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-base-content/5">{{ $this->stats['total'] }}</span>
            </button>
            <button wire:click="setTab('restores')"
                    class="px-4 py-3 text-sm font-medium border-b-2 -mb-px transition-colors
                           {{ $logsTab === 'restores' ? 'border-primary text-primary' : 'border-transparent text-base-content/50 hover:text-base-content' }}">
                {{ __('backup-job-show.tab_restores') }}
                <span class="ml-1.5 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-base-content/5">{{ $this->stats['total_restores'] }}</span>
            </button>
        </div>

        {{-- Backup Logs Tab --}}
        @if ($logsTab === 'backups')
            <div wire:loading.class="opacity-50" wire:target="setTab" class="flex flex-col flex-1 min-h-0">
                <div class="overflow-auto flex-1 min-h-0">
                    <table class="table">
                        <thead class="sticky top-0 z-10 bg-base-100">
                            <tr class="border-b border-base-content/5">
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_started') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_status') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_type') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_size') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_duration') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('backup-job-show.col_actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->recentLogs as $log)
                                <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors {{ $log->is_locked ? 'bg-warning/[0.02]' : '' }}" wire:key="bklog-{{ $log->id }}">
                                    <td class="text-sm">
                                        <div class="flex flex-col gap-0.5">
                                            <div class="flex items-center gap-1.5">
                                                <span class="font-medium">{{ $log->started_at->format('d/m/Y H:i') }}</span>
                                                @if ($log->is_locked)
                                                    <span class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-[10px] font-semibold bg-warning/15 text-warning border border-warning/20 tooltip tooltip-right"
                                                          data-tip="{{ $log->locked_at ? __('backup-job-show.locked_by', ['name' => $log->locker?->name ?? '—', 'date' => $log->locked_at->format('d/m H:i')]) : __('backup-job-show.locked_badge') }}">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-2.5 h-2.5" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" /></svg>
                                                        {{ __('backup-job-show.locked_badge') }}
                                                    </span>
                                                @endif
                                            </div>
                                            @if ($log->finished_at)
                                                <span class="text-xs text-base-content/40">→ {{ $log->finished_at->format('H:i') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @switch($log->status)
                                            @case('running')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                                    <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                                    {{ __('backup-job-show.status_running') }}
                                                </span>
                                                @break
                                            @case('success')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                                    {{ __('backup-job-show.status_success') }}
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                    {{ __('backup-job-show.status_failed') }}
                                                </span>
                                                @break
                                            @case('partial')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-warning"></span>
                                                    {{ __('backup-job-show.status_partial') }}
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">
                                                    {{ __('backup-job-show.status_pending') }}
                                                </span>
                                                @break
                                            @case('cancelled')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/10 text-base-content/40">
                                                    {{ __('backup-job-show.status_cancelled') }}
                                                </span>
                                                @break
                                        @endswitch
                                    </td>
                                    <td class="text-xs text-base-content/60">
                                        @if ($log->is_full)
                                            <span class="px-2 py-0.5 rounded bg-base-content/5">{{ __('backup-job-show.type_full') }}</span>
                                        @else
                                            <span class="px-2 py-0.5 rounded bg-info/10 text-info">{{ __('backup-job-show.type_incremental') }}</span>
                                        @endif
                                    </td>
                                    <td class="text-sm text-base-content/70">{{ $log->formatted_size }}</td>
                                    <td class="text-sm text-base-content/70">{{ $log->formatted_duration }}</td>
                                    <td>
                                        <div class="flex items-center justify-end gap-1">
                                            {{-- Detail --}}
                                            <button wire:click="openLogDetail({{ $log->id }})"
                                                    class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left"
                                                    data-tip="{{ __('backup-job-show.action_detail') }}">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z"/>
                                                </svg>
                                            </button>
                                            {{-- Lock / Unlock (solo se il file esiste ancora) --}}
                                            @if ($log->storage_path)
                                            <button wire:click="toggleLock({{ $log->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="toggleLock({{ $log->id }})"
                                                    class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left {{ $log->is_locked ? 'text-warning/80 hover:text-warning hover:bg-warning/10' : 'text-base-content/40 hover:text-base-content/70 hover:bg-base-content/10' }}"
                                                    data-tip="{{ $log->is_locked ? __('backup-job-show.action_unlock') : __('backup-job-show.action_lock') }}">
                                                @if ($log->is_locked)
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor">
                                                        <path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" />
                                                    </svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/>
                                                    </svg>
                                                @endif
                                            </button>
                                            @endif
                                            {{-- Download --}}
                                            @if ($log->status === 'success' && $log->storage_path)
                                                <a href="{{ route('admin.backup.logs.download', $log->id) }}"
                                                   class="btn btn-ghost btn-xs btn-square rounded-lg text-info/80 hover:text-info hover:bg-info/10 tooltip tooltip-left"
                                                   data-tip="{{ __('backup-job-show.action_download') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                                    </svg>
                                                </a>
                                            @endif
                                            {{-- Restore --}}
                                            @if ($log->status === 'success' && $log->storage_path)
                                                <button wire:click="openRestoreModal({{ $log->id }})"
                                                        class="btn btn-ghost btn-xs btn-square rounded-lg text-accent/80 hover:text-accent hover:bg-accent/10 tooltip tooltip-left"
                                                        data-tip="{{ __('backup-job-show.action_restore') }}">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                                                    </svg>
                                                </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-12">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm text-base-content/40">{{ __('backup-job-show.empty_backups') }}</p>
                                            <button wire:click="runNow" class="btn btn-primary btn-sm mt-4 rounded-xl gap-2">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z"/>
                                                </svg>
                                                {{ __('backup-job-show.run_now') }}
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($this->recentLogs->hasPages())
                    <div class="px-6 py-4 border-t border-base-content/5 flex-shrink-0">
                        {{ $this->recentLogs->links() }}
                    </div>
                @endif
            </div>
        @endif

        {{-- Restore History Tab --}}
        @if ($logsTab === 'restores')
            <div wire:loading.class="opacity-50" wire:target="setTab" class="flex flex-col flex-1 min-h-0">
                <div class="overflow-auto flex-1 min-h-0">
                    <table class="table">
                        <thead class="sticky top-0 z-10 bg-base-100">
                            <tr class="border-b border-base-content/5">
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_started') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_status') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_restore_type') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_target') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_backup_ref') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_user') }}</th>
                                <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job-show.col_duration') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->restoreLogs as $restore)
                                <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="rst-{{ $restore->id }}">
                                    <td class="text-sm">
                                        <div class="flex flex-col gap-0.5">
                                            <span class="font-medium">{{ $restore->started_at->format('d/m/Y H:i') }}</span>
                                            @if ($restore->finished_at)
                                                <span class="text-xs text-base-content/40">→ {{ $restore->finished_at->format('H:i') }}</span>
                                            @endif
                                        </div>
                                    </td>
                                    <td>
                                        @switch($restore->status)
                                            @case('completed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                                    {{ __('backup-job-show.status_completed') }}
                                                </span>
                                                @break
                                            @case('running')
                                                <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning">
                                                    <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                                    {{ __('backup-job-show.status_running') }}
                                                </span>
                                                @break
                                            @case('failed')
                                                <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-error"></span>
                                                    {{ __('backup-job-show.status_failed') }}
                                                </span>
                                                @break
                                            @default
                                                <span class="px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">{{ $restore->status }}</span>
                                        @endswitch
                                    </td>
                                    <td class="text-sm text-base-content/70">{{ $restore->restore_type_label }}</td>
                                    <td class="text-sm text-base-content/70">
                                        {{ $restore->restore_target === 'remote' ? '🌐 Remote' : '🖥 Local' }}
                                    </td>
                                    <td class="text-xs text-base-content/50 font-mono">
                                        @if ($restore->backupLog)
                                            #{{ $restore->backupLog->id }}
                                            <span class="ml-1 text-base-content/30">{{ $restore->backupLog->started_at?->format('d/m H:i') }}</span>
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                    <td class="text-sm text-base-content/70">{{ $restore->user->name ?? '—' }}</td>
                                    <td class="text-sm text-base-content/70">{{ $restore->formatted_duration }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7">
                                        <div class="text-center py-12">
                                            <div class="w-14 h-14 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/>
                                                </svg>
                                            </div>
                                            <p class="text-sm text-base-content/40">{{ __('backup-job-show.empty_restores') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if ($this->restoreLogs->hasPages())
                    <div class="px-6 py-4 border-t border-base-content/5 flex-shrink-0">
                        {{ $this->restoreLogs->links() }}
                    </div>
                @endif
            </div>
        @endif
    </div>

    {{-- MODAL: Edit Job Form --}}
    @if ($showEditForm)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">{{ __('backup-job.edit_title') }}</h3>
                    <button wire:click="closeEdit" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <livewire:backup.backup-job-form :jobId="$jobId" :key="'edit-job-' . $jobId" />
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeEdit"></div>
        </div>
    @endif

    {{-- MODAL: Backup Log Detail --}}
    @if ($showLogDetail && $detailLog)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">{{ __('backup-log.detail_title') }}</h3>
                    <button wire:click="closeLogDetail" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5 space-y-5">
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.col_status') }}</p>
                            @switch($detailLog->status)
                                @case('success')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success"><span class="w-1.5 h-1.5 rounded-full bg-success"></span>{{ __('backup-log.status_success') }}</span>
                                    @break
                                @case('failed')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-error/10 text-error"><span class="w-1.5 h-1.5 rounded-full bg-error"></span>{{ __('backup-log.status_failed') }}</span>
                                    @break
                                @case('running')
                                    <span class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning"><span class="loading loading-spinner" style="width:10px;height:10px"></span>{{ __('backup-log.status_running') }}</span>
                                    @break
                                @case('partial')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-warning/10 text-warning"><span class="w-1.5 h-1.5 rounded-full bg-warning"></span>{{ __('backup-log.status_partial') }}</span>
                                    @break
                                @default
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/50">{{ $detailLog->status }}</span>
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
                        <div class="col-span-2">
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('backup-log.detail_path') }}</p>
                            <p class="text-sm break-all font-mono text-base-content/70">{{ $detailLog->storage_path ?? '-' }}</p>
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
                <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                    {{-- Lock / Unlock (solo se il file esiste ancora) --}}
                    @if ($detailLog->storage_path)
                    <button wire:click="toggleLock({{ $detailLog->id }})"
                            class="btn btn-sm rounded-xl gap-2 mr-auto {{ $detailLog->is_locked ? 'btn-warning' : 'btn-ghost' }}">
                        @if ($detailLog->is_locked)
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" viewBox="0 0 24 24" fill="currentColor"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 00-5.25 5.25v3a3 3 0 00-3 3v6.75a3 3 0 003 3h10.5a3 3 0 003-3v-6.75a3 3 0 00-3-3v-3c0-2.9-2.35-5.25-5.25-5.25zm3.75 8.25v-3a3.75 3.75 0 10-7.5 0v3h7.5z" clip-rule="evenodd" /></svg>
                            {{ __('backup-job-show.action_unlock') }}
                        @else
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 10.5V6.75a4.5 4.5 0 119 0v3.75M3.75 21.75h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H3.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z"/></svg>
                            {{ __('backup-job-show.action_lock') }}
                        @endif
                    </button>
                    @endif
                    @if ($detailLog->status === 'success' && $detailLog->storage_path)
                        <a href="{{ route('admin.backup.logs.download', $detailLog) }}" class="btn btn-primary btn-sm rounded-xl gap-2" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                            {{ __('backup-log.download') }}
                        </a>
                    @endif
                    @if ($detailLog->status === 'success' && $detailLog->storage_path)
                        <button wire:click="closeLogDetailAndRestore({{ $detailLog->id }})" class="btn btn-accent btn-sm rounded-xl gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                            {{ __('backup-job-show.action_restore') }}
                        </button>
                    @endif
                    <button wire:click="closeLogDetail" class="btn btn-sm rounded-xl">{{ __('backup-log.close') }}</button>
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeLogDetail"></div>
        </div>
    @endif

    {{-- MODAL: Restore --}}
    @if ($showRestoreModal && $selectedBackupLogId)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
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
                    <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_date') }}</p>
                                <p class="text-sm tabular-nums">{{ $selectedBackupInfo['backup_date'] ?? '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1">{{ __('restore.info_size') }}</p>
                                <p class="text-sm tabular-nums">{{ $selectedBackupInfo['file_size'] ?? '-' }}</p>
                            </div>
                        </div>

                        <div>
                            <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-3">{{ __('restore.restore_target') }}</p>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="restoreTarget" value="same_host" class="peer hidden" />
                                    <div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 border-base-content/10 hover:border-base-content/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 14.25h13.5m-13.5 0a3 3 0 01-3-3m3 3a3 3 0 100 6h13.5a3 3 0 100-6m-16.5-3a3 3 0 013-3h13.5a3 3 0 013 3m-19.5 0a4.5 4.5 0 01.9-2.7L5.737 5.1a3.375 3.375 0 012.7-1.35h7.126c1.062 0 2.062.5 2.7 1.35l2.587 3.45a4.5 4.5 0 01.9 2.7m0 0a3 3 0 01-3 3m0 3h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008zm-3 6h.008v.008h-.008v-.008zm0-6h.008v.008h-.008v-.008z" /></svg>
                                        <p class="text-sm font-semibold">{{ __('restore.target_same_host') }}</p>
                                        <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.target_same_host_desc') }}</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="restoreTarget" value="remote_host" class="peer hidden" />
                                    <div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 border-base-content/10 hover:border-base-content/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 008.716-6.747M12 21a9.004 9.004 0 01-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 017.843 4.582M12 3a8.997 8.997 0 00-7.843 4.582m15.686 0A11.953 11.953 0 0112 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0121 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0112 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 013 12c0-1.605.42-3.113 1.157-4.418" /></svg>
                                        <p class="text-sm font-semibold">{{ __('restore.target_remote_host') }}</p>
                                        <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.target_remote_host_desc') }}</p>
                                    </div>
                                </label>
                                <label class="cursor-pointer">
                                    <input type="radio" wire:model.live="restoreTarget" value="known_host" class="peer hidden" />
                                    <div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-secondary peer-checked:bg-secondary/5 border-base-content/10 hover:border-base-content/20">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 mx-auto mb-2 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                        <p class="text-sm font-semibold">{{ __('restore.target_known_host') }}</p>
                                        <p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.target_known_host_desc') }}</p>
                                    </div>
                                </label>
                            </div>
                        </div>

                        @if (in_array($restoreTarget, ['remote_host', 'known_host']))
                            <div class="rounded-xl border border-primary/20 bg-primary/[0.02] p-4 space-y-4">
                                <p class="text-xs font-semibold text-primary uppercase tracking-wider">{{ __('restore.remote_config') }}</p>
                                @if ($restoreTarget === 'known_host')
                                    <div class="form-control">
                                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('restore.known_host_select') }}</span></label>
                                        <select wire:model.live="knownSourceId" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10">
                                            <option value="">— {{ __('restore.known_host_select') }} —</option>
                                            @foreach ($backupSources as $src)
                                                <option value="{{ $src->id }}">{{ $src->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                                @if (($selectedBackupInfo['has_mysql'] ?? false) && in_array($restoreType, ['full', 'db_only']))
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-base-content/60"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-blue-500/10 text-blue-400 mr-2">MySQL</span>{{ __('restore.remote_mysql_config') }}</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_host') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mysql.host" class="input input-bordered input-sm rounded-lg" placeholder="192.168.1.100" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_port') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mysql.port" class="input input-bordered input-sm rounded-lg" placeholder="3306" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_username') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mysql.username" class="input input-bordered input-sm rounded-lg" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_password') }}</span></label><input type="password" wire:model.live.debounce.500ms="remoteConfig.mysql.password" class="input input-bordered input-sm rounded-lg" /></div>
                                        </div>
                                    </div>
                                @endif
                                @if (($selectedBackupInfo['has_mongodb'] ?? false) && in_array($restoreType, ['full', 'db_only']))
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-base-content/60"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-green-500/10 text-green-400 mr-2">MongoDB</span>{{ __('restore.remote_mongodb_config') }}</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_host') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mongodb.host" class="input input-bordered input-sm rounded-lg" placeholder="192.168.1.100" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_port') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mongodb.port" class="input input-bordered input-sm rounded-lg" placeholder="27017" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_username') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mongodb.username" class="input input-bordered input-sm rounded-lg" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_password') }}</span></label><input type="password" wire:model.live.debounce.500ms="remoteConfig.mongodb.password" class="input input-bordered input-sm rounded-lg" /></div>
                                            <div class="form-control col-span-2"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_auth_database') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.mongodb.auth_database" class="input input-bordered input-sm rounded-lg" placeholder="admin" /></div>
                                        </div>
                                    </div>
                                @endif
                                @if (($selectedBackupInfo['has_filesystem'] ?? false) && in_array($restoreType, ['full', 'files_only']))
                                    <div class="space-y-2">
                                        <p class="text-xs font-medium text-base-content/60"><span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-orange-500/10 text-orange-400 mr-2">SSH</span>{{ __('restore.remote_filesystem_config') }}</p>
                                        <div class="grid grid-cols-2 gap-2">
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_ssh_host') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.filesystem.ssh_host" class="input input-bordered input-sm rounded-lg" placeholder="192.168.1.100" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_ssh_port') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.filesystem.ssh_port" class="input input-bordered input-sm rounded-lg" placeholder="22" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_ssh_user') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.filesystem.ssh_user" class="input input-bordered input-sm rounded-lg" /></div>
                                            <div class="form-control"><label class="label pb-0.5"><span class="label-text text-[10px] uppercase text-base-content/40">{{ __('restore.remote_ssh_key_path') }}</span></label><input type="text" wire:model.live.debounce.500ms="remoteConfig.filesystem.ssh_key_path" class="input input-bordered input-sm rounded-lg" placeholder="/root/.ssh/id_rsa" /></div>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        @endif

                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('restore.backup_contains') }}</p>
                                <button type="button" wire:click="resetCustomNames" class="text-xs text-primary hover:underline">{{ __('restore.reset_names') }}</button>
                            </div>
                            <div class="space-y-2">
                                @if ($selectedBackupInfo['has_mysql'] ?? false)
                                    <div class="rounded-lg border border-base-content/5 bg-base-200/30 p-3 space-y-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-blue-500/10 text-blue-400">MySQL</span>
                                        @foreach ($customDbNames as $index => $item)
                                            @if ($item['type'] === 'mysql')
                                                <div class="px-2 py-1.5 rounded-lg {{ in_array($restoreType, ['files_only']) ? 'opacity-30 pointer-events-none' : '' }}">
                                                    <label class="flex items-center gap-3 cursor-pointer">
                                                        <input type="checkbox" wire:model.live="selectedDatabases" value="{{ $item['original'] }}" class="checkbox checkbox-sm checkbox-primary rounded" {{ in_array($restoreType, ['files_only']) ? 'disabled' : '' }} />
                                                        <span class="text-sm font-mono text-base-content/70">{{ $item['original'] }}</span>
                                                        <span class="text-base-content/30 text-xs">&rarr;</span>
                                                        <input type="text" wire:model.live.debounce.300ms="customDbNames.{{ $index }}.target" class="input input-xs input-bordered rounded-lg font-mono flex-1 text-xs" {{ in_array($restoreType, ['files_only']) ? 'disabled' : '' }} />
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                @if ($selectedBackupInfo['has_mongodb'] ?? false)
                                    <div class="rounded-lg border border-base-content/5 bg-base-200/30 p-3 space-y-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-green-500/10 text-green-400">MongoDB</span>
                                        @foreach ($customDbNames as $index => $item)
                                            @if ($item['type'] === 'mongodb')
                                                <div class="px-2 py-1.5 rounded-lg {{ in_array($restoreType, ['files_only']) ? 'opacity-30 pointer-events-none' : '' }}">
                                                    <label class="flex items-center gap-3 cursor-pointer">
                                                        <input type="checkbox" wire:model.live="selectedDatabases" value="{{ $item['original'] }}" class="checkbox checkbox-sm checkbox-primary rounded" {{ in_array($restoreType, ['files_only']) ? 'disabled' : '' }} />
                                                        <span class="text-sm font-mono text-base-content/70">{{ $item['original'] }}</span>
                                                        <span class="text-base-content/30 text-xs">&rarr;</span>
                                                        <input type="text" wire:model.live.debounce.300ms="customDbNames.{{ $index }}.target" class="input input-xs input-bordered rounded-lg font-mono flex-1 text-xs" {{ in_array($restoreType, ['files_only']) ? 'disabled' : '' }} />
                                                    </label>
                                                </div>
                                            @endif
                                        @endforeach
                                    </div>
                                @endif
                                @if ($selectedBackupInfo['has_filesystem'] ?? false)
                                    <div class="rounded-lg border border-base-content/5 bg-base-200/30 p-3 space-y-2">
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-semibold uppercase bg-orange-500/10 text-orange-400">Files</span>
                                        @foreach ($customPaths as $index => $item)
                                            <div class="px-2 py-1.5 rounded-lg {{ in_array($restoreType, ['db_only']) ? 'opacity-30 pointer-events-none' : '' }}">
                                                <label class="flex items-center gap-3 cursor-pointer">
                                                    <input type="checkbox" wire:model.live="selectedPaths" value="{{ $item['original'] }}" class="checkbox checkbox-sm checkbox-primary rounded" {{ in_array($restoreType, ['db_only']) ? 'disabled' : '' }} />
                                                    <span class="text-xs font-mono text-base-content/70">{{ $item['original'] }}</span>
                                                    <span class="text-base-content/30 text-xs">&rarr;</span>
                                                    <input type="text" wire:model.live.debounce.300ms="customPaths.{{ $index }}.target" class="input input-xs input-bordered rounded-lg font-mono flex-1 text-xs" {{ in_array($restoreType, ['db_only']) ? 'disabled' : '' }} />
                                                </label>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="rounded-xl border border-error/20 bg-error/[0.02] p-4 space-y-3">
                            <label class="flex items-center gap-3 cursor-pointer">
                                <input type="checkbox" wire:model.live="overrideExisting" class="toggle toggle-error toggle-sm" />
                                <div>
                                    <p class="text-sm font-semibold text-error">{{ __('restore.override_existing') }}</p>
                                    <p class="text-xs text-error/60">{{ __('restore.override_existing_desc') }}</p>
                                </div>
                            </label>
                            @if ($overrideExisting)
                                <div class="rounded-lg border border-error/30 bg-error/10 p-3 space-y-2">
                                    <div class="flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        <p class="text-xs font-bold text-error uppercase">{{ __('restore.override_warning_title') }}</p>
                                    </div>
                                    <ul class="text-xs text-error/80 space-y-1 list-disc list-inside">
                                        <li>{{ __('restore.override_warning_1') }}</li>
                                        <li>{{ __('restore.override_warning_2') }}</li>
                                        <li>{{ __('restore.override_warning_3') }}</li>
                                    </ul>
                                </div>
                            @endif
                        </div>

                        @php
                            $hasDb = ($selectedBackupInfo['has_mysql'] ?? false) || ($selectedBackupInfo['has_mongodb'] ?? false);
                            $hasFs = $selectedBackupInfo['has_filesystem'] ?? false;
                            $canChoose = $hasDb && $hasFs;
                        @endphp
                        @if ($canChoose)
                            <div>
                                <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-3">{{ __('restore.select_type') }}</p>
                                <div class="grid grid-cols-3 gap-3">
                                    <label class="cursor-pointer"><input type="radio" wire:model.live="restoreType" value="full" class="peer hidden" /><div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 border-base-content/10 hover:border-base-content/20"><p class="text-sm font-semibold">{{ __('restore.type_full') }}</p><p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_full_desc') }}</p></div></label>
                                    <label class="cursor-pointer"><input type="radio" wire:model.live="restoreType" value="db_only" class="peer hidden" /><div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 border-base-content/10 hover:border-base-content/20"><p class="text-sm font-semibold">{{ __('restore.type_db_only') }}</p><p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_db_only_desc') }}</p></div></label>
                                    <label class="cursor-pointer"><input type="radio" wire:model.live="restoreType" value="files_only" class="peer hidden" /><div class="border-2 rounded-xl p-4 text-center transition-all peer-checked:border-primary peer-checked:bg-primary/5 border-base-content/10 hover:border-base-content/20"><p class="text-sm font-semibold">{{ __('restore.type_files_only') }}</p><p class="text-[10px] text-base-content/40 mt-1">{{ __('restore.type_files_only_desc') }}</p></div></label>
                                </div>
                            </div>
                        @endif
                    </div>
                    <div class="flex justify-end gap-2 px-6 py-4 border-t border-base-content/5">
                        <button wire:click="closeRestoreModal" class="btn btn-sm rounded-xl">{{ __('restore.cancel') }}</button>
                        <button wire:click="confirmRestore" class="btn btn-primary btn-sm rounded-xl gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                            {{ __('restore.continue') }}
                        </button>
                    </div>
                @else
                    <div class="px-6 py-5 space-y-5 max-h-[70vh] overflow-y-auto">
                        @if ($overrideExisting)
                            <div class="rounded-xl border-2 border-error bg-error/10 p-5 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 mx-auto text-error mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                <h4 class="text-lg font-black text-error mb-2 uppercase">{{ __('restore.override_confirm_title') }}</h4>
                                <p class="text-sm text-error/80">{{ __('restore.override_confirm_desc') }}</p>
                            </div>
                        @else
                            <div class="rounded-xl border border-error/20 bg-error/[0.03] p-5 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto text-error/60 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                <h4 class="text-lg font-bold text-error mb-2">{{ __('restore.confirm_title') }}</h4>
                                <p class="text-sm text-base-content/60">{{ __('restore.confirm_desc') }}</p>
                            </div>
                        @endif
                        <div class="bg-base-200/50 border border-base-content/5 rounded-xl p-4 space-y-2">
                            <div class="flex justify-between text-sm"><span class="text-base-content/50">{{ __('restore.info_job') }}</span><span class="font-medium">{{ $selectedBackupInfo['job_name'] ?? '-' }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-base-content/50">{{ __('restore.col_type') }}</span><span class="font-medium">{{ __('restore.type_' . $restoreType) }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-base-content/50">{{ __('restore.confirm_target') }}</span>
                                <span class="font-medium {{ in_array($restoreTarget, ['remote_host', 'known_host']) ? 'text-warning' : '' }}">
                                    @if ($restoreTarget === 'remote_host') {{ __('restore.confirm_target_remote') }}
                                    @elseif ($restoreTarget === 'known_host') {{ __('restore.confirm_target_known') }}
                                    @else {{ __('restore.confirm_target_same') }} @endif
                                </span>
                            </div>
                            <div class="flex justify-between text-sm"><span class="text-base-content/50">{{ __('restore.confirm_override') }}</span><span class="font-medium {{ $overrideExisting ? 'text-error font-bold' : '' }}">{{ $overrideExisting ? __('restore.confirm_override_yes') : __('restore.confirm_override_no') }}</span></div>
                            <div class="flex justify-between text-sm"><span class="text-base-content/50">{{ __('restore.info_date') }}</span><span class="font-medium tabular-nums">{{ $selectedBackupInfo['backup_date'] ?? '-' }}</span></div>
                            @if (! empty($selectedDatabases) && in_array($restoreType, ['full', 'db_only']))
                                <div class="pt-2 border-t border-base-content/5">
                                    <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1.5">{{ __('restore.confirm_databases') }}</p>
                                    @foreach ($customDbNames as $item)
                                        @if (in_array($item['original'], $selectedDatabases))
                                            <div class="flex items-center gap-2 text-xs mb-1"><span class="font-mono text-info">{{ $item['original'] }}</span><span class="text-base-content/30">&rarr;</span><span class="font-mono font-semibold text-base-content/70">{{ $item['target'] }}</span></div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                            @if (! empty($selectedPaths) && in_array($restoreType, ['full', 'files_only']))
                                <div class="pt-2 border-t border-base-content/5">
                                    <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider mb-1.5">{{ __('restore.confirm_paths') }}</p>
                                    @foreach ($customPaths as $item)
                                        @if (in_array($item['original'], $selectedPaths))
                                            <div class="flex items-center gap-2 text-xs mb-1"><span class="font-mono text-accent">{{ $item['original'] }}</span><span class="text-base-content/30">&rarr;</span><span class="font-mono font-semibold text-base-content/70">{{ $item['target'] }}</span></div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
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

</div>
