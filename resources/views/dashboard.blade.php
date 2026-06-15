<x-app-layout>
    <x-slot name="header">
        Dashboard
    </x-slot>

    <div class="space-y-8">
        <!-- Welcome Section -->
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-slate-500 font-medium uppercase tracking-wider text-xs">eGSales AI Intelligence</h3>
            </div>
            <div class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-xs font-bold flex items-center">
                <span class="w-2 h-2 bg-emerald-500 rounded-full mr-2 animate-pulse"></span>
                EGSALES AI LIVE
            </div>
        </div>

        <!-- Hero Card -->
        <div class="bg-navy-900 rounded-3xl p-12 text-center text-white shadow-2xl relative overflow-hidden">
            <div class="relative z-10">
                <h1 class="text-4xl font-extrabold mb-4">Sales Intelligence Engine</h1>
                <p class="text-slate-300 text-lg max-w-2xl mx-auto">
                    Welcome back <span class="text-white font-bold">{{ auth()->user()->name }}</span>. 
                    Your workspace is connected to <span class="text-brand-blue font-bold">{{ $stats['total_leads'] }}</span> verified intelligence records, ready for discovery and export.
                </p>
            </div>
            <!-- Decorative background elements -->
            <div class="absolute top-0 right-0 -mr-20 -mt-20 w-64 h-64 bg-brand-blue/10 rounded-full blur-3xl"></div>
            <div class="absolute bottom-0 left-0 -ml-20 -mb-20 w-64 h-64 bg-brand-cyan/10 rounded-full blur-3xl"></div>
        </div>

        <!-- Email Tracking Stats -->
        <div>
            <div class="flex items-center justify-between mb-4">
                <h3 class="font-bold text-slate-800">Email Outreach</h3>
                {{-- <a href="{{ route('opened-emails.index') }}" class="text-brand-blue text-sm font-bold hover:underline">View opened emails</a> --}}
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                        </div>
                        <span class="bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Sent</span>
                    </div>
                    <p class="text-3xl font-black text-slate-800">{{ number_format($emailStats['sent']) }}</p>
                    <p class="text-slate-500 text-sm mt-1">Emails delivered via outreach</p>
                </div>

                <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-amber-50 rounded-2xl flex items-center justify-center text-amber-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                        </div>
                        <span class="bg-amber-50 text-amber-700 px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Drafts</span>
                    </div>
                    <p class="text-3xl font-black text-slate-800">{{ number_format($emailStats['drafted']) }}</p>
                    <p class="text-slate-500 text-sm mt-1">Emails saved as drafts</p>
                </div>

                <a href="{{ route('opened-emails.index') }}" class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 hover:border-brand-blue/30 transition-colors block">
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-brand-blue">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </div>
                        <span class="bg-blue-50 text-brand-blue px-2.5 py-1 rounded-full text-[10px] font-bold uppercase">Opened</span>
                    </div>
                    <p class="text-3xl font-black text-slate-800">{{ number_format($emailStats['opened']) }}</p>
                    <p class="text-slate-500 text-sm mt-1">Sent emails opened by recipients</p>
                </a>
            </div>
        </div>

        <!-- Stats Cards Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Account Authority / Search Usage -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 flex flex-col items-center text-center">
                @if(auth()->user()->role === 'admin')
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                    </div>
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs mb-2">Account Authority</p>
                    <h4 class="text-2xl font-bold text-slate-800 mb-4">Administrator</h4>
                    <p class="text-slate-400 text-sm italic">Unlimited access.</p>
                @else
                    <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </div>
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs mb-2">Search Usage</p>
                    <h4 class="text-2xl font-bold text-slate-800 mb-4">
                        {{ auth()->user()->userPlan?->searches_used ?? 0 }} <span class="text-slate-300 mx-1">/</span> {{ auth()->user()->userPlan?->search_limit ?? 0 }}
                    </h4>
                    <p class="text-slate-400 text-sm italic">Searches utilized.</p>
                @endif
            </div>

            <!-- Access Expiry -->
            @php
                $user = auth()->user();
                $hasExpiry = $user->role !== 'admin' && optional($user->userPlan)->expiry_date;
                $expiryDateStr = $hasExpiry ? $user->userPlan->expiry_date->format('M d, Y') : 'Lifetime';
                
                if ($hasExpiry) {
                    $days = (int) now()->startOfDay()->diffInDays($user->userPlan->expiry_date, false);
                    $isPast = $days < 0;
                    
                    if ($isPast) {
                        $statusText = 'EXPIRED';
                        $statusSubtext = 'Please renew plan.';
                        $progressColor = 'bg-red-500';
                        $progressWidth = '100%';
                    } elseif ($days === 0) {
                        $statusText = 'EXPIRES TODAY';
                        $statusSubtext = 'Critical: Renew today.';
                        $progressColor = 'bg-amber-500';
                        $progressWidth = '95%';
                    } else {
                        $statusText = $days . ' ' . Str::plural('DAY', $days) . ' LEFT';
                        $statusSubtext = 'Active Plan.';
                        $progressColor = $days < 7 ? 'bg-amber-500' : 'bg-brand-blue';
                        $progressWidth = min(100, max(5, ($days / 30) * 100)) . '%'; // Visual approx
                    }
                } else {
                    $statusText = 'NO EXPIRY';
                    $statusSubtext = 'Status OK.';
                    $progressColor = 'bg-brand-blue';
                    $progressWidth = '100%';
                }
            @endphp
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-6">
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs">Access Expiry</p>
                    <span class="text-slate-800 font-bold text-sm">{{ $expiryDateStr }}</span>
                </div>
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-slate-50 px-4 py-2 rounded-xl text-slate-800 font-bold text-sm border border-slate-100">
                        {{ $statusText }}
                    </div>
                    <span class="text-slate-400 text-sm italic">{{ $statusSubtext }}</span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="{{ $progressColor }} h-full transition-all" style="width: {{ $progressWidth }}"></div>
                </div>
            </div>

            <!-- System Health -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-6">
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs">System Health</p>
                    <span class="bg-emerald-50 text-emerald-700 px-3 py-1 rounded-full text-[10px] font-bold">STABLE</span>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">Hunter Pipeline</span>
                        <span class="text-slate-800 font-bold">Online</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">Enrichment</span>
                        <span class="text-slate-800 font-bold">Running</span>
                    </div>
                    <div class="flex justify-between items-center text-sm">
                        <span class="text-slate-500 font-medium">Exports</span>
                        <span class="text-slate-800 font-bold">Ready</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Latest Activity Row -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Latest Leads -->
            {{-- <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Latest Intelligence</h3>
                    <a href="{{ route('leads.index') }}" class="text-brand-blue text-sm font-bold hover:underline">View Hunter</a>
                </div>
                <div class="p-0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Name</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Company</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($latestLeads as $lead)
                            <tr class="hover:bg-slate-50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs mr-3 group-hover:bg-brand-blue group-hover:text-white transition-colors">
                                            {{ substr($lead->person_name, 0, 1) }}
                                        </div>
                                        <span class="text-sm font-bold text-slate-700">{{ $lead->person_name ?: '-' }}</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-sm text-slate-500">{{ $lead->company_name ?: '-' }}</td>
                                <td class="px-6 py-4">
                                    <span class="bg-emerald-50 text-emerald-700 px-2 py-1 rounded-md text-[10px] font-bold">HIGH</span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-6 py-12 text-center text-slate-400 text-sm">No intelligence records found.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div> --}}

            <!-- Recent Searches -->
            {{-- <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center">
                    <h3 class="font-bold text-slate-800">Recent Discoveries</h3>
                    <a href="{{ route('lead-searches.index') }}" class="text-brand-blue text-sm font-bold hover:underline">History</a>
                </div>
                <div class="p-0">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Parameters</th>
                                <th class="px-6 py-4 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100">
                            @forelse($latestSearches as $search)
                            <tr class="hover:bg-slate-50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="max-w-xs truncate text-sm font-bold text-slate-700">{{ $search->main_search_query ?: '-' }}</div>
                                    <div class="text-[10px] text-slate-400">{{ $search->city_by_search_param }}, {{ $search->country_by_search_param }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-2 py-1 rounded-md text-[10px] font-bold {{ $search->status === 'completed' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                        {{ strtoupper($search->status) }}
                                    </span>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="2" class="px-6 py-12 text-center text-slate-400 text-sm">No recent searches.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div> --}}
        </div>
    </div>
</x-app-layout>
