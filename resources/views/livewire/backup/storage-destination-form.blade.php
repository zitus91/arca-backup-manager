<div>
    <form wire:submit="save" class="space-y-5">
        {{-- Name --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-storage-destination.name') }}</span></label>
            <input type="text" wire:model="name" class="input input-bordered rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('backup-storage-destination.name_placeholder') }}" />
            @error('name') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
        </div>

        {{-- Type Radio Cards --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-storage-destination.type_label') }}</span></label>
            <div class="grid grid-cols-3 gap-3">
                {{-- S3 --}}
                <label class="relative cursor-pointer">
                    <input type="radio" wire:model.live="type" value="s3" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-info peer-checked:bg-info/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-info/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Amazon S3</p>
                            <p class="text-xs text-base-content/40">{{ __('backup-storage-destination.s3_desc') }}</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all {{ $type === 's3' ? 'border-info bg-info' : 'border-base-content/20' }}">
                                @if ($type === 's3')
                                    <div class="w-1.5 h-1.5 rounded-full bg-info-content"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>

                {{-- FTP --}}
                <label class="relative cursor-pointer">
                    <input type="radio" wire:model.live="type" value="ftp" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-warning peer-checked:bg-warning/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">FTP / SFTP</p>
                            <p class="text-xs text-base-content/40">{{ __('backup-storage-destination.ftp_desc') }}</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all {{ $type === 'ftp' ? 'border-warning bg-warning' : 'border-base-content/20' }}">
                                @if ($type === 'ftp')
                                    <div class="w-1.5 h-1.5 rounded-full bg-warning-content"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Local --}}
                <label class="relative cursor-pointer">
                    <input type="radio" wire:model.live="type" value="local" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-success peer-checked:bg-success/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">{{ __('backup-storage-destination.local') }}</p>
                            <p class="text-xs text-base-content/40">{{ __('backup-storage-destination.local_desc') }}</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded-full border-2 flex items-center justify-center transition-all {{ $type === 'local' ? 'border-success bg-success' : 'border-base-content/20' }}">
                                @if ($type === 'local')
                                    <div class="w-1.5 h-1.5 rounded-full bg-success-content"></div>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            @error('type') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
        </div>

        {{-- S3 Fields --}}
        @if ($type === 's3')
            <div class="rounded-xl border border-info/20 bg-info/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-info" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" /></svg>
                    <h4 class="text-sm font-semibold text-info">{{ __('backup-storage-destination.s3_config') }}</h4>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.s3_endpoint') }}</span></label>
                    <input type="text" wire:model="s3_endpoint" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" placeholder="{{ __('backup-storage-destination.s3_endpoint_placeholder') }}" />
                    <p class="text-[10px] text-base-content/40 mt-1">{{ __('backup-storage-destination.s3_endpoint_hint') }}</p>
                    @error('s3_endpoint') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.s3_bucket') }}</span></label>
                        <input type="text" wire:model="s3_bucket" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('s3_bucket') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.s3_region') }}</span></label>
                        <input type="text" wire:model="s3_region" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" placeholder="us-east-1" />
                        @error('s3_region') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.s3_access_key') }}</span></label>
                        <input type="password" wire:model="s3_access_key" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('s3_access_key') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.s3_secret_key') }}</span></label>
                        <input type="password" wire:model="s3_secret_key" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('s3_secret_key') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                {{-- Test S3 Connection --}}
                <div class="pt-2 border-t border-info/10">
                    <button type="button" wire:click="testS3Connection" class="btn btn-sm btn-outline btn-info rounded-lg gap-2" wire:loading.attr="disabled" wire:target="testS3Connection">
                        <span wire:loading wire:target="testS3Connection" class="loading loading-spinner loading-xs"></span>
                        <span wire:loading.remove wire:target="testS3Connection">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                        </span>
                        {{ __('backup-storage-destination.test') }}
                    </button>

                    @if ($s3_connection_status === 'success')
                        <div class="mt-3 flex items-center gap-2 text-success text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $s3_connection_message }}
                        </div>
                    @elseif ($s3_connection_status === 'failed')
                        <div class="mt-3 flex items-start gap-2 text-error text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span>{{ $s3_connection_message }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- FTP Fields --}}
        @if ($type === 'ftp')
            <div class="rounded-xl border border-warning/20 bg-warning/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 005.25 21h13.5A2.25 2.25 0 0021 18.75V16.5m-13.5-9L12 3m0 0l4.5 4.5M12 3v13.5" /></svg>
                    <h4 class="text-sm font-semibold text-warning">{{ __('backup-storage-destination.ftp_config') }}</h4>
                </div>

                <div class="grid grid-cols-3 gap-4">
                    <div class="form-control col-span-2">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.ftp_host') }}</span></label>
                        <input type="text" wire:model="ftp_host" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('ftp_host') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.ftp_port') }}</span></label>
                        <input type="number" wire:model="ftp_port" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('ftp_port') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.ftp_username') }}</span></label>
                        <input type="text" wire:model="ftp_username" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('ftp_username') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.ftp_password') }}</span></label>
                        <input type="password" wire:model="ftp_password" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('ftp_password') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.ftp_root_path') }}</span></label>
                    <input type="text" wire:model="ftp_root_path" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                    @error('ftp_root_path') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="flex gap-6 pt-1">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="ftp_passive" class="toggle toggle-sm toggle-warning" />
                        <span class="text-sm">{{ __('backup-storage-destination.ftp_passive') }}</span>
                    </label>
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="ftp_ssl" class="toggle toggle-sm toggle-warning" />
                        <span class="text-sm">{{ __('backup-storage-destination.ftp_ssl') }}</span>
                    </label>
                </div>

                {{-- Test FTP Connection --}}
                <div class="pt-2 border-t border-warning/10">
                    <button type="button" wire:click="testFtpConnection" class="btn btn-sm btn-outline btn-warning rounded-lg gap-2" wire:loading.attr="disabled" wire:target="testFtpConnection">
                        <span wire:loading wire:target="testFtpConnection" class="loading loading-spinner loading-xs"></span>
                        <span wire:loading.remove wire:target="testFtpConnection">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                        </span>
                        {{ __('backup-storage-destination.test') }}
                    </button>

                    @if ($ftp_connection_status === 'success')
                        <div class="mt-3 flex items-center gap-2 text-success text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $ftp_connection_message }}
                        </div>
                    @elseif ($ftp_connection_status === 'failed')
                        <div class="mt-3 flex items-start gap-2 text-error text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span>{{ $ftp_connection_message }}</span>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        {{-- Local Fields --}}
        @if ($type === 'local')
            <div class="rounded-xl border border-success/20 bg-success/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                    <h4 class="text-sm font-semibold text-success">{{ __('backup-storage-destination.local_config') }}</h4>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-storage-destination.local_path') }}</span></label>
                    <div class="flex gap-2">
                        <input type="text" wire:model="local_path" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 flex-1 font-mono text-xs" placeholder="{{ __('backup-storage-destination.local_path_placeholder') }}" />
                        <button type="button" wire:click="checkLocalPath" class="btn btn-sm btn-outline btn-success rounded-lg gap-1 px-3" wire:loading.attr="disabled" wire:target="checkLocalPath">
                            <span wire:loading wire:target="checkLocalPath" class="loading loading-spinner loading-xs"></span>
                            <span wire:loading.remove wire:target="checkLocalPath">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            </span>
                            {{ __('backup-storage-destination.check') }}
                        </button>
                    </div>
                    @error('local_path') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                @if ($local_path_status === 'success')
                    <div class="flex items-center gap-2 text-success text-xs font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $local_path_message }}
                    </div>
                @elseif ($local_path_status === 'failed')
                    <div class="flex items-start gap-2 text-error text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <span>{{ $local_path_message }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Active Toggle --}}
        <label class="flex items-center gap-3 cursor-pointer py-2">
            <input type="checkbox" wire:model="is_active" class="toggle toggle-sm toggle-success" />
            <span class="text-sm font-medium">{{ __('backup-storage-destination.is_active') }}</span>
        </label>

        {{-- Submit --}}
        <div class="flex justify-end pt-2 border-t border-base-content/5">
            <button type="submit" class="btn btn-primary btn-sm rounded-xl gap-2 px-6" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                {{ $destinationId ? __('backup-storage-destination.update') : __('backup-storage-destination.save') }}
            </button>
        </div>
    </form>
</div>
