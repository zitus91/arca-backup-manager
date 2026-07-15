<div class="space-y-6" wire:poll.30s>
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('system.title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('system.subtitle') }}</p>
    </div>

    {{-- Global stat tiles --}}
    @php
        $rate = $this->stats['success_rate'];
        $rateColor = $rate >= 90 ? 'text-success' : ($rate >= 70 ? 'text-warning' : 'text-error');
        $tiles = [
            ['label' => __('system.users'), 'value' => $this->stats['total_users']],
            ['label' => __('system.jobs'), 'value' => $this->stats['active_jobs'].' / '.$this->stats['total_jobs']],
            ['label' => __('system.sources'), 'value' => $this->stats['total_sources']],
            ['label' => __('system.destinations'), 'value' => $this->stats['total_destinations']],
            ['label' => __('system.total_backups'), 'value' => $this->stats['total_backups']],
            ['label' => __('system.running'), 'value' => $this->stats['running']],
            ['label' => __('system.failed_today'), 'value' => $this->stats['today_failed']],
            ['label' => __('system.storage'), 'value' => $this->formatBytes($this->stats['total_storage_bytes'])],
        ];
    @endphp

    <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-5 gap-3">
        <div class="card bg-base-100 border border-base-content/5 rounded-xl">
            <div class="card-body p-4 items-center text-center gap-1">
                <span class="text-3xl font-semibold tabular-nums tracking-tight {{ $rateColor }}">{{ $rate }}%</span>
                <span class="text-[11px] text-base-content/50 uppercase tracking-wide">{{ __('system.success_rate') }}</span>
                <span class="text-[10px] text-base-content/30">{{ __('system.last_30_days') }}</span>
            </div>
        </div>
        @foreach ($tiles as $tile)
            <div class="card bg-base-100 border border-base-content/5 rounded-xl">
                <div class="card-body p-4 gap-1">
                    <span class="text-xl font-semibold tabular-nums tracking-tight">{{ $tile['value'] }}</span>
                    <span class="text-[11px] text-base-content/50 uppercase tracking-wide">{{ $tile['label'] }}</span>
                </div>
            </div>
        @endforeach
    </div>

    {{-- Per-user breakdown --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-base-content/5">
            <h2 class="text-sm font-semibold">{{ __('system.per_user') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="border-b border-base-content/5">
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_user') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_role') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('system.col_jobs') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('system.col_sources') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('system.col_destinations') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('system.col_storage') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_last_backup') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->perUser as $row)
                        <tr class="border-b border-base-content/5">
                            <td>
                                <div class="text-sm font-medium">{{ $row['name'] }}</div>
                                <div class="text-[11px] text-base-content/40">{{ $row['email'] }}</div>
                            </td>
                            <td>
                                <span class="text-[10px] font-semibold px-1.5 py-0.5 rounded {{ $row['role'] === 'admin' ? 'bg-primary/10 text-primary' : 'bg-base-content/5 text-base-content/50' }}">
                                    {{ $row['role'] === 'admin' ? __('system.role_admin') : __('system.role_standard') }}
                                </span>
                            </td>
                            <td class="text-right tabular-nums text-sm">{{ $row['jobs'] }}</td>
                            <td class="text-right tabular-nums text-sm">{{ $row['sources'] }}</td>
                            <td class="text-right tabular-nums text-sm">{{ $row['destinations'] }}</td>
                            <td class="text-right tabular-nums text-sm">{{ $this->formatBytes($row['storage_bytes']) }}</td>
                            <td class="text-[11px] text-base-content/50 tabular-nums">
                                {{ $row['last_backup'] ? \Illuminate\Support\Carbon::parse($row['last_backup'])->diffForHumans() : __('system.never') }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    {{-- Recent activity across all users --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl overflow-hidden">
        <div class="px-5 py-4 border-b border-base-content/5">
            <h2 class="text-sm font-semibold">{{ __('system.recent_activity') }}</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="table table-sm">
                <thead>
                    <tr class="border-b border-base-content/5">
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_owner') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_job') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_status') }}</th>
                        <th class="text-[10px] font-semibold text-base-content/40 uppercase tracking-wider">{{ __('system.col_when') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $statusBadge = [
                            'running' => 'bg-warning/10 text-warning',
                            'success' => 'bg-success/10 text-success',
                            'failed' => 'bg-error/10 text-error',
                            'pending' => 'bg-base-content/5 text-base-content/40',
                            'partial' => 'bg-warning/10 text-warning',
                        ];
                    @endphp
                    @forelse ($this->recentLogs as $log)
                        <tr class="border-b border-base-content/5">
                            <td class="text-sm">{{ $log->job?->user?->name ?? '—' }}</td>
                            <td class="text-sm font-medium">{{ $log->job?->name ?? '—' }}</td>
                            <td>
                                <span class="inline-flex px-2 py-0.5 rounded-md text-[10px] font-semibold {{ $statusBadge[$log->status] ?? 'bg-base-content/5 text-base-content/40' }}">
                                    {{ __('backup-dashboard.status_'.$log->status) }}
                                </span>
                            </td>
                            <td class="text-[11px] text-base-content/40 tabular-nums whitespace-nowrap">{{ $log->started_at?->diffForHumans() }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-8 text-sm text-base-content/30">{{ __('system.no_activity') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
