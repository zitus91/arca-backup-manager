<div class="space-y-8" wire:poll.15s>
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('backup-dashboard.title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('backup-dashboard.subtitle') }}</p>
    </div>

    {{-- Stats Grid --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4">
        {{-- Active Jobs --}}
        <div class="card bg-base-100 border border-base-content/5 hover:border-primary/20 transition-all duration-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-dashboard.active_jobs') }}</p>
                        <p class="text-3xl font-bold mt-1 tabular-nums">{{ $this->stats['active_jobs'] }}</p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-primary/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Today Stats --}}
        <div class="card bg-base-100 border border-base-content/5 hover:border-success/20 transition-all duration-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-dashboard.today') }}</p>
                        <div class="flex items-baseline gap-3 mt-1">
                            <span class="text-3xl font-bold tabular-nums text-success">{{ $this->stats['today_success'] }}</span>
                            @if ($this->stats['today_failed'] > 0)
                                <span class="text-lg font-semibold text-error tabular-nums">/{{ $this->stats['today_failed'] }}</span>
                            @endif
                        </div>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-success/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
                @if ($this->stats['running'] > 0)
                    <div class="mt-2 flex items-center gap-2 text-warning text-xs font-medium">
                        <span class="loading loading-spinner loading-xs"></span>
                        {{ $this->stats['running'] }} {{ __('backup-dashboard.running') }}
                    </div>
                @endif
            </div>
        </div>

        {{-- Last Success --}}
        <div class="card bg-base-100 border border-base-content/5 hover:border-info/20 transition-all duration-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-dashboard.last_success') }}</p>
                        <p class="text-sm font-semibold mt-2">
                            @if ($this->stats['last_success'])
                                {{ $this->stats['last_success']->finished_at->diffForHumans() }}
                            @else
                                <span class="text-base-content/30">&mdash;</span>
                            @endif
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-info/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        {{-- Last Failure --}}
        <div class="card bg-base-100 border border-base-content/5 hover:border-error/20 transition-all duration-300">
            <div class="card-body p-5">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-dashboard.last_failure') }}</p>
                        <p class="text-sm font-semibold mt-2">
                            @if ($this->stats['last_failure'])
                                <span class="text-error">{{ $this->stats['last_failure']->finished_at->diffForHumans() }}</span>
                            @else
                                <span class="text-success">{{ __('backup-dashboard.no_failures') }}</span>
                            @endif
                        </p>
                    </div>
                    <div class="w-12 h-12 rounded-2xl bg-error/10 flex items-center justify-center">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" />
                        </svg>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Chart + Activity --}}
    <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        {{-- Chart - Last 7 Days --}}
        <div class="xl:col-span-2 card bg-base-100 border border-base-content/5">
            <div class="card-body p-6">
                <h2 class="text-sm font-semibold mb-6">{{ __('backup-dashboard.chart_title') }}</h2>

                @php
                    $maxVal = max(1, max(array_column($this->chartData, 'success')) + max(array_column($this->chartData, 'failed')));
                @endphp

                <div class="flex items-end gap-3 h-48">
                    @foreach ($this->chartData as $day)
                        <div class="flex-1 flex flex-col items-center gap-1 h-full justify-end">
                            {{-- Bar Stack --}}
                            <div class="w-full flex flex-col items-center gap-0.5 flex-1 justify-end">
                                @if ($day['failed'] > 0)
                                    <div class="w-full max-w-10 bg-error/80 rounded-t transition-all duration-500" style="height: {{ ($day['failed'] / $maxVal) * 100 }}%"></div>
                                @endif
                                @if ($day['success'] > 0)
                                    <div class="w-full max-w-10 bg-success/80 {{ $day['failed'] > 0 ? '' : 'rounded-t' }} rounded-b transition-all duration-500" style="height: {{ ($day['success'] / $maxVal) * 100 }}%"></div>
                                @endif
                                @if ($day['success'] === 0 && $day['failed'] === 0)
                                    <div class="w-full max-w-10 bg-base-content/5 rounded h-1"></div>
                                @endif
                            </div>
                            {{-- Count --}}
                            <span class="text-[10px] font-semibold tabular-nums text-base-content/60">{{ $day['success'] + $day['failed'] }}</span>
                            {{-- Label --}}
                            <span class="text-[10px] text-base-content/40">{{ $day['label'] }}</span>
                        </div>
                    @endforeach
                </div>

                {{-- Legend --}}
                <div class="flex items-center gap-6 mt-4 pt-4 border-t border-base-content/5">
                    <div class="flex items-center gap-2 text-xs text-base-content/60">
                        <div class="w-3 h-3 rounded bg-success/80"></div>
                        {{ __('backup-dashboard.success') }}
                    </div>
                    <div class="flex items-center gap-2 text-xs text-base-content/60">
                        <div class="w-3 h-3 rounded bg-error/80"></div>
                        {{ __('backup-dashboard.failed') }}
                    </div>
                </div>
            </div>
        </div>

        {{-- Activity Feed --}}
        <div class="card bg-base-100 border border-base-content/5">
            <div class="card-body p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-sm font-semibold">{{ __('backup-dashboard.recent_logs') }}</h2>
                    <a href="{{ route('admin.backup.logs') }}" class="text-xs text-primary hover:underline">{{ __('backup-dashboard.view_all') }}</a>
                </div>

                <div class="space-y-3">
                    @forelse ($this->recentLogs->take(8) as $log)
                        <div class="flex items-start gap-3 group">
                            {{-- Status Dot --}}
                            <div class="mt-1.5 flex-shrink-0">
                                @switch($log->status)
                                    @case('running')
                                        <span class="relative flex h-2.5 w-2.5">
                                            <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-warning opacity-75"></span>
                                            <span class="relative inline-flex rounded-full h-2.5 w-2.5 bg-warning"></span>
                                        </span>
                                        @break
                                    @case('success')
                                        <div class="w-2.5 h-2.5 rounded-full bg-success"></div>
                                        @break
                                    @case('failed')
                                        <div class="w-2.5 h-2.5 rounded-full bg-error"></div>
                                        @break
                                    @case('pending')
                                        <div class="w-2.5 h-2.5 rounded-full bg-base-content/20"></div>
                                        @break
                                    @case('partial')
                                        <div class="w-2.5 h-2.5 rounded-full bg-warning"></div>
                                        @break
                                @endswitch
                            </div>

                            {{-- Content --}}
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate">{{ $log->job->name ?? '-' }}</p>
                                <div class="flex items-center gap-2 mt-0.5">
                                    <span class="text-[11px] text-base-content/40">{{ $log->started_at->diffForHumans() }}</span>
                                    @if ($log->formatted_size && $log->status === 'success')
                                        <span class="text-[11px] text-base-content/30">&middot;</span>
                                        <span class="text-[11px] text-base-content/40">{{ $log->formatted_size }}</span>
                                    @endif
                                </div>
                            </div>

                            {{-- Download --}}
                            @if ($log->status === 'success' && $log->storage_path)
                                <a href="{{ route('admin.backup.logs.download', $log) }}" class="flex-shrink-0 btn btn-ghost btn-xs btn-square rounded-lg opacity-0 group-hover:opacity-100 transition-opacity tooltip tooltip-left" data-tip="{{ __('backup-dashboard.download') }}" target="_blank">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5M16.5 12L12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                                </a>
                            @endif

                            {{-- Status Badge --}}
                            <div class="flex-shrink-0">
                                @switch($log->status)
                                    @case('running')
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning/10 text-warning">
                                            <span class="loading loading-spinner" style="width: 10px; height: 10px;"></span>
                                            {{ __('backup-dashboard.status_running') }}
                                        </span>
                                        @break
                                    @case('success')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/10 text-success">{{ __('backup-dashboard.status_success') }}</span>
                                        @break
                                    @case('failed')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-error/10 text-error">{{ __('backup-dashboard.status_failed') }}</span>
                                        @break
                                    @case('pending')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-base-content/5 text-base-content/50">{{ __('backup-dashboard.status_pending') }}</span>
                                        @break
                                    @case('partial')
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning/10 text-warning">{{ __('backup-dashboard.status_partial') }}</span>
                                        @break
                                @endswitch
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-8">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 mx-auto text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
                            </svg>
                            <p class="text-xs text-base-content/30 mt-2">{{ __('backup-dashboard.no_activity') }}</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
