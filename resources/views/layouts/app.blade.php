<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'LeadFlow') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased bg-slate-50 text-slate-900"
          x-data="{
              sidebarOpen: false,
              sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === 'true',
              toggleCollapse() {
                  this.sidebarCollapsed = !this.sidebarCollapsed;
                  localStorage.setItem('sidebarCollapsed', this.sidebarCollapsed);
              }
          }">
        <div class="flex h-screen overflow-hidden">

            <!-- Sidebar -->
            @include('layouts.sidebar')

            <!-- Main Content -->
            <div class="flex-1 flex flex-col overflow-y-auto overflow-x-hidden transition-all duration-300">

                <!-- Top Header -->
                <header class="bg-white border-b border-slate-200 h-16 px-6 lg:px-8 sticky top-0 z-10 flex justify-between items-center">
                    <div class="flex items-center">
                        <!-- Mobile hamburger -->
                        <button @click="sidebarOpen = true" class="lg:hidden mr-4 text-slate-500 hover:text-slate-700 focus:outline-none">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                        </button>
                        <div>
                            <h2 class="text-xl font-bold text-slate-800">
                                {{ $header ?? 'Dashboard' }}
                            </h2>
                            @if(isset($subheader))
                                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">{{ $subheader }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center space-x-3">
                        @if(isset($actions))
                            {{ $actions }}
                        @endif

                        <!-- User Dropdown -->
                        <div class="relative" x-data="{ open: false }">
                            <button @click="open = !open" class="flex items-center space-x-2 focus:outline-none group">
                                <div class="w-9 h-9 rounded-xl bg-brand-blue flex items-center justify-center text-white font-bold text-sm shadow-lg shadow-blue-500/20">
                                    {{ substr(auth()->user()->name, 0, 1) }}
                                </div>
                                <span class="hidden md:block text-sm font-medium text-slate-700 group-hover:text-slate-900 transition-colors">{{ auth()->user()->name }}</span>
                                <svg class="hidden md:block w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                            </button>

                            <div x-show="open" @click.away="open = false"
                                 x-transition:enter="transition ease-out duration-150"
                                 x-transition:enter-start="opacity-0 scale-95"
                                 x-transition:enter-end="opacity-100 scale-100"
                                 x-transition:leave="transition ease-in duration-100"
                                 x-transition:leave-start="opacity-100 scale-100"
                                 x-transition:leave-end="opacity-0 scale-95"
                                 class="absolute right-0 mt-2 w-56 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-20">
                                <div class="px-4 py-3 border-b border-slate-100">
                                    <p class="text-sm font-bold text-slate-800">{{ auth()->user()->name }}</p>
                                    <p class="text-[10px] text-slate-400 truncate">{{ auth()->user()->email }}</p>
                                </div>
                                <a href="{{ route('profile.edit') }}" class="flex items-center px-4 py-2.5 text-sm text-slate-700 hover:bg-slate-50 transition-colors">
                                    <svg class="w-4 h-4 mr-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                    Your Profile
                                </a>
                            </div>
                        </div>
                    </div>
                </header>

                <!-- Page Content -->
                <main class="flex-1 bg-[#f8fafc] overflow-y-auto no-scrollbar">
                    <div class="max-w-7xl mx-auto pl-4 lg:pl-8 xl:pl-10 pr-4 lg:pr-6 py-6 lg:py-8">
                        {{ $slot }}
                    </div>
                </main>
            </div>
        </div>
        @auth
            @include('layouts.partials.integrations-modal')
        @endauth
        @include('sweetalert2::index')
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                if (typeof window.bootLeadflowToasts === 'function') {
                    window.bootLeadflowToasts({
                        success: @js(session('success')),
                        error: @js(session('error')),
                        errors: @js($errors->any() ? $errors->all() : []),
                    });
                }
            });
        </script>
        @stack('scripts')
    </body>
</html>
