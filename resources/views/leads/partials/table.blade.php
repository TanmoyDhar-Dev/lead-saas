<div class="p-0 overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Name</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Position / Company</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Location</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">LinkedIn</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Details</th>
                @if(Auth::user()->isAdmin())
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Actions</th>
                @endif
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse($leads as $lead)
            <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" @click="openModal('{{ $lead->id }}')">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm mr-3 group-hover:bg-brand-blue group-hover:text-white transition-all shrink-0">
                            {{ strtoupper(substr($lead->person_name ?: '?', 0, 1)) }}
                        </div>
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-slate-800 truncate">{{ $lead->person_name ?: '-' }}</div>
                            @if(Auth::user()->isAdmin() && $lead->user)
                                <div class="text-[9px] text-slate-400">Owner: {{ $lead->user->name }}</div>
                            @elseif(Auth::user()->isAdmin() && !$lead->user)
                                <div class="text-[9px] text-red-400 font-bold">Unassigned</div>
                            @endif
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-xs font-medium text-slate-700">{{ $lead->position_by_search_param ?: $lead->position_by_apifiapi ?: '-' }}</div>
                    <div class="text-[10px] text-slate-400 italic">{{ $lead->company_name ?: '-' }}</div>
                </td>
                <td class="px-6 py-4">
                    <div class="text-xs text-slate-600">{{ $lead->city_by_search_param ?: '-' }}</div>
                    <div class="text-[10px] text-slate-400">{{ $lead->country_by_search_param ?: '-' }}</div>
                </td>
                <td class="px-6 py-4">
                    @if($lead->personal_email_address)
                        <span class="text-xs text-slate-700 font-medium">{{ $lead->personal_email_address }}</span>
                    @else
                        <span class="bg-slate-100 text-slate-400 px-2 py-0.5 rounded text-[10px] font-bold">N/A</span>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if($lead->personal__linkdin_url)
                        <a href="{{ $lead->personal__linkdin_url }}" target="_blank" @click.stop class="text-brand-blue hover:underline text-[10px] font-medium break-all max-w-[120px] block">
                            {{ Str::limit($lead->personal__linkdin_url, 28) }}
                        </a>
                    @else
                        <span class="text-slate-300 text-[10px]">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    <button @click.stop="openModal('{{ $lead->id }}')"
                            class="bg-slate-100 hover:bg-brand-blue hover:text-white text-slate-500 p-2 rounded-lg transition-all" title="View Details">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                    </button>
                </td>
                @if(Auth::user()->isAdmin())
                <td class="px-6 py-4 text-center">
                    <form action="{{ route('leads.destroy', $lead) }}" method="POST" @click.stop onsubmit="return confirm('Delete this lead permanently?');" class="inline">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 p-2 rounded-lg transition-all" title="Delete Lead">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                        </button>
                    </form>
                </td>
                @endif
            </tr>
            @empty
            <tr>
                <td colspan="{{ Auth::user()->isAdmin() ? '7' : '6' }}" class="px-6 py-16 text-center">
                    <div class="text-slate-300 mb-3">
                        <svg class="w-12 h-12 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 15l-2 5L9 9l11 4-5 2zm0 0l5 5"></path></svg>
                    </div>
                    <p class="text-slate-400 text-sm font-medium">No intelligence records found.</p>
                    <p class="text-slate-400 text-xs mt-1">Use the <span class="text-brand-blue font-bold">Run Hunter</span> above to discover leads.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($leads->hasPages())
<div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between">
    <div class="text-xs text-slate-500 font-medium">
        Showing {{ $leads->firstItem() }} to {{ $leads->lastItem() }} of {{ $leads->total() }} results
    </div>
    <div class="ajax-pagination">
        {{ $leads->links('vendor.pagination.custom-tailwind') }}
    </div>
</div>
@endif
