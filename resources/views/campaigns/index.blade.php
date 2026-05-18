<x-app-layout>
    <x-slot name="header">
        Campaigns
    </x-slot>

    <x-slot name="subheader">
        eGSales AI Intelligence • Outreach Automation
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('campaigns.create') }}" class="bg-brand-blue text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors flex items-center shadow-lg shadow-blue-500/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            NEW CAMPAIGN
        </a>
    </x-slot>

    <div class="space-y-6">
        {{-- Admin filter --}}
        @if(Auth::user()->isAdmin())
        <div class="bg-white rounded-2xl p-5 shadow-sm border border-slate-100">
            <form method="GET" action="{{ route('campaigns.index') }}" class="flex flex-col sm:flex-row items-end space-y-3 sm:space-y-0 sm:space-x-4">
                <div class="flex-1 w-full">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Filter by Owner</label>
                    <select name="user_id" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        <option value="">All Users</option>
                        @foreach(\App\Models\User::all() as $u)
                            <option value="{{ $u->id }}" {{ request('user_id') == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="flex space-x-2">
                    <button type="submit" class="bg-slate-800 text-white font-bold py-2.5 px-6 rounded-xl text-sm hover:bg-slate-900 transition-colors">FILTER</button>
                    <a href="{{ route('campaigns.index') }}" class="bg-white border border-slate-200 text-slate-600 font-bold py-2.5 px-6 rounded-xl text-sm hover:bg-slate-50 transition-colors">RESET</a>
                </div>
            </form>
        </div>
        @endif

        {{-- Campaigns Table --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-0 overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Campaign</th>
                            @if(Auth::user()->isAdmin())
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Owner</th>
                            @endif
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recipients</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Created</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($campaigns as $campaign)
                        <tr class="hover:bg-slate-50 transition-colors group">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="w-10 h-10 rounded-xl bg-indigo-50 flex items-center justify-center text-indigo-600 mr-4 group-hover:bg-brand-blue group-hover:text-white transition-all">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    </div>
                                    <div>
                                        <div class="text-sm font-bold text-slate-800">{{ $campaign->name }}</div>
                                        <div class="text-[10px] text-slate-400">ID: {{ Str::limit($campaign->id, 8) }}</div>
                                    </div>
                                </div>
                            </td>
                            @if(Auth::user()->isAdmin())
                            <td class="px-6 py-4">
                                <span class="text-xs text-slate-600 font-medium">{{ $campaign->user ? $campaign->user->name : '-' }}</span>
                            </td>
                            @endif
                            <td class="px-6 py-4">
                                @if($campaign->status === 'sent' || $campaign->status === 'completed')
                                    <span class="bg-emerald-50 text-emerald-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">{{ strtoupper($campaign->status) }}</span>
                                @elseif($campaign->status === 'draft')
                                    <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg text-[10px] font-bold">DRAFT</span>
                                @elseif($campaign->status === 'sending')
                                    <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold animate-pulse">SENDING</span>
                                @else
                                    <span class="bg-amber-50 text-amber-700 px-2.5 py-1 rounded-lg text-[10px] font-bold">{{ strtoupper($campaign->status) }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm font-bold text-slate-700">{{ $campaign->campaign_recipients_count }}</span>
                                <span class="text-[10px] text-slate-400 ml-1">leads</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-slate-700 font-medium">{{ $campaign->created_at->format('M d, Y') }}</div>
                                <div class="text-[10px] text-slate-400">{{ $campaign->created_at->format('h:i A') }}</div>
                            </td>
                            <td class="px-6 py-4 text-right space-x-2">
                                @if(! $campaign->sent_to_n8n_at)
                                    <a href="{{ route('campaigns.confirm', $campaign) }}" class="bg-brand-blue/10 hover:bg-brand-blue hover:text-white text-brand-blue px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all inline-block">
                                        SETUP
                                    </a>
                                @endif
                                <a href="{{ route('campaigns.show', $campaign) }}" class="bg-slate-100 hover:bg-brand-blue hover:text-white text-slate-600 px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all inline-block">
                                    VIEW
                                </a>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="{{ Auth::user()->isAdmin() ? '6' : '5' }}" class="px-6 py-16 text-center">
                                <div class="text-slate-300 mb-3">
                                    <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                </div>
                                <p class="text-slate-400 text-sm font-medium">No campaigns yet.</p>
                                <a href="{{ route('campaigns.create') }}" class="text-brand-blue text-xs font-bold hover:underline mt-1 inline-block">Create your first campaign →</a>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($campaigns->hasPages())
            <div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between">
                <div class="text-xs text-slate-500 font-medium">
                    Showing {{ $campaigns->firstItem() }} to {{ $campaigns->lastItem() }} of {{ $campaigns->total() }} campaigns
                </div>
                <div>
                    {{ $campaigns->links('vendor.pagination.custom-tailwind') }}
                </div>
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
