<div>
    <form wire:submit="save" class="space-y-5">
        {{-- Name --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.name') }}</span></label>
            <input type="text" wire:model="name" class="input input-bordered rounded-xl bg-base-200/50 border-base-content/10 focus:border-primary" placeholder="{{ __('backup-source.name_placeholder') }}" />
            <p class="text-[10px] text-base-content/40 mt-1">{{ __('backup-source.name_hint') }}</p>
            @error('name') <span class="text-error text-xs mt-1.5">{{ $message }}</span> @enderror
        </div>

        {{-- Source Toggles --}}
        <div class="form-control">
            <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.sources') }}</span></label>
            <div class="grid grid-cols-3 gap-3">
                {{-- MySQL Toggle --}}
                <label class="relative cursor-pointer">
                    <input type="checkbox" wire:model.live="enable_mysql" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-primary peer-checked:bg-primary/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-primary/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">MySQL</p>
                            <p class="text-xs text-base-content/40">SQL Database</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded border-2 border-base-content/20 flex items-center justify-center transition-all {{ $enable_mysql ? 'border-primary bg-primary' : '' }}">
                                @if ($enable_mysql)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>

                {{-- MongoDB Toggle --}}
                <label class="relative cursor-pointer">
                    <input type="checkbox" wire:model.live="enable_mongodb" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-success peer-checked:bg-success/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-success/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">MongoDB</p>
                            <p class="text-xs text-base-content/40">NoSQL Database</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded border-2 border-base-content/20 flex items-center justify-center transition-all {{ $enable_mongodb ? 'border-success bg-success' : '' }}">
                                @if ($enable_mongodb)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-success-content" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>

                {{-- Filesystem Toggle --}}
                <label class="relative cursor-pointer">
                    <input type="checkbox" wire:model.live="enable_filesystem" class="peer sr-only" />
                    <div class="flex items-center gap-3 p-4 rounded-xl border-2 border-base-content/10 peer-checked:border-warning peer-checked:bg-warning/5 transition-all">
                        <div class="w-10 h-10 rounded-xl bg-warning/10 flex items-center justify-center">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                        </div>
                        <div>
                            <p class="font-semibold text-sm">Filesystem</p>
                            <p class="text-xs text-base-content/40">Files & Folders</p>
                        </div>
                        <div class="ml-auto">
                            <div class="w-4 h-4 rounded border-2 border-base-content/20 flex items-center justify-center transition-all {{ $enable_filesystem ? 'border-warning bg-warning' : '' }}">
                                @if ($enable_filesystem)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 text-warning-content" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                @endif
                            </div>
                        </div>
                    </div>
                </label>
            </div>
            <p class="text-[10px] text-base-content/40 mt-1.5">{{ __('backup-source.at_least_one_source') }}</p>
            @error('enable_sources') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
        </div>

        {{-- MySQL Fields --}}
        @if ($enable_mysql)
            <div class="rounded-xl border border-primary/20 bg-primary/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-primary" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                    <h4 class="text-sm font-semibold text-primary">{{ __('backup-source.mysql_config') }}</h4>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-source.mysql_host_select') }}</span></label>
                    <div class="flex items-center gap-3">
                        <select wire:model.live="mysql_host_id" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 flex-1">
                            <option value="">{{ __('backup-source.host_none') }}</option>
                            @foreach ($mysqlHosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('backup.hosts') }}" target="_blank" class="btn btn-sm btn-outline btn-primary rounded-lg gap-1 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('backup-source.host_new') }}
                        </a>
                    </div>
                    @error('mysql_host_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Load Databases --}}
                @if ($mysql_host_id)
                    <div class="pt-2 border-t border-primary/10">
                        <button type="button" wire:click="loadMysqlDatabases" class="btn btn-sm btn-outline btn-primary rounded-lg gap-2" wire:loading.attr="disabled" wire:target="loadMysqlDatabases">
                            <span wire:loading wire:target="loadMysqlDatabases" class="loading loading-spinner loading-xs"></span>
                            <span wire:loading.remove wire:target="loadMysqlDatabases">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                            </span>
                            {{ __('backup-source.load_databases') }}
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
                    </div>
                @endif

                {{-- Database Selection (shown after successful connection) --}}
                @if (count($mysql_available_databases) > 0)
                    <div class="pt-3 border-t border-primary/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.mysql_select_databases') }}</label>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="toggleSelectAllDatabases" class="text-[10px] font-medium text-primary hover:text-primary/80 transition-colors">
                                    {{ count($mysql_databases) === count($mysql_available_databases) ? __('backup-source.mysql_deselect_all') : __('backup-source.mysql_select_all') }}
                                </button>
                                <span class="text-[10px] text-base-content/40">{{ count($mysql_databases) }}/{{ count($mysql_available_databases) }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
                            @foreach ($mysql_available_databases as $db)
                                <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all
                                    {{ in_array($db, $mysql_databases) ? 'border-primary/40 bg-primary/10' : 'border-base-content/10 bg-base-100 hover:border-base-content/20' }}">
                                    <input type="checkbox" value="{{ $db }}" wire:model="mysql_databases" class="checkbox checkbox-xs checkbox-primary rounded" />
                                    <span class="text-xs font-medium truncate">{{ $db }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('mysql_databases') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @elseif (!empty($mysql_databases))
                    <div class="pt-3 border-t border-primary/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.mysql_select_databases') }}</label>
                            <span class="text-[10px] text-base-content/40">{{ count($mysql_databases) }} {{ __('backup-source.mysql_selected') }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($mysql_databases as $db)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-primary/10 text-primary border border-primary/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                    {{ $db }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-base-content/40">{{ __('backup-source.mysql_test_to_change') }}</p>
                        @error('mysql_databases') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- MongoDB Fields --}}
        @if ($enable_mongodb)
            <div class="rounded-xl border border-success/20 bg-success/[0.03] p-5 space-y-4">
                <div class="flex items-center gap-2 mb-1">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                    <h4 class="text-sm font-semibold text-success">{{ __('backup-source.mongodb_config') }}</h4>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-source.mongodb_host_select') }}</span></label>
                    <div class="flex items-center gap-3">
                        <select wire:model.live="mongodb_host_id" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 flex-1">
                            <option value="">{{ __('backup-source.host_none') }}</option>
                            @foreach ($mongodbHosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('backup.hosts') }}" target="_blank" class="btn btn-sm btn-outline btn-success rounded-lg gap-1 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('backup-source.host_new') }}
                        </a>
                    </div>
                    @error('mongodb_host_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Load Databases --}}
                @if ($mongodb_host_id)
                    <div class="pt-2 border-t border-success/10">
                        <button type="button" wire:click="loadMongodbDatabases" class="btn btn-sm btn-outline btn-success rounded-lg gap-2" wire:loading.attr="disabled" wire:target="loadMongodbDatabases">
                            <span wire:loading wire:target="loadMongodbDatabases" class="loading loading-spinner loading-xs"></span>
                            <span wire:loading.remove wire:target="loadMongodbDatabases">
                                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.288 15.038a5.25 5.25 0 017.424 0M5.106 11.856c3.807-3.808 9.98-3.808 13.788 0M1.924 8.674c5.565-5.565 14.587-5.565 20.152 0M12.53 18.22l-.53.53-.53-.53a.75.75 0 011.06 0z" /></svg>
                            </span>
                            {{ __('backup-source.load_databases') }}
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
                    </div>
                @endif

                {{-- Database Selection (shown after successful connection) --}}
                @if (count($mongodb_available_databases) > 0)
                    <div class="pt-3 border-t border-success/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.mongodb_select_databases') }}</label>
                            <div class="flex items-center gap-3">
                                <button type="button" wire:click="toggleSelectAllMongoDatabases" class="text-[10px] font-medium text-success hover:text-success/80 transition-colors">
                                    {{ count($mongodb_databases) === count($mongodb_available_databases) ? __('backup-source.mongodb_deselect_all') : __('backup-source.mongodb_select_all') }}
                                </button>
                                <span class="text-[10px] text-base-content/40">{{ count($mongodb_databases) }}/{{ count($mongodb_available_databases) }}</span>
                            </div>
                        </div>
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 max-h-48 overflow-y-auto pr-1">
                            @foreach ($mongodb_available_databases as $db)
                                <label class="flex items-center gap-2.5 px-3 py-2 rounded-lg border cursor-pointer transition-all
                                    {{ in_array($db, $mongodb_databases) ? 'border-success/40 bg-success/10' : 'border-base-content/10 bg-base-100 hover:border-base-content/20' }}">
                                    <input type="checkbox" value="{{ $db }}" wire:model="mongodb_databases" class="checkbox checkbox-xs checkbox-success rounded" />
                                    <span class="text-xs font-medium truncate">{{ $db }}</span>
                                </label>
                            @endforeach
                        </div>
                        @error('mongodb_databases') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @elseif (!empty($mongodb_databases))
                    <div class="pt-3 border-t border-success/10 space-y-3">
                        <div class="flex items-center justify-between">
                            <label class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.mongodb_select_databases') }}</label>
                            <span class="text-[10px] text-base-content/40">{{ count($mongodb_databases) }} {{ __('backup-source.mongodb_selected') }}</span>
                        </div>
                        <div class="flex flex-wrap gap-2">
                            @foreach ($mongodb_databases as $db)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-xs font-semibold bg-success/10 text-success border border-success/20">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3 mr-1.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375" /></svg>
                                    {{ $db }}
                                </span>
                            @endforeach
                        </div>
                        <p class="text-[10px] text-base-content/40">{{ __('backup-source.mongodb_test_to_change') }}</p>
                        @error('mongodb_databases') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                    </div>
                @endif
            </div>
        @endif

        {{-- Filesystem Fields --}}
        @if ($enable_filesystem)
            <div class="rounded-xl border border-warning/20 bg-warning/[0.03] p-5 space-y-4">
                <div class="flex items-center justify-between mb-1">
                    <div class="flex items-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-warning" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A2.25 2.25 0 014.5 9.75h15A2.25 2.25 0 0121.75 12v.75m-8.69-6.44l-2.12-2.12a1.5 1.5 0 00-1.061-.44H4.5A2.25 2.25 0 002.25 6v12a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9a2.25 2.25 0 00-2.25-2.25h-5.379a1.5 1.5 0 01-1.06-.44z" /></svg>
                        <h4 class="text-sm font-semibold text-warning">{{ __('backup-source.filesystem_config') }}</h4>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" wire:click="checkAllFilesystemPaths" class="text-[10px] font-medium text-warning hover:text-warning/80 transition-colors">
                            {{ __('backup-source.check_all') }}
                        </button>
                    </div>
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-source.filesystem_host_select') }}</span></label>
                    <div class="flex items-center gap-3">
                        <select wire:model.live="filesystem_host_id" class="select select-bordered select-sm rounded-lg bg-base-100 border-base-content/10 flex-1">
                            <option value="">{{ __('backup-source.host_none') }}</option>
                            @foreach ($filesystemHosts as $host)
                                <option value="{{ $host->id }}">{{ $host->name }}</option>
                            @endforeach
                        </select>
                        <a href="{{ route('backup.hosts') }}" target="_blank" class="btn btn-sm btn-outline btn-warning rounded-lg gap-1 whitespace-nowrap">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                            {{ __('backup-source.host_new') }}
                        </a>
                    </div>
                    @error('filesystem_host_id') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                {{-- Paths List --}}
                <div class="space-y-3">
                    <label class="label-text text-xs font-medium text-base-content/50 uppercase tracking-wider">{{ __('backup-source.paths') }}</label>
                    @foreach ($fs_paths as $index => $path)
                        <div wire:key="fs-path-{{ $index }}">
                            <div class="flex gap-2">
                                <input type="text" wire:model="fs_paths.{{ $index }}" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10 flex-1 font-mono text-xs" placeholder="{{ __('backup-source.path_placeholder') }}" />
                                <button type="button" wire:click="checkFilesystemPath({{ $index }})" class="btn btn-sm btn-outline btn-warning rounded-lg gap-1 px-2" wire:loading.attr="disabled" wire:target="checkFilesystemPath({{ $index }})">
                                    <span wire:loading wire:target="checkFilesystemPath({{ $index }})" class="loading loading-spinner loading-xs"></span>
                                    <span wire:loading.remove wire:target="checkFilesystemPath({{ $index }})">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    </span>
                                </button>
                                @if (count($fs_paths) > 1)
                                    <button type="button" wire:click="removeFsPath({{ $index }})" class="btn btn-sm btn-ghost btn-square rounded-lg text-error/60 hover:text-error hover:bg-error/10">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                    </button>
                                @endif
                            </div>
                            @if (isset($fs_path_statuses[$index]) && $fs_path_statuses[$index] === 'success')
                                <div class="mt-1 flex items-center gap-1.5 text-success text-[10px] font-medium">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    {{ $fs_path_messages[$index] ?? '' }}
                                </div>
                            @elseif (isset($fs_path_statuses[$index]) && $fs_path_statuses[$index] === 'failed')
                                <div class="mt-1 flex items-center gap-1.5 text-error text-[10px]">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" /></svg>
                                    {{ $fs_path_messages[$index] ?? '' }}
                                </div>
                            @endif
                            @error('fs_paths.' . $index) <span class="text-error text-xs mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    @endforeach

                    <button type="button" wire:click="addFsPath" class="btn btn-xs btn-ghost text-warning gap-1.5 rounded-lg">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        {{ __('backup-source.add_path') }}
                    </button>
                    @error('fs_paths') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div class="form-control">
                    <label class="label pb-1"><span class="label-text text-xs font-medium text-base-content/50">{{ __('backup-source.exclude') }} <span class="text-base-content/30">({{ __('backup-source.optional') }})</span></span></label>
                    <input type="text" wire:model="fs_exclude_patterns" class="input input-bordered input-sm rounded-lg bg-base-100 border-base-content/10" placeholder="{{ __('backup-source.exclude_placeholder') }}" />
                    @error('fs_exclude_patterns') <span class="text-error text-xs mt-1">{{ $message }}</span> @enderror
                </div>

            </div>
        @endif

        {{-- Active Toggle --}}
        <label class="flex items-center gap-3 cursor-pointer py-2">
            <input type="checkbox" wire:model="is_active" class="toggle toggle-sm toggle-success" />
            <span class="text-sm font-medium">{{ __('backup-source.is_active') }}</span>
        </label>

        {{-- Submit --}}
        <div class="flex justify-end pt-2 border-t border-base-content/5">
            <button type="submit" class="btn btn-primary btn-sm rounded-xl gap-2 px-6" wire:loading.attr="disabled">
                <span wire:loading wire:target="save" class="loading loading-spinner loading-xs"></span>
                {{ $sourceId ? __('backup-source.update') : __('backup-source.save') }}
            </button>
        </div>
    </form>
</div>
