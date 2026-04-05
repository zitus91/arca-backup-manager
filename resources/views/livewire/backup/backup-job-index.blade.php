<div class="space-y-6">
    {{-- Page Header --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h1 class="text-2xl font-bold tracking-tight">{{ __('backup-job.title') }}</h1>
            <p class="text-base-content/50 text-sm mt-1">{{ __('backup-job.subtitle') }}</p>
        </div>
        <button wire:click="openCreate" class="btn btn-primary btn-sm gap-2 rounded-xl shadow-lg shadow-primary/20">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            {{ __('backup-job.add') }}
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
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.filter_status') }}</span></label>
                    <select wire:model.live="filterStatus" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-job.all') }}</option>
                        <option value="active">{{ __('backup-job.active') }}</option>
                        <option value="inactive">{{ __('backup-job.inactive') }}</option>
                    </select>
                </div>
                <div class="form-control w-44">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.filter_schedule') }}</span></label>
                    <select wire:model.live="filterScheduleType" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10 focus:border-primary">
                        <option value="">{{ __('backup-job.all') }}</option>
                        <option value="manual">{{ __('backup-job.schedule_manual') }}</option>
                        <option value="hourly">{{ __('backup-job.schedule_hourly') }}</option>
                        <option value="daily">{{ __('backup-job.schedule_daily') }}</option>
                        <option value="weekly">{{ __('backup-job.schedule_weekly') }}</option>
                        <option value="monthly">{{ __('backup-job.schedule_monthly') }}</option>
                        <option value="custom">{{ __('backup-job.schedule_custom') }}</option>
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
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_name') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_source') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_destination') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_schedule') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_type') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_last_run') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-job.col_status') }}</th>
                        <th class="text-xs font-semibold text-base-content/40 uppercase tracking-wider text-right">{{ __('backup-job.col_actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($jobs as $job)
                        <tr class="border-b border-base-content/5 hover:bg-base-content/[0.02] transition-colors" wire:key="job-{{ $job->id }}">
                            <td>
                                <div class="flex items-center gap-3">
                                    <div class="w-9 h-9 rounded-xl bg-primary/10 flex items-center justify-center flex-shrink-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </div>
                                    <div>
                                        <span class="font-medium text-sm">{{ $job->name }}</span>
                                        @if ($job->is_active)
                                            <span class="inline-flex items-center gap-1 ml-2">
                                                <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </td>
                            <td class="text-sm text-base-content/70">{{ $job->source->name ?? '-' }}</td>
                            <td class="text-sm text-base-content/70">{{ $job->destination->name ?? '-' }}</td>
                            <td>
                                <span class="inline-flex px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/60">{{ $job->schedule_type }}</span>
                            </td>
                            <td>
                                @if ($job->backup_type === 'incremental')
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-info/10 text-info">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 7.5L7.5 3m0 0L12 7.5M7.5 3v13.5m13.5 0L16.5 21m0 0L12 16.5m4.5 4.5V7.5" /></svg>
                                        {{ __('backup-job.backup_type_incremental') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/60">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" /></svg>
                                        {{ __('backup-job.backup_type_full') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                @if ($job->latestLog)
                                    <div class="flex flex-col gap-0.5">
                                        <span class="text-xs text-base-content/60">{{ $job->latestLog->started_at->format('d/m/Y H:i') }}</span>
                                        @switch($job->latestLog->status)
                                            @case('success')
                                                <span class="inline-flex w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-success/10 text-success">{{ __('backup-job.status_success') }}</span>
                                                @break
                                            @case('failed')
                                                <span class="inline-flex w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-error/10 text-error">{{ __('backup-job.status_failed') }}</span>
                                                @break
                                            @case('running')
                                                <span class="inline-flex w-fit items-center gap-1 px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning/10 text-warning">
                                                    <span class="loading loading-spinner" style="width:10px;height:10px"></span>
                                                    {{ __('backup-job.status_running') }}
                                                </span>
                                                @break
                                            @case('pending')
                                                <span class="inline-flex w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-base-content/5 text-base-content/50">{{ __('backup-job.status_pending') }}</span>
                                                @break
                                            @case('partial')
                                                <span class="inline-flex w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-warning/10 text-warning">{{ __('backup-job.status_partial') }}</span>
                                                @break
                                            @case('cancelled')
                                                <span class="inline-flex w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-base-content/10 text-base-content/50">{{ __('backup-job.status_cancelled') }}</span>
                                                @break
                                        @endswitch
                                    </div>
                                @else
                                    <span class="text-base-content/30">&mdash;</span>
                                @endif
                            </td>
                            <td>
                                @if ($job->is_active)
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success">
                                        <span class="w-1.5 h-1.5 rounded-full bg-success"></span>
                                        {{ __('backup-job.active') }}
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-lg text-xs font-semibold bg-base-content/5 text-base-content/40">
                                        <span class="w-1.5 h-1.5 rounded-full bg-base-content/30"></span>
                                        {{ __('backup-job.inactive') }}
                                    </span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center justify-end gap-1">
                                    @if ($job->latestLog && in_array($job->latestLog->status, ['running', 'pending']))
                                        <button wire:click="confirmCancelJob({{ $job->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg text-warning/80 hover:text-warning hover:bg-warning/10 tooltip tooltip-left" data-tip="{{ __('backup-job.cancel_job') }}"
                                            wire:loading.attr="disabled" wire:target="cancelJobConfirmed">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 7.5A2.25 2.25 0 017.5 5.25h9a2.25 2.25 0 012.25 2.25v9a2.25 2.25 0 01-2.25 2.25h-9a2.25 2.25 0 01-2.25-2.25v-9z" /></svg>
                                        </button>
                                    @else
                                        <button wire:click="runNow({{ $job->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg text-accent tooltip tooltip-left" data-tip="{{ __('backup-job.run_now') }}"
                                            wire:loading.attr="disabled" wire:target="runNow({{ $job->id }})">
                                            <span wire:loading wire:target="runNow({{ $job->id }})" class="loading loading-spinner loading-xs"></span>
                                            <span wire:loading.remove wire:target="runNow({{ $job->id }})">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.348a1.125 1.125 0 010 1.971l-11.54 6.347a1.125 1.125 0 01-1.667-.985V5.653z" /></svg>
                                            </span>
                                        </button>
                                    @endif
                                    <button wire:click="openEdit({{ $job->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('backup-job.edit') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button wire:click="confirmDelete({{ $job->id }})" class="btn btn-ghost btn-xs btn-square rounded-lg text-error/70 hover:text-error hover:bg-error/10 tooltip tooltip-left" data-tip="{{ __('backup-job.delete') }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8">
                                <div class="text-center py-12">
                                    <div class="w-16 h-16 mx-auto rounded-2xl bg-base-content/5 flex items-center justify-center mb-4">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8 text-base-content/20" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </div>
                                    <p class="text-sm text-base-content/40">{{ __('backup-job.empty') }}</p>
                                    <button wire:click="openCreate" class="btn btn-primary btn-sm mt-4 rounded-xl">{{ __('backup-job.add') }}</button>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($jobs->hasPages())
            <div class="px-6 py-4 border-t border-base-content/5">
                {{ $jobs->links() }}
            </div>
        @endif
    </div>

    {{-- Modal Form --}}
    @if ($showForm)
        <div class="modal modal-open">
            <div class="modal-box max-w-3xl bg-base-100 border border-base-content/10 rounded-2xl p-0">
                <div class="flex items-center justify-between px-6 py-4 border-b border-base-content/5">
                    <h3 class="font-bold text-lg">
                        {{ $editId ? __('backup-job.edit_title') : __('backup-job.create_title') }}
                    </h3>
                    <button wire:click="closeForm" class="btn btn-ghost btn-sm btn-square rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                    </button>
                </div>
                <div class="px-6 py-5">
                    <livewire:backup.backup-job-form :jobId="$editId" :key="'form-'.($editId ?? 'new')" />
                </div>
            </div>
            <div class="modal-backdrop bg-black/60 backdrop-blur-sm" wire:click="closeForm"></div>
        </div>
    @endif

    {{-- Confirm Delete Modal --}}
    <x-confirm-modal
        :show="$confirmingDeleteId !== null"
        :message="__('backup-job.confirm_delete')"
    />

    {{-- Confirm Cancel Modal --}}
    <x-confirm-modal
        :show="$confirmingCancelId !== null"
        :title="__('backup-job.confirm_cancel_title')"
        :message="__('backup-job.confirm_cancel')"
        :confirmLabel="__('backup-job.cancel_confirm_btn')"
        confirmAction="cancelJobConfirmed"
        cancelAction="dismissCancelConfirm"
    />
</div>
