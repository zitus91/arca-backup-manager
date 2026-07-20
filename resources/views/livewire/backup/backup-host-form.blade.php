<div>
    <form wire:submit="save" class="space-y-5">
        {{-- Name --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-host.name') }}</span></label>
            <input type="text" wire:model="name" class="input input-bordered rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('backup-host.name_placeholder') }}" />
            <p class="text-[10px] text-base-content/40 mt-1">{{ __('backup-host.name_hint') }}</p>
            @error('name') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
        </div>

        @error('enable_services') <div class="rounded-lg border border-error/30 bg-error/5 px-3 py-2 text-error text-xs font-medium">{{ $message }}</div> @enderror

        {{-- SSH Connection --}}
        <div class="rounded-xl border border-secondary/20 bg-secondary/[0.03] p-5 space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="enable_ssh" class="toggle toggle-sm toggle-secondary" />
                <span class="text-sm font-semibold">{{ __('backup-host.enable_ssh') }}</span>
            </label>

            @if ($enable_ssh)
            {{-- Requirements check --}}
            @if (!empty($sshRequirements))
                <div class="space-y-1.5">
                    @foreach ($sshRequirements as $bin => $req)
                        @if ($req['needed'] ?? true)
                            @if ($req['ok'])
                                <div class="flex items-center gap-2 text-success text-xs font-medium px-1">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <span class="font-mono">{{ $bin }}</span>
                                    <span class="text-base-content/30 font-normal">{{ $req['path'] }}</span>
                                </div>
                            @else
                                <div class="rounded-lg border border-warning/30 bg-warning/5 px-3 py-2 space-y-1">
                                    <div class="flex items-center gap-2 text-warning text-xs font-semibold">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126zM12 15.75h.007v.008H12v-.008z" /></svg>
                                        <span class="font-mono">{{ $bin }}</span>
                                        <span class="font-normal text-warning/80">{{ __('backup-host.ssh_req_missing') }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 mt-0.5">
                                        <span class="text-[10px] text-base-content/40">{{ __('backup-host.ssh_req_install') }}:</span>
                                        <code class="text-[10px] font-mono bg-base-300/60 text-base-content/70 px-2 py-0.5 rounded select-all">{{ $req['install'] }}</code>
                                    </div>
                                </div>
                            @endif
                        @endif
                    @endforeach
                </div>
            @endif

            <div class="grid grid-cols-3 gap-3 pt-1">
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_user') }}</span></label>
                    <input type="text" wire:model="ssh_user" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" placeholder="ubuntu" />
                    @error('ssh_user') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_host') }}</span></label>
                    <input type="text" wire:model="ssh_host" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 font-mono" placeholder="ssh.server.com" />
                    @error('ssh_host') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>
                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_port') }}</span></label>
                    <input type="number" wire:model="ssh_port" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                    @error('ssh_port') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Auth method toggle --}}
                <div class="col-span-2">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_auth_method') }}</span></label>
                    <div class="flex gap-2">
                        <label class="cursor-pointer flex-1">
                            <input type="radio" wire:model.live="ssh_auth_method" value="key" class="peer sr-only" />
                            <div class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 text-xs font-semibold transition-all
                                peer-checked:border-secondary peer-checked:bg-secondary/10 peer-checked:text-secondary
                                border-base-content/10 text-base-content/50 hover:border-base-content/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 013 3m3 0a6 6 0 01-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1121.75 8.25z" /></svg>
                                {{ __('backup-host.ssh_auth_key') }}
                            </div>
                        </label>
                        <label class="cursor-pointer flex-1">
                            <input type="radio" wire:model.live="ssh_auth_method" value="password" class="peer sr-only" />
                            <div class="flex items-center justify-center gap-2 px-3 py-2 rounded-lg border-2 text-xs font-semibold transition-all
                                peer-checked:border-secondary peer-checked:bg-secondary/10 peer-checked:text-secondary
                                border-base-content/10 text-base-content/50 hover:border-base-content/20">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 10.5V6.75a4.5 4.5 0 10-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 002.25-2.25v-6.75a2.25 2.25 0 00-2.25-2.25H6.75a2.25 2.25 0 00-2.25 2.25v6.75a2.25 2.25 0 002.25 2.25z" /></svg>
                                {{ __('backup-host.ssh_auth_password') }}
                            </div>
                        </label>
                    </div>
                </div>

                @if ($ssh_auth_method === 'key')
                    <div class="form-control ">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_key_path') }}</span></label>
                        <input type="text" wire:model="ssh_key_path" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 font-mono" placeholder="/home/user/.ssh/id_rsa" />
                        @error('ssh_key_path') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @else
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.ssh_password') }}</span></label>
                        <input type="password" wire:model="ssh_password" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('ssh_password') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>

            {{-- Test SSH --}}
            <div class="pt-2 border-t border-secondary/10">
                <button type="button" wire:click="testSshConnection" class="btn btn-sm btn-outline btn-secondary rounded-lg gap-2" wire:loading.attr="disabled" wire:target="testSshConnection">
                    <span wire:loading wire:target="testSshConnection" class="loading loading-spinner loading-xs"></span>
                    <span wire:loading.remove wire:target="testSshConnection">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 3v1.5M4.5 8.25H3m18 0h-1.5M4.5 12H3m18 0h-1.5m-15 3.75H3m18 0h-1.5M8.25 19.5V21M12 3v1.5m0 15V21m3.75-18v1.5m0 15V21m-9-1.5h10.5a2.25 2.25 0 002.25-2.25V6.75a2.25 2.25 0 00-2.25-2.25H6.75A2.25 2.25 0 004.5 6.75v10.5a2.25 2.25 0 002.25 2.25zm.75-12h9v9h-9v-9z" /></svg>
                    </span>
                    {{ __('backup-host.ssh_test') }}
                </button>

                @if ($ssh_connection_status === 'success')
                    <div class="mt-3 flex items-center gap-2 text-success text-xs font-medium">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        {{ $ssh_connection_message }}
                    </div>
                @elseif ($ssh_connection_status === 'failed')
                    <div class="mt-3 flex items-start gap-2 text-error text-xs">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                        <span>{{ $ssh_connection_message }}</span>
                    </div>
                @endif
            </div>
            @endif
        </div>

        {{-- MySQL Service --}}
        <div class="rounded-xl border border-primary/20 bg-primary/[0.03] p-5 space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="enable_mysql" class="toggle toggle-sm toggle-primary" />
                <span class="text-sm font-semibold">{{ __('backup-host.service_mysql') }}</span>
            </label>

            @if ($enable_mysql)
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div class="form-control col-span-2 sm:col-span-1">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mysql_host') }}</span></label>
                        <input type="text" wire:model="mysql_host" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 font-mono" />
                        @error('mysql_host') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mysql_port') }}</span></label>
                        <input type="number" wire:model="mysql_port" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mysql_port') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mysql_user') }}</span></label>
                        <input type="text" wire:model="mysql_user" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mysql_user') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mysql_password') }}</span></label>
                        <input type="password" wire:model="mysql_password" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mysql_password') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-2 border-t border-primary/10">
                    <button type="button" wire:click="testMysqlConnection" class="btn btn-sm btn-outline btn-primary rounded-lg gap-2" wire:loading.attr="disabled" wire:target="testMysqlConnection">
                        <span wire:loading wire:target="testMysqlConnection" class="loading loading-spinner loading-xs"></span>
                        {{ __('backup-host.test_connection') }}
                    </button>

                    @if ($mysql_connection_status === 'success')
                        <div class="mt-3 flex items-center gap-2 text-success text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $mysql_connection_message }}
                        </div>
                    @elseif ($mysql_connection_status === 'failed')
                        <div class="mt-3 flex items-start gap-2 text-error text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span>{{ $mysql_connection_message }}</span>
                        </div>
                    @endif

                    @if (count($mysql_available_databases) > 0)
                        <p class="text-[10px] text-base-content/40 mt-2">{{ implode(', ', $mysql_available_databases) }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- MongoDB Service --}}
        <div class="rounded-xl border border-success/20 bg-success/[0.03] p-5 space-y-3">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="enable_mongodb" class="toggle toggle-sm toggle-success" />
                <span class="text-sm font-semibold">{{ __('backup-host.service_mongodb') }}</span>
            </label>

            @if ($enable_mongodb)
                <div class="grid grid-cols-2 gap-3 pt-1">
                    <div class="form-control col-span-2 sm:col-span-1">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mongodb_host') }}</span></label>
                        <input type="text" wire:model="mongodb_host" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 font-mono" />
                        @error('mongodb_host') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mongodb_port') }}</span></label>
                        <input type="number" wire:model="mongodb_port" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mongodb_port') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mongodb_user') }}</span></label>
                        <input type="text" wire:model="mongodb_user" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mongodb_user') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                    <div class="form-control">
                        <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-host.mongodb_password') }}</span></label>
                        <input type="password" wire:model="mongodb_password" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" />
                        @error('mongodb_password') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                </div>

                <div class="pt-2 border-t border-success/10">
                    <button type="button" wire:click="testMongoConnection" class="btn btn-sm btn-outline btn-success rounded-lg gap-2" wire:loading.attr="disabled" wire:target="testMongoConnection">
                        <span wire:loading wire:target="testMongoConnection" class="loading loading-spinner loading-xs"></span>
                        {{ __('backup-host.test_connection') }}
                    </button>

                    @if ($mongodb_connection_status === 'success')
                        <div class="mt-3 flex items-center gap-2 text-success text-xs font-medium">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                            {{ $mongodb_connection_message }}
                        </div>
                    @elseif ($mongodb_connection_status === 'failed')
                        <div class="mt-3 flex items-start gap-2 text-error text-xs">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 flex-shrink-0 mt-0.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                            <span>{{ $mongodb_connection_message }}</span>
                        </div>
                    @endif

                    @if (count($mongodb_available_databases) > 0)
                        <p class="text-[10px] text-base-content/40 mt-2">{{ implode(', ', $mongodb_available_databases) }}</p>
                    @endif
                </div>
            @endif
        </div>

        {{-- Filesystem Capability --}}
        <div class="rounded-xl border border-warning/20 bg-warning/[0.03] p-5 space-y-2">
            <label class="flex items-center gap-3 cursor-pointer">
                <input type="checkbox" wire:model.live="enable_filesystem" class="toggle toggle-sm toggle-warning" />
                <span class="text-sm font-semibold">{{ __('backup-host.service_filesystem') }}</span>
            </label>
            <p class="text-[10px] text-base-content/40">{{ __('backup-host.filesystem_capability_hint') }}</p>
        </div>

        {{-- Active Toggle --}}
        <label class="flex items-center gap-3 cursor-pointer py-2">
            <input type="checkbox" wire:model="is_active" class="toggle toggle-sm toggle-success" />
            <span class="text-sm font-medium">{{ __('backup-host.is_active') }}</span>
        </label>

        {{-- Submit --}}
        <div class="flex justify-end pt-2 border-t border-base-content/5">
            <button type="submit" class="btn btn-primary btn-sm rounded-xl gap-2 px-6" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                {{ __('backup-host.save_button') }}
            </button>
        </div>
    </form>
</div>
