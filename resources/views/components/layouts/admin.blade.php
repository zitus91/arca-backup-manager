<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} &mdash; Backup Manager</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/images/logo.png') }}">
    <link href="https://fonts.bunny.net/css?family=inter:400,500,600,700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    <style>
        [data-sidebar-open="true"] .sidebar-overlay { display: block; }
        [data-sidebar-open="true"] .sidebar-panel { transform: translateX(0); }
    </style>
</head>
<body class="min-h-screen bg-base-300 font-[Inter]" data-sidebar-open="false">

    <div class="flex h-screen overflow-hidden">
        {{-- Sidebar Overlay (mobile) --}}
        <div class="sidebar-overlay fixed inset-0 bg-black/60 backdrop-blur-sm z-40 lg:hidden hidden"
             onclick="document.body.dataset.sidebarOpen='false'"></div>

        {{-- Sidebar --}}
        <aside class="sidebar-panel fixed lg:static inset-y-0 left-0 z-50 w-72 bg-base-100 border-r border-base-content/5 flex flex-col transform -translate-x-full lg:translate-x-0 transition-transform duration-300 ease-in-out">
            {{-- Logo --}}
            <div class="px-6 py-5 border-b border-base-content/5 flex-shrink-0">
                <a href="{{ route('admin.backup.dashboard') }}" class="flex items-center gap-3">
                    {{-- <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary to-secondary flex items-center justify-center shadow-lg shadow-primary/20"> --}}
                    <div class="rounded-xl flex items-center justify-center w-15 h-15">
                        {{-- <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 text-primary-content" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4" />
                        </svg> --}}
                        <img src="{{ asset('/images/logo.png') }}" alt="Backup Manager Logo" class="w-15 h-15" />
                    </div>
                    <div>
                        <span class="text-lg font-bold tracking-tight">Backup</span>
                        <span class="text-xs text-base-content/50 block -mt-0.5">Manager v1.1.0</span>
                    </div>
                </a>
            </div>

            {{-- Navigation --}}
            <nav class="flex-1 overflow-y-auto px-4 py-6 space-y-1">
                <p class="px-3 mb-3 text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-dashboard.nav_overview') }}</p>

                <a href="{{ route('admin.backup.dashboard') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.dashboard') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.dashboard') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v2.25a2.25 2.25 0 01-2.25 2.25H6a2.25 2.25 0 01-2.25-2.25V6zM3.75 15.75A2.25 2.25 0 016 13.5h2.25a2.25 2.25 0 012.25 2.25V18a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18v-2.25zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v2.25A2.25 2.25 0 0118 10.5h-2.25a2.25 2.25 0 01-2.25-2.25V6zM13.5 15.75a2.25 2.25 0 012.25-2.25H18a2.25 2.25 0 012.25 2.25V18A2.25 2.25 0 0118 20.25h-2.25A2.25 2.25 0 0113.5 18v-2.25z" />
                    </svg>
                    Dashboard
                </a>

                <p class="px-3 mb-3 mt-6 text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('backup-dashboard.nav_manage') }}</p>

                <a href="{{ route('admin.backup.jobs') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.jobs') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.jobs') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" />
                    </svg>
                    {{ __('backup-job.title') }}
                </a>

                <a href="{{ route('admin.backup.sources') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.sources') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.sources') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 6.375c0 2.278-3.694 4.125-8.25 4.125S3.75 8.653 3.75 6.375m16.5 0c0-2.278-3.694-4.125-8.25-4.125S3.75 4.097 3.75 6.375m16.5 0v11.25c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125V6.375m16.5 0v3.75m-16.5-3.75v3.75m16.5 0v3.75C20.25 16.153 16.556 18 12 18s-8.25-1.847-8.25-4.125v-3.75m16.5 0c0 2.278-3.694 4.125-8.25 4.125s-8.25-1.847-8.25-4.125" />
                    </svg>
                    {{ __('backup-source.title') }}
                </a>

                <a href="{{ route('admin.backup.destinations') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.destinations') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.destinations') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15a4.5 4.5 0 004.5 4.5H18a3.75 3.75 0 001.332-7.257 3 3 0 00-3.758-3.848 5.25 5.25 0 00-10.233 2.33A4.502 4.502 0 002.25 15z" />
                    </svg>
                    {{ __('backup-storage-destination.title') }}
                </a>

                <a href="{{ route('admin.backup.logs') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.logs') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.logs') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" />
                    </svg>
                    {{ __('backup-log.title') }}
                </a>

                <a href="{{ route('admin.backup.restore') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.restore') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.restore') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182" />
                    </svg>
                    {{ __('restore.title') }}
                </a>

                <a href="{{ route('admin.backup.audit') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.audit') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.audit') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />
                    </svg>
                    {{ __('audit-log.title') }}
                </a>

                <p class="px-3 mb-3 mt-6 text-xs font-semibold text-base-content/40 uppercase tracking-wider">{{ __('users.nav_section') }}</p>

                <a href="{{ route('admin.backup.users') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.users') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.users') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                    </svg>
                    {{ __('users.title') }}
                </a>

                <a href="{{ route('admin.backup.profile') }}"
                   class="flex items-center gap-3 px-3 py-2.5 rounded-xl text-sm font-medium transition-all duration-200
                          {{ request()->routeIs('admin.backup.profile') ? 'bg-primary/10 text-primary shadow-sm' : 'text-base-content/70 hover:bg-base-content/5 hover:text-base-content' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5 {{ request()->routeIs('admin.backup.profile') ? 'text-primary' : 'text-base-content/40' }}" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    {{ __('users.profile') }}
                </a>
            </nav>

            {{-- Footer --}}
            <div class="px-6 py-4 border-t border-base-content/5 flex-shrink-0">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-lg bg-primary/10 flex items-center justify-center flex-shrink-0">
                            <span class="text-xs font-bold text-primary">{{ strtoupper(substr(auth()->user()->name, 0, 2)) }}</span>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                            <p class="text-[11px] text-base-content/40 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="btn btn-ghost btn-xs btn-square rounded-lg tooltip tooltip-left" data-tip="{{ __('auth.logout') }}">
                            <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-base-content/50" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
                            </svg>
                        </button>
                    </form>
                </div>
            </div>
        </aside>

        {{-- Main Content Area --}}
        <div class="flex-1 flex flex-col min-w-0 overflow-hidden">
            {{-- Top bar (mobile) --}}
            <header class="flex-shrink-0 h-14 bg-base-100/80 backdrop-blur-xl border-b border-base-content/5 flex items-center px-4 lg:hidden">
                <button onclick="document.body.dataset.sidebarOpen='true'" class="btn btn-sm btn-square btn-ghost">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" class="w-5 h-5 stroke-current"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>
                <span class="ml-3 text-lg font-bold bg-gradient-to-r from-primary to-secondary bg-clip-text text-transparent">Backup Manager</span>
            </header>

            {{-- Page Content --}}
            <main class="flex-1 overflow-y-auto p-4 lg:p-8">
                <div class="max-w-7xl mx-auto">
                    {{ $slot }}
                </div>
            </main>
        </div>
    </div>

    @livewireScripts

    {{-- Toast Container for backup events --}}
    <div id="toast-container" class="toast toast-end toast-bottom z-[100]"></div>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            if (window.Echo) {
                window.Echo.channel('backup-jobs')
                    .listen('.backup.started', (e) => {
                        showToast('info', '🚀 ' + e.job_name, '{{ __("backup-dashboard.toast_started") }}');
                    })
                    .listen('.backup.completed', (e) => {
                        if (e.status === 'success') {
                            showToast('success', '✅ ' + e.job_name, '{{ __("backup-dashboard.toast_success") }}');
                        } else {
                            showToast('error', '❌ ' + e.job_name, '{{ __("backup-dashboard.toast_failed") }}');
                        }
                    })
                    .listen('.restore.started', (e) => {
                        showToast('info', '🔄 ' + e.job_name, '{{ __("restore.toast_started") }}');
                    })
                    .listen('.restore.completed', (e) => {
                        if (e.status === 'success') {
                            showToast('success', '✅ ' + e.job_name, '{{ __("restore.toast_success") }}');
                        } else {
                            showToast('error', '❌ ' + e.job_name, '{{ __("restore.toast_failed") }}');
                        }
                    });
            }

            document.addEventListener('livewire:initialized', () => {
                Livewire.on('test-email-result', (params) => {
                    const p = Array.isArray(params) ? params[0] : params;
                    if (p.status === 'success') {
                        showToast('success', '{{ __("backup-job.test_email_toast_title") }}', p.message);
                    } else {
                        showToast('error', '{{ __("backup-job.test_email_toast_error_title") }}', p.message);
                    }
                });
            });
        });

        function showToast(type, title, subtitle) {
            const container = document.getElementById('toast-container');
            const alertClass = { success: 'alert-success', error: 'alert-error', info: 'alert-info' }[type] || 'alert-info';
            const toast = document.createElement('div');
            toast.className = 'alert ' + alertClass + ' shadow-lg animate-slide-in-right text-sm max-w-sm';
            toast.innerHTML = '<div class="flex flex-col gap-0.5"><span class="font-semibold">' + title + '</span><span class="text-xs opacity-80">' + subtitle + '</span></div>';
            container.appendChild(toast);
            setTimeout(() => {
                toast.style.transition = 'opacity 0.3s, transform 0.3s';
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(() => toast.remove(), 300);
            }, 5000);
        }
    </script>
</body>
</html>
