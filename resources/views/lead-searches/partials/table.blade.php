<div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-left border-collapse">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Date / Time</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Owner</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target Location</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Industry</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Position</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Leads</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($searches as $search)
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-6 py-4">
                        @php
                            $date = $search->started_at ?? $search->created_at ?? now();
                        @endphp
                        <div class="text-sm font-medium text-slate-800">{{ $date->format('M d, Y') }}</div>
                        <div class="text-[10px] text-slate-400">{{ $date->format('h:i A') }}</div>
                    </td>
                    
                    <td class="px-6 py-4">
                        @if(Auth::user()->isAdmin())
                            <div class="flex items-center">
                                <div class="w-7 h-7 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-[10px] mr-2">
                                    {{ $search->user ? substr($search->user->name, 0, 1) : '?' }}
                                </div>
                                <div>
                                    <div class="text-xs text-slate-600 font-medium">{{ $search->user ? $search->user->name : 'Unknown' }}</div>
                                    <div class="text-[9px] text-slate-400">{{ $search->user ? $search->user->email : '' }}</div>
                                </div>
                            </div>
                        @else
                            <span class="text-xs text-slate-600 font-medium">You</span>
                        @endif
                    </td>

                    <td class="px-6 py-4">
                        @php
                            $location = trim($search->target_location);
                            if (empty($location)) $location = 'Not specified';
                        @endphp
                        <div class="text-sm text-slate-700 font-semibold uppercase">{{ $location }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-700 font-semibold uppercase">{{ $search->industry ?: 'Any Industry' }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="text-sm text-slate-700 font-semibold uppercase" title="{{ $search->position }}">{{ $search->position ?: 'Any Position' }}</div>
                    </td>

                    <td class="px-6 py-4">
                        <div class="flex items-center">
                            <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold border border-blue-100">
                                {{ $search->leads_count }} LEADS
                            </span>
                        </div>
                    </td>

                    <td class="px-6 py-4">
                        @if($search->status === 'completed')
                            <span class="bg-green-100 text-green-700 font-medium px-2.5 py-0.5 rounded-full text-[10px] uppercase">Completed</span>
                        @elseif($search->status === 'failed')
                            <span class="bg-red-50 text-red-700 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase">Failed</span>
                        @elseif($search->status === 'processing' || $search->status === 'pending')
                            <span class="bg-blue-50 text-blue-700 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase animate-pulse">Processing</span>
                        @else
                            <span class="bg-slate-100 text-slate-600 px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase">{{ $search->status }}</span>
                        @endif
                    </td>

                    <td class="px-6 py-4 text-right">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('lead-searches.leads', $search) }}" class="bg-brand-blue/10 hover:bg-brand-blue text-brand-blue hover:text-white px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all uppercase tracking-wide">
                                View Leads
                            </a>
                            
                            <form action="{{ route('lead-searches.destroy', $search) }}" method="POST" onsubmit="return confirm('This will delete this extraction history and all leads collected under it. This cannot be undone.');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-50 hover:bg-red-500 text-red-500 hover:text-white p-1.5 rounded-lg transition-all" title="Delete Search & Leads">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="px-6 py-16 text-center">
                        <div class="text-slate-300 mb-3">
                            <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        </div>
                        <p class="text-slate-400 text-sm font-medium">No extraction history found matching your criteria.</p>
                        <a href="{{ route('lead-searches.create') }}" class="text-brand-blue text-xs font-bold hover:underline mt-1 inline-block">Run new extraction →</a>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($searches->hasPages())
    <div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between pagination">
        <div class="text-xs text-slate-500 font-medium">
            Showing {{ $searches->firstItem() }} to {{ $searches->lastItem() }} of {{ $searches->total() }} searches
        </div>
        <div>
            {{ $searches->links('vendor.pagination.custom-tailwind') }}
        </div>
    </div>
    @endif
</div>
