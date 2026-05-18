{{-- Helper to build nav link classes --}}
@php
    $navLinkClass = function($active) {
        $base = 'flex items-center px-3 py-2.5 text-sm font-medium rounded-xl transition-all duration-200';
        return $active
            ? "$base bg-brand-blue text-white shadow-lg shadow-blue-500/20"
            : "$base text-slate-400 hover:text-white hover:bg-navy-800";
    };
@endphp

{{-- ===== DESKTOP SIDEBAR ===== --}}
<aside
    class="hidden lg:flex flex-col bg-navy-900 transition-all duration-300 ease-in-out shrink-0"
    :class="sidebarCollapsed ? 'w-[72px]' : 'w-64'"
>
    <div class="flex flex-col h-full">
        {{-- Logo --}}
        <div class="flex items-center h-[65px] border-b border-navy-800 bg-navy-950 transition-all duration-300"
             :class="sidebarCollapsed ? 'justify-center px-0' : 'px-5'">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3 min-w-0">
                <div class="w-9 h-9 bg-brand-blue rounded-xl flex items-center justify-center shrink-0 shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="text-white font-bold text-lg tracking-tight whitespace-nowrap overflow-hidden transition-all duration-300"
                      :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">
                    eGSales AI
                </span>
            </a>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-5 space-y-6 transition-all duration-300"
             :class="sidebarCollapsed ? 'px-2' : 'px-3'">

            {{-- Main Menu --}}
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 transition-all duration-300 overflow-hidden whitespace-nowrap"
                   :class="sidebarCollapsed ? 'px-0 text-center' : 'px-3'">
                    <span :class="sidebarCollapsed ? 'hidden' : ''">Main Menu</span>
                    <span :class="sidebarCollapsed ? '' : 'hidden'" class="text-slate-600">•••</span>
                </p>
                <nav class="space-y-1">
                    {{-- Dashboard --}}
                    <a href="{{ route('dashboard') }}" class="{{ $navLinkClass(request()->routeIs('dashboard')) }}" title="Dashboard">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Dashboard</span>
                    </a>

                    {{-- Lead Hunter --}}
                    <a href="{{ route('lead-searches.create') }}" class="{{ $navLinkClass(request()->routeIs('lead-searches.create')) }}" title="Lead Hunter">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Lead Hunter</span>
                    </a>

                    {{-- Leads --}}
                    <a href="{{ route('lead-searches.index') }}" class="{{ $navLinkClass(request()->routeIs('lead-searches.index') || request()->routeIs('lead-searches.leads')) }}" title="Leads">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Leads</span>
                    </a>

                    {{-- Campaigns --}}
                    <a href="{{ route('campaigns.index') }}" class="{{ $navLinkClass(request()->routeIs('campaigns.*')) }}" title="Campaigns">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Campaigns</span>
                    </a>

                    {{-- Templates --}}
                    <a href="{{ route('settings.templates') }}" class="{{ $navLinkClass(request()->routeIs('settings.templates')) }}" title="Templates">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Templates</span>
                    </a>

                    {{-- Email Settings --}}
                    <a href="{{ route('settings.senders') }}" class="{{ $navLinkClass(request()->routeIs('settings.senders')) }}" title="Email Settings">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Email Settings</span>
                    </a>

                    {{-- Billing History --}}
                    <a href="#" class="{{ $navLinkClass(false) }}" title="Billing History">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Billing History</span>
                    </a>
                </nav>
            </div>

            {{-- Administration (Admin Only) --}}
            @if(Auth::user()->isAdmin())
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 transition-all duration-300 overflow-hidden whitespace-nowrap"
                   :class="sidebarCollapsed ? 'px-0 text-center' : 'px-3'">
                    <span :class="sidebarCollapsed ? 'hidden' : ''">Administration</span>
                    <span :class="sidebarCollapsed ? '' : 'hidden'" class="text-slate-600">•••</span>
                </p>
                <nav class="space-y-1">
                    {{-- Users and Plans --}}
                    <a href="{{ route('admin.users.index') }}" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}" title="Users and Plans">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                              :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Users and Plans</span>
                    </a>
                </nav>
            </div>
            @endif
        </div>

        {{-- Logout --}}
        <div class="border-t border-navy-800 bg-navy-950 transition-all duration-300"
             :class="sidebarCollapsed ? 'p-2' : 'p-3'">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-3 py-2.5 text-sm font-medium text-red-400 hover:text-white hover:bg-red-600/20 rounded-xl transition-all" title="Logout">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="ml-3 whitespace-nowrap overflow-hidden transition-all duration-300"
                          :class="sidebarCollapsed ? 'w-0 opacity-0' : 'w-auto opacity-100'">Logout</span>
                </button>
            </form>
        </div>
    </div>
