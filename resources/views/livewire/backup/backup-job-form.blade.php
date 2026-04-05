<div>
    <form wire:submit="save" class="space-y-5">
        {{-- Name --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.name') }}</span></label>
            <input type="text" wire:model="name" class="input input-bordered rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('backup-job.name') }}" />
            @error('name') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
        </div>

        {{-- Source & Destination --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="form-control">
                <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.source') }}</span></label>
                <select wire:model="backup_source_id" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 focus:border-primary">
                    <option value="">{{ __('backup-job.select_source') }}</option>
                    @foreach ($sources as $source)
                        <option value="{{ $source->id }}">{{ $source->name }} ({{ implode(', ', $source->enabled_types) }})</option>
                    @endforeach
                </select>
                @error('backup_source_id') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
            </div>
            <div class="form-control">
                <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.destination') }}</span></label>
                <select wire:model="backup_storage_destination_id" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 focus:border-primary">
                    <option value="">{{ __('backup-job.select_destination') }}</option>
                    @foreach ($destinations as $dest)
                        <option value="{{ $dest->id }}">{{ $dest->name }} ({{ $dest->type }})</option>
                    @endforeach
                </select>
                @error('backup_storage_destination_id') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Schedule Section --}}
        <div class="rounded-xl border border-info/20 bg-info/[0.03] p-5 space-y-4">
            <div class="flex items-center gap-2 mb-1 justify-between">
                <div class="flex items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                    <h4 class="text-sm font-semibold text-info">{{ __('backup-job.schedule_type') }}</h4>
                </div>
                <div class="form-control">
                    <select wire:model.live="schedule_type" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 w-50">
                        <option value="manual">{{ __('backup-job.schedule_manual') }}</option>
                        <option value="hourly">{{ __('backup-job.schedule_hourly') }}</option>
                        <option value="daily">{{ __('backup-job.schedule_daily') }}</option>
                        <option value="weekly">{{ __('backup-job.schedule_weekly') }}</option>
                        <option value="monthly">{{ __('backup-job.schedule_monthly') }}</option>
                        <option value="custom">{{ __('backup-job.schedule_custom') }}</option>
                    </select>
                    @error('schedule_type') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            @if (in_array($schedule_type, ['daily', 'weekly', 'monthly']))
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-job.schedule_time') }}</span></label>
                    <input type="time" wire:model="schedule_time" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                    @error('schedule_time') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($schedule_type === 'weekly')
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-job.schedule_day_of_week') }}</span></label>
                    <select wire:model="schedule_day_of_week" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10">
                        <option value="">{{ __('backup-job.select_day') }}</option>
                        <option value="0">{{ __('backup-job.day_sunday') }}</option>
                        <option value="1">{{ __('backup-job.day_monday') }}</option>
                        <option value="2">{{ __('backup-job.day_tuesday') }}</option>
                        <option value="3">{{ __('backup-job.day_wednesday') }}</option>
                        <option value="4">{{ __('backup-job.day_thursday') }}</option>
                        <option value="5">{{ __('backup-job.day_friday') }}</option>
                        <option value="6">{{ __('backup-job.day_saturday') }}</option>
                    </select>
                    @error('schedule_day_of_week') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($schedule_type === 'monthly')
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-job.schedule_day_of_month') }}</span></label>
                    <select wire:model="schedule_day_of_month" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10">
                        <option value="">{{ __('backup-job.select_day') }}</option>
                        @for ($i = 1; $i <= 31; $i++)
                            <option value="{{ $i }}">{{ $i }}</option>
                        @endfor
                    </select>
                    @error('schedule_day_of_month') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            @endif

            @if ($schedule_type === 'custom')
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-job.cron_expression') }}</span></label>
                    <input type="text" wire:model.live.debounce.500ms="schedule_cron" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 font-mono" placeholder="0 */6 * * *" />
                    @error('schedule_cron') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    @if ($cronPreview)
                        <span class="text-xs mt-1.5 text-info">{{ $cronPreview }}</span>
                    @endif
                </div>
            @endif
        </div>

        {{-- Retention & Compression --}}
        <div class="grid grid-cols-2 gap-4">
            <div class="form-control">
                <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.retention_count') }}</span></label>
                <input type="number" wire:model="retention_count" class="input input-bordered input-sm rounded-lg bg-base-200/50 border-base-content/10" min="1" max="365" />
                @error('retention_count') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
            </div>
            <div class="form-control">
                <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.compression') }}</span></label>
                <select wire:model="compression" class="select select-bordered select-sm rounded-lg bg-base-200/50 border-base-content/10">
                    <option value="none">{{ __('backup-job.compression_none') }}</option>
                    <option value="gzip">Gzip</option>
                    <option value="zip">Zip</option>
                </select>
                @error('compression') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
            </div>
        </div>

        {{-- Backup Type (Full / Incremental) --}}
        <div class="rounded-xl border border-accent/20 bg-accent/[0.03] p-5 space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-accent" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125v-3.75" /></svg>
                <h4 class="text-sm font-semibold text-accent">{{ __('backup-job.backup_type') }}</h4>
            </div>

            <div class="form-control">
                <select wire:model.live="backup_type" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10">
                    <option value="full">{{ __('backup-job.backup_type_full') }}</option>
                    <option value="incremental">{{ __('backup-job.backup_type_incremental') }}</option>
                </select>
                @error('backup_type') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
            </div>

            @if ($backup_type === 'incremental')
                <div class="rounded-lg bg-accent/5 border border-accent/10 p-3">
                    <p class="text-xs text-base-content/60 mb-3">{{ __('backup-job.incremental_description') }}</p>

                    <div class="form-control flex justify-around">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-job.full_backup_every') }}</span></label>
                        <input type="number" wire:model="full_backup_every" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" min="1" max="365" placeholder="{{ __('backup-job.full_backup_every_placeholder') }}" />
                        @error('full_backup_every') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>
            @endif
        </div>

        {{-- Notifications --}}
        <div class="rounded-xl border border-warning/20 bg-warning/[0.03] p-5 space-y-4">
            <div class="flex items-center gap-2 mb-1">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75v-.7V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" /></svg>
                <h4 class="text-sm font-semibold text-warning">{{ __('backup-job.notifications') }}</h4>
            </div>

            <div class="flex gap-6">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="notify_on_success" class="toggle toggle-sm toggle-primary" />
                    <span class="text-sm">{{ __('backup-job.notify_success') }}</span>
                </label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" wire:model.live="notify_on_failure" class="toggle toggle-sm toggle-primary" />
                    <span class="text-sm">{{ __('backup-job.notify_failure') }}</span>
                </label>
            </div>

            @if ($notify_on_success || $notify_on_failure)
                <div class="space-y-3">
                    <label class="label pb-0 pt-0">
                        <span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-job.notification_emails') }}</span>
                    </label>

                    {{-- Email list --}}
                    @if(count($notification_emails) > 0)
                        <div class="space-y-1.5">
                            @foreach($notification_emails as $i => $email)
                                <div class="flex items-center gap-2 bg-base-100 border border-base-content/10 rounded-lg px-3 py-2">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-base-content/40 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                    <span class="text-sm flex-1 truncate">{{ $email }}</span>
                                    <button
                                        type="button"
                                        wire:click="removeEmail({{ $i }})"
                                        class="btn btn-xs btn-ghost btn-circle text-base-content/40 hover:text-error hover:bg-error/10"
                                        title="{{ __('backup-job.email_remove') }}"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif
                    @error('notification_emails') <span class="text-error text-xs">{{ $message }}</span> @enderror

                    {{-- Add new email --}}
                    <datalist id="registered-users-list">
                        @foreach($this->registeredUsers as $user)
                            <option value="{{ $user['email'] }}">{{ $user['name'] }} &lt;{{ $user['email'] }}&gt;</option>
                        @endforeach
                    </datalist>

                    <div class="flex gap-2">
                        <input
                            type="email"
                            wire:model="newEmail"
                            wire:keydown.enter.prevent="addEmail"
                            list="registered-users-list"
                            class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 flex-1 @error('newEmail') input-error @enderror"
                            placeholder="{{ __('backup-job.email_add_placeholder') }}"
                        />
                        <button
                            type="button"
                            wire:click="addEmail"
                            wire:loading.attr="disabled"
                            wire:target="addEmail"
                            class="btn btn-sm btn-outline rounded-lg gap-1.5"
                            title="{{ __('backup-job.email_add') }}"
                        >
                            <span wire:loading wire:target="addEmail" class="loading loading-spinner loading-xs"></span>
                            <span wire:loading.remove wire:target="addEmail">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            </span>
                            <span class="text-xs font-medium">{{ __('backup-job.email_add') }}</span>
                        </button>
                        <button
                            type="button"
                            wire:click="sendTestEmail"
                            wire:loading.attr="disabled"
                            wire:target="sendTestEmail"
                            @if(count($notification_emails) === 0) disabled @endif
                            class="btn btn-sm rounded-lg gap-1.5 {{ count($notification_emails) === 0 ? 'btn-disabled' : ($testEmailState === 'success' ? 'btn-success' : ($testEmailState === 'error' ? 'btn-error' : 'btn-outline btn-warning')) }}"
                            title="{{ __('backup-job.test_email_btn_title') }}"
                        >
                            <span wire:loading wire:target="sendTestEmail" class="loading loading-spinner loading-xs"></span>
                            <span wire:loading.remove wire:target="sendTestEmail">
                                @if($testEmailState === 'success')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                                @elseif($testEmailState === 'error')
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                                @endif
                            </span>
                            <span class="text-xs font-medium">{{ __('backup-job.test_email_btn') }}</span>
                        </button>
                    </div>
                    @error('newEmail') <span class="text-error text-xs mt-0.5">{{ $message }}</span> @enderror

                    {{-- Test feedback --}}
                    @if($testEmailState === 'success')
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-success flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" /></svg>
                            <span class="text-success text-xs">{{ __('backup-job.test_email_sent') }}</span>
                        </div>
                    @elseif($testEmailState === 'error')
                        <div class="flex items-center gap-1.5">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 text-error flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span class="text-error text-xs">{{ __('backup-job.test_email_failed') }}</span>
                        </div>
                    @endif
                </div>
            @endif
        </div>

        {{-- Active Toggle --}}
        <label class="flex items-center gap-3 cursor-pointer py-2">
            <input type="checkbox" wire:model="is_active" class="toggle toggle-sm toggle-success" />
            <span class="text-sm font-medium">{{ __('backup-job.is_active') }}</span>
        </label>

        {{-- Submit --}}
        <div class="flex justify-end pt-2 border-t border-base-content/5">
            <button type="submit" class="btn btn-primary btn-sm rounded-xl gap-2 px-6" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                {{ $jobId ? __('backup-job.update') : __('backup-job.save') }}
            </button>
        </div>
    </form>
</div>
