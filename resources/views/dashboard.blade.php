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

        <!-- Stats Cards Row -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Account Authority -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100 flex flex-col items-center text-center">
                <div class="w-12 h-12 bg-indigo-50 rounded-2xl flex items-center justify-center text-indigo-600 mb-6">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                </div>
                <p class="text-slate-500 font-bold uppercase tracking-wider text-xs mb-2">Account Authority</p>
                <h4 class="text-2xl font-bold text-slate-800 mb-4">{{ auth()->user()->role === 'admin' ? 'Administrator' : 'Verified User' }}</h4>
                <p class="text-slate-400 text-sm italic">{{ auth()->user()->role === 'admin' ? 'Unlimited access.' : 'Assigned access.' }}</p>
            </div>

            <!-- Access Expiry -->
            <div class="bg-white rounded-3xl p-8 shadow-sm border border-slate-100">
                <div class="flex justify-between items-start mb-6">
                    <p class="text-slate-500 font-bold uppercase tracking-wider text-xs">Access Expiry</p>
                    <span class="text-slate-800 font-bold text-sm">Lifetime</span>
                </div>
                <div class="flex items-center space-x-4 mb-4">
                    <div class="bg-slate-50 px-4 py-2 rounded-xl text-slate-800 font-bold text-sm border border-slate-100">
                        NO EXPIRY
                    </div>
                    <span class="text-slate-400 text-sm italic">Status OK.</span>
                </div>
                <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                    <div class="bg-brand-blue h-full w-full"></div>
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
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
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
            </div>

            <!-- Recent Searches -->
            <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
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
            </div>
        </div>
    </div>
</x-app-layout>
