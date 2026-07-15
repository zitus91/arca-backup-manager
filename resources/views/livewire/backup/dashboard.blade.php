<div class="space-y-8" wire:poll.30s>

    {{-- Impeccable: Varied layout instead of uniform hero-metric card grid.
         Primary success rate is prominent. Other metrics in a compact, scannable strip with stronger hierarchy. --}}
    <div>
        <div class="flex items-end justify-between mb-3">
            <div>
                <div class="text-[10px] font-semibold tracking-[0.08em] text-base-content/50 uppercase">{{ __('backup-dashboard.success_rate') }}</div>
                <div class="text-[11px] text-base-content/40">{{ __('backup-dashboard.last_30_days') }}</div>
            </div>
            <div class="text-[10px] text-base-content/40 tabular-nums">{{ $this->stats['total_backups_ever'] }} {{ __('backup-dashboard.total_backups') }}</div>
        </div>

        {{-- Primary metric - larger, deliberate visual weight --}}
        <div class="flex items-center gap-6 rounded-2xl bg-base-200/60 px-6 py-5 border border-base-content/5">
            <div class="relative w-20 h-20 flex-shrink-0">
                <svg class="w-20 h-20 -rotate-90" viewBox="0 0 80 80">
                    <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor" stroke-width="5" class="text-base-content/8" />
                    <circle cx="40" cy="40" r="34" fill="none" stroke="currentColor" stroke-width="5"
                        class="{{ $this->stats['success_rate'] >= 90 ? 'text-success' : ($this->stats['success_rate'] >= 70 ? 'text-warning' : 'text-error') }}"
                        stroke-dasharray="{{ 2 * 3.14159 * 34 }}"
                        stroke-dashoffset="{{ 2 * 3.14159 * 34 * (1 - $this->stats['success_rate'] / 100) }}"
                        stroke-linecap="round"
                        style="transition: stroke-dashoffset 800ms cubic-bezier(0.25, 0.1, 0.25, 1)" />
                </svg>
                <span class="absolute inset-0 flex items-center justify-center text-3xl font-semibold tabular-nums tracking-tighter">{{ $this->stats['success_rate'] }}</span>
                <span class="absolute inset-0 flex items-end justify-center pb-1 text-[10px] font-medium text-base-content/50">%</span>
            </div>

            <div class="flex-1 grid grid-cols-2 sm:grid-cols-4 gap-x-6 gap-y-3 text-sm">
                <div>
                    <div class="text-[10px] text-base-content/50 tracking-wide uppercase">{{ __('backup-dashboard.label_today') }}</div>
                    <div class="font-semibold tabular-nums mt-0.5">
                        <span class="text-success">{{ $this->stats['today_success'] }}</span>
                        @if ($this->stats['today_failed'] > 0)
                            <span class="text-error">/{{ $this->stats['today_failed'] }}</span>
                        @endif
                        <span class="text-base-content/30"> / {{ $this->stats['today_count'] }}</span>
                    </div>
                </div>

                <div>
                    <div class="text-[10px] text-base-content/50 tracking-wide uppercase">{{ __('backup-dashboard.status_running') }}</div>
                    <div class="font-semibold tabular-nums mt-0.5 flex items-center gap-1.5">
                        @if ($this->stats['running'] > 0)
                            <span class="inline-block w-1.5 h-1.5 rounded-full bg-warning animate-pulse"></span>
                        @endif
                        {{ $this->stats['running'] }}
                    </div>
                </div>

                <div>
                    <div class="text-[10px] text-base-content/50 tracking-wide uppercase">{{ __('backup-dashboard.active_jobs') }}</div>
                    <div class="font-semibold tabular-nums mt-0.5">{{ $this->stats['active_jobs'] }} <span class="font-normal text-base-content/30">/ {{ $this->stats['total_jobs'] }}</span></div>
                </div>

                <div>
                    <div class="text-[10px] text-base-content/50 tracking-wide uppercase">{{ __('backup-dashboard.total_storage') }}</div>
                    <div class="font-semibold tabular-nums mt-0.5">{{ $this->formatBytes($this->stats['total_storage_bytes']) }}</div>
                    <div class="text-[10px] text-base-content/40">{{ $this->stats['total_sources'] }} {{ __('backup-dashboard.sources') }} · {{ $this->stats['total_destinations'] }} {{ __('backup-dashboard.destinations') }}</div>
                </div>
            </div>

            <div class="hidden lg:block text-right pl-4 border-l border-base-content/10">
                <div class="text-[10px] text-base-content/50 uppercase">{{ __('backup-dashboard.avg_duration') }}</div>
                <div class="font-semibold tabular-nums mt-0.5 text-lg tracking-tight">
                    @if ($this->stats['avg_duration'])
                        {{ $this->formatDuration($this->stats['avg_duration']) }}
                    @else — @endif
                </div>
                <div class="text-[10px] text-base-content/40">{{ __('backup-dashboard.last_30_days') }}</div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ROW 2: CHART + UPCOMING BACKUPS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Chart - Last 14 Days --}}
        <div class="xl:col-span-2 card bg-base-100 border border-base-content/5">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-sm font-semibold">{{ __('backup-dashboard.chart_title') }}</h2>
                    <div class="flex items-center gap-4">
                        <div class="flex items-center gap-1.5 text-[11px] text-base-content/50">
                            <div class="w-2.5 h-2.5 rounded-sm bg-success"></div>
                            {{ __('backup-dashboard.success') }}
                        </div>
                        <div class="flex items-center gap-1.5 text-[11px] text-base-content/50">
                            <div class="w-2.5 h-2.5 rounded-sm bg-error"></div>
                            {{ __('backup-dashboard.failed') }}
                        </div>
                    </div>
                </div>

                @php
                    $maxVal = max(1, max(array_map(fn($d) => $d['success'] + $d['failed'], $this->chartData)));
                @endphp

                <div class="flex items-end gap-1.5 h-40">
                    @foreach ($this->chartData as $day)
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end group/bar relative">
                            {{-- Tooltip --}}
                            <div class="absolute -top-8 left-1/2 -translate-x-1/2 bg-base-300 text-[10px] px-2 py-1 rounded-lg shadow-lg opacity-0 group-hover/bar:opacity-100 transition-opacity whitespace-nowrap z-10 pointer-events-none border border-base-content/10">
                                <span class="text-success font-semibold">{{ $day['success'] }}</span>
                                <span class="text-base-content/30 mx-0.5">/</span>
                                <span class="text-error font-semibold">{{ $day['failed'] }}</span>
                            </div>

                            {{-- Bar --}}
                            <div class="w-full flex flex-col items-center gap-px flex-1 justify-end">
                                @if ($day['failed'] > 0)
                                    <div class="w-full max-w-8 bg-error rounded-t transition-all duration-500 hover:brightness-110 cursor-default"
                                         style="height: {{ max(4, ($day['failed'] / $maxVal) * 100) }}%"></div>
                                @endif
                                @if ($day['success'] > 0)
                                    <div class="w-full max-w-8 bg-success {{ $day['failed'] > 0 ? '' : 'rounded-t' }} rounded-b transition-all duration-500 hover:brightness-110 cursor-default"
                                         style="height: {{ max(4, ($day['success'] / $maxVal) * 100) }}%"></div>
                                @endif
                                @if ($day['success'] === 0 && $day['failed'] === 0)
                                    <div class="w-full max-w-8 bg-base-content/5 rounded h-1"></div>
                                @endif
                            </div>
                            {{-- Label --}}
                            <span class="text-[9px] tabular-nums {{ $day['is_today'] ? 'text-primary font-bold' : 'text-base-content/30' }}">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        {{-- Upcoming Backups --}}
        <div class="card bg-base-100 border border-base-content/5">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold">{{ __('backup-dashboard.upcoming') }}</h2>
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/30" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>

                <div class="space-y-2.5">
                    @forelse ($this->upcomingBackups as $job)
                        <div class="flex items-center gap-3 p-2.5 rounded-xl bg-base-content/[0.02] hover:bg-base-content/[0.04] transition-colors">
                            <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-medium truncate">{{ $job->name }}</p>
                                <p class="text-[10px] text-base-content/40 tabular-nums mt-0.5">{{ $job->next_run_at->format('d/m H:i') }} &middot; {{ $job->next_run_at->diffForHumans() }}</p>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-6">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 mx-auto text-base-content/15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 012.25-2.25h13.5A2.25 2.25 0 0121 7.5v11.25m-18 0A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75m-18 0v-7.5A2.25 2.25 0 015.25 9h13.5A2.25 2.25 0 0121 11.25v7.5" />
                            </svg>
                            <p class="text-[11px] text-base-content/30 mt-2">{{ __('backup-dashboard.no_upcoming') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    {{-- Impeccable: Avoided repeated card grid. Jobs health now uses a clean, scannable list with left status accent and better rhythm. --}}
    <div>
        <div class="flex items-center justify-between mb-3 px-1">
            <h2 class="text-sm font-semibold tracking-tight">{{ __('backup-dashboard.jobs_health') }}</h2>
            <a href="{{ route('admin.backup.jobs') }}" class="text-[11px] text-primary hover:underline font-medium">{{ __('backup-dashboard.manage') }}</a>
        </div>

        @if ($this->jobsHealth->isEmpty())
            <div class="text-center py-8 text-sm text-base-content/30">
                {{ __('backup-dashboard.no_jobs') }}
            </div>
        @else
            <div class="divide-y divide-base-content/5 rounded-2xl border border-base-content/5 bg-base-100 overflow-hidden">
                @foreach ($this->jobsHealth as $job)
                    @php
                        $lastLog = $job->latestLog;
                        $status = $lastLog?->status ?? 'unknown';
                    @endphp
                    <a href="{{ route('admin.backup.jobs.show', $job) }}" class="flex items-center gap-4 px-4 py-3 hover:bg-base-200/40 transition-colors group {{ !$job->is_active ? 'opacity-50' : '' }}">
                        <div class="flex-shrink-0">
                            @if ($status === 'running')
                                <span class="relative flex h-2.5 w-2.5">
                                    <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-70"></span>
                                    <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-warning"></span>
                                </span>
                            @else
                                <span class="block w-2.5 h-2.5 rounded-full {{ $status === 'success' ? 'bg-success' : ($status === 'failed' ? 'bg-error' : 'bg-base-content/25') }}"></span>
                            @endif
                        </div>

                        <div class="flex-1 min-w-0 flex items-baseline justify-between gap-3">
                            <div class="min-w-0">
                                <span class="font-medium text-[13px] group-hover:text-primary transition-colors">{{ $job->name }}</span>
                                @if ($job->source)
                                    <span class="ml-2 text-[10px] text-base-content/40">{{ $job->source->name }}</span>
                                @endif
                            </div>

                            <div class="text-right text-[10px] tabular-nums text-base-content/40 shrink-0">
                                @if ($lastLog)
                                    {{ $lastLog->finished_at?->diffForHumans() ?? '—' }}
                                @else
                                    {{ __('backup-dashboard.never_short') }}
                                @endif
                            </div>
                        </div>

                        @if (!$job->is_active)
                            <span class="text-[9px] font-medium px-2 py-px rounded bg-base-content/5 text-base-content/40 uppercase">{{ __('backup-dashboard.off') }}</span>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         STORAGE BREAKDOWN + ACTIVITY FEED
    ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-4">

        {{-- Storage Breakdown --}}
        <div class="card bg-base-100 border border-base-content/5">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold">{{ __('backup-dashboard.storage_breakdown') }}</h2>
                    <a href="{{ route('admin.backup.destinations') }}" class="text-[11px] text-primary hover:underline font-medium">{{ __('backup-dashboard.manage') }}</a>
                </div>

                @if (empty($this->storageBreakdown))
                    <div class="text-center py-6">
                        <p class="text-[11px] text-base-content/30">{{ __('backup-dashboard.no_storage') }}</p>
                    </div>
                @else
                    @php
                        $maxBytes = max(1, max(array_column($this->storageBreakdown, 'bytes')));
                        $typeIcons = [
                            's3' => 'text-warning',
                            'ftp' => 'text-info',
                            'local' => 'text-success',
                        ];
                    @endphp
                    <div class="space-y-3">
                        @foreach ($this->storageBreakdown as $storage)
                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <div class="flex items-center gap-2">
                                        <span class="inline-flex px-1.5 py-0.5 rounded text-[9px] font-bold uppercase tracking-wider {{ $typeIcons[$storage['type']] ?? 'text-base-content/50' }} bg-current/10">
                                            {{ $storage['type'] }}
                                        </span>
                                        <span class="text-xs font-medium truncate max-w-32">{{ $storage['name'] }}</span>
                                    </div>
                                    <span class="text-[11px] text-base-content/50 tabular-nums">{{ $this->formatBytes($storage['bytes']) }}</span>
                                </div>
                                <div class="w-full h-1.5 rounded-full bg-base-content/5 overflow-hidden">
                                    <div class="h-full rounded-full bg-primary/60 transition-all duration-700"
                                         style="width: {{ $maxBytes > 0 ? max(2, ($storage['bytes'] / $maxBytes) * 100) : 0 }}%"></div>
                                </div>
                                <p class="text-[10px] text-base-content/30 mt-1">{{ $storage['count'] }} {{ __('backup-dashboard.backup_files') }}</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Activity Feed --}}
        <div class="xl:col-span-2 card bg-base-100 border border-base-content/5">
            <div class="card-body p-5">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold">{{ __('backup-dashboard.recent_logs') }}</h2>
                    <a href="{{ route('admin.backup.logs') }}" class="text-[11px] text-primary hover:underline font-medium">{{ __('backup-dashboard.view_all') }}</a>
                </div>

                <div class="overflow-x-auto -mx-2">
                    <table class="table table-xs">
                        <thead>
                            <tr class="border-b border-base-content/5">
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider pl-2">{{ __('backup-dashboard.col_status') }}</th>
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider">{{ __('backup-dashboard.col_job') }}</th>
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider">{{ __('backup-dashboard.col_source') }}</th>
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider">{{ __('backup-dashboard.col_size') }}</th>
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider">{{ __('backup-dashboard.col_duration') }}</th>
                                <th class="text-[10px] font-semibold text-base-content/30 uppercase tracking-wider pr-2">{{ __('backup-dashboard.col_time') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($this->recentLogs as $log)
                                <tr class="border-b border-base-content/[0.03] hover:bg-base-content/[0.02] transition-colors group">
                                    <td class="pl-2">
                                        @php
                                            $statusBadge = [
                                                'running' => 'bg-warning/10 text-warning',
                                                'success' => 'bg-success/10 text-success',
                                                'failed' => 'bg-error/10 text-error',
                                                'pending' => 'bg-base-content/5 text-base-content/40',
                                                'partial' => 'bg-warning/10 text-warning',
                                            ];
                                            $badgeCls = $statusBadge[$log->status] ?? 'bg-base-content/5 text-base-content/40';
                                        @endphp
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $badgeCls }}">
                                            @if ($log->status === 'running')
                                                <span class="loading loading-spinner" style="width: 8px; height: 8px;"></span>
                                            @endif
                                            {{ __('backup-dashboard.status_' . $log->status) }}
                                        </span>
                                    </td>
                                    <td class="text-xs font-medium max-w-40 truncate">{{ $log->job->name ?? '-' }}</td>
                                    <td class="text-[11px] text-base-content/50 max-w-28 truncate">{{ $log->job->source->name ?? '-' }}</td>
                                    <td class="text-[11px] text-base-content/50 tabular-nums">{{ $log->formatted_size }}</td>
                                    <td class="text-[11px] text-base-content/50 tabular-nums">{{ $log->formatted_duration }}</td>
                                    <td class="text-[11px] text-base-content/40 tabular-nums whitespace-nowrap pr-2">
                                        {{ $log->started_at->diffForHumans() }}
                                        @if ($log->status === 'success' && $log->storage_path)
                                            <a href="{{ route('admin.backup.logs.download', $log) }}" class="inline-flex ml-1 opacity-0 group-hover:opacity-100 transition-opacity text-primary hover:text-primary/80" target="_blank">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6">
                                        <div class="text-center py-8">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-7 h-7 mx-auto text-base-content/15" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                                            </svg>
                                            <p class="text-[11px] text-base-content/30 mt-2">{{ __('backup-dashboard.no_activity') }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- ═══════════════════════════════════════════════════════════
         ROW 5: LAST SUCCESS / LAST FAILURE MINI CARDS
    ═══════════════════════════════════════════════════════════ --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
        {{-- Last Success --}}
        <div class="card bg-base-100 border border-base-content/5">
            <div class="card-body p-4 flex-row items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-dashboard.last_success') }}</p>
                    @if ($this->stats['last_success'])
                        <p class="text-sm font-medium mt-0.5">{{ $this->stats['last_success']->job->name ?? '-' }}</p>
                        <p class="text-[11px] text-base-content/40 mt-0.5">{{ $this->stats['last_success']->finished_at->diffForHumans() }} &middot; {{ $this->stats['last_success']->formatted_size }}</p>
                    @else
                        <p class="text-sm text-base-content/25 mt-0.5">&mdash;</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Last Failure --}}
        <div class="card bg-base-100 border border-base-content/5">
            <div class="card-body p-4 flex-row items-center gap-4">
                <div class="w-10 h-10 rounded-xl bg-error/10 flex items-center justify-center flex-shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-dashboard.last_failure') }}</p>
                    @if ($this->stats['last_failure'])
                        <p class="text-sm font-medium text-error mt-0.5">{{ $this->stats['last_failure']->job->name ?? '-' }}</p>
                        <p class="text-[11px] text-base-content/40 mt-0.5">{{ $this->stats['last_failure']->finished_at->diffForHumans() }}</p>
                    @else
                        <p class="text-sm text-success font-medium mt-0.5">{{ __('backup-dashboard.no_failures') }}</p>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