</aside>


{{-- ===== MOBILE SIDEBAR (Drawer) ===== --}}
<div x-show="sidebarOpen" class="lg:hidden fixed inset-0 z-30 flex" x-cloak>
    {{-- Backdrop --}}
    <div @click="sidebarOpen = false"
         class="fixed inset-0 bg-black/60 backdrop-blur-sm"
         x-transition:enter="transition ease-out duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0">
    </div>

    {{-- Drawer --}}
    <aside class="relative w-72 bg-navy-900 flex flex-col h-full shadow-2xl"
           x-transition:enter="transition ease-out duration-300" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
           x-transition:leave="transition ease-in duration-200" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full">

        {{-- Logo --}}
        <div class="flex items-center justify-between h-[65px] px-5 bg-navy-950 border-b border-navy-800">
            <a href="{{ route('dashboard') }}" class="flex items-center space-x-3">
                <div class="w-9 h-9 bg-brand-blue rounded-xl flex items-center justify-center shadow-lg shadow-blue-500/20">
                    <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                </div>
                <span class="text-white font-bold text-lg tracking-tight">eGSales AI</span>
            </a>
            <button @click="sidebarOpen = false" class="text-slate-400 hover:text-white focus:outline-none transition-colors">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        </div>

        {{-- Navigation --}}
        <div class="flex-1 overflow-y-auto py-5 px-3 space-y-6">

            {{-- Main Menu --}}
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 px-3">Main Menu</p>
                <nav class="space-y-1">
                    <a href="{{ route('dashboard') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('dashboard')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                        <span class="ml-3">Dashboard</span>
                    </a>
                    <a href="{{ route('lead-searches.create') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('lead-searches.create')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5M7.188 2.239l.777 2.897M5.136 7.965l-2.898-.777M13.95 4.05l-2.122 2.122m-5.657 5.656l-2.12 2.122"></path></svg>
                        <span class="ml-3">Lead Hunter</span>
                    </a>
                    <a href="{{ route('lead-searches.index') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('lead-searches.index') || request()->routeIs('lead-searches.leads')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        <span class="ml-3">Leads</span>
                    </a>
                    <a href="{{ route('campaigns.index') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('campaigns.*')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span class="ml-3">Campaigns</span>
                    </a>
                    <a href="{{ route('settings.templates') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('settings.templates')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm0 8a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zm10 0a1 1 0 011-1h4a1 1 0 011 1v6a1 1 0 01-1 1h-4a1 1 0 01-1-1v-6z"></path></svg>
                        <span class="ml-3">Templates</span>
                    </a>
                    <a href="{{ route('settings.senders') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('settings.senders')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                        <span class="ml-3">Email Settings</span>
                    </a>
                    <a href="#" class="{{ $navLinkClass(false) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span class="ml-3">Billing History</span>
                    </a>
                </nav>
            </div>

            {{-- Administration (Admin Only) --}}
            @if(Auth::user()->isAdmin())
            <div>
                <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mb-3 px-3">Administration</p>
                <nav class="space-y-1">
                    <a href="{{ route('admin.users.index') }}" @click="sidebarOpen = false" class="{{ $navLinkClass(request()->routeIs('admin.users.*')) }}">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        <span class="ml-3">Users and Plans</span>
                    </a>
                </nav>
            </div>
            @endif
        </div>

        {{-- Logout --}}
        <div class="p-3 border-t border-navy-800 bg-navy-950">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="flex items-center w-full px-3 py-2.5 text-sm font-medium text-red-400 hover:text-white hover:bg-red-600/20 rounded-xl transition-all">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    <span class="ml-3">Logout</span>
                </button>
            </form>
        </div>
    </aside>
</div>
