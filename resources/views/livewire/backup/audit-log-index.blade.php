<div class="space-y-6">
    {{-- Page Header --}}
    <div>
        <h1 class="text-2xl font-bold tracking-tight">{{ __('audit-log.title') }}</h1>
        <p class="text-base-content/50 text-sm mt-1">{{ __('audit-log.subtitle') }}</p>
    </div>

    {{-- Filters --}}
    <div class="card bg-base-100 border border-base-content/5 rounded-xl">
        <div class="card-body p-4">
            <div class="flex flex-wrap items-end gap-4">
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('audit-log.filter_action') }}</span></label>
                    <select wire:model.live="filterAction" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('audit-log.all') }}</option>
                        @foreach ($actions as $action)
                            <option value="{{ $action }}">{{ ucfirst($action) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('audit-log.filter_user') }}</span></label>
                    <select wire:model.live="filterUserId" class="select select-bordered select-sm rounded-lg bg-base-200 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('audit-log.all') }}</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('audit-log.filter_date_from') }}</span></label>
                    <input type="date" wire:model.live="filterDateFrom" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary" />
                </div>
                <div class="form-control w-40">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('audit-log.filter_date_to') }}</span></label>
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
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_date') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_user') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_action') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_description') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_model') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('audit-log.col_ip') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($logs as $log)
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="audit-{{ $log->id }}">
                            <td class="text-xs text-base-content/60 tabular-nums whitespace-nowrap">{{ $log->created_at->format('d/m/Y H:i:s') }}</td>
                            <td class="text-sm">{{ $log->user->name ?? __('audit-log.system') }}</td>
                            <td>
                                @php
                                    $colors = [
                                        'created' => 'bg-success/10 text-success',
                                        'updated' => 'bg-info/10 text-info',
                                        'deleted' => 'bg-error/10 text-error',
                                        'login' => 'bg-primary/10 text-primary',
                                        'logout' => 'bg-base-content/5 text-base-content/50',
                                        'run' => 'bg-warning/10 text-warning',
                                    ];
                                    $badgeClass = $colors[$log->action] ?? 'bg-base-content/5 text-base-content/60';
                                @endphp
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold {{ $badgeClass }}">
                                    {{ ucfirst($log->action) }}
                                </span>
                            </td>
                            <td class="text-sm text-base-content/70 max-w-xs truncate">{{ $log->description }}</td>
                            <td class="text-xs text-base-content/50">{{ $log->model_label }}</td>
                            <td class="text-xs text-base-content/40 tabular-nums">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                                        </svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('audit-log.empty') }}</p>
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
</div>
