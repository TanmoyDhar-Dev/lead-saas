@php
    $hasPendingOutreach = $leads->contains(function ($lead) {
        return in_array($lead->campaignRecipients->first()?->status, ['pending', 'queued'], true);
    });
@endphp
<div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden"
     data-total-count="{{ $leads->total() }}"
     data-has-pending-outreach="{{ $hasPendingOutreach ? '1' : '0' }}">
    <div class="p-0 overflow-x-auto">
        <table class="w-full text-left border-collapse min-w-[1000px]">
            <thead>
                <tr class="bg-slate-50/50 border-b border-slate-100">
                    <th class="px-6 py-5 sticky left-0 z-10 bg-slate-50/90 backdrop-blur border-b border-r border-slate-100 text-[10px] font-bold text-slate-400 uppercase tracking-widest w-10">
                        <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                    </th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Full Name</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Job Title</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Position</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">LinkedIn</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Outreach Status</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Company Email</th>
                    <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right whitespace-nowrap sticky right-0 z-10 bg-slate-50/90 backdrop-blur border-b border-l border-slate-100">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100">
                @forelse ($leads as $lead)
                <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" @click="openModal('{{ $lead->id }}')">
                    <td class="px-6 py-4 sticky left-0 z-10 bg-white group-hover:bg-slate-50 border-r border-slate-100" @click.stop>
                        <input type="checkbox" value="{{ $lead->id }}" x-model="selectedLeadIds" class="lead-checkbox w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                    </td>
                    <td class="px-6 py-4 min-w-[200px]">
                        <div class="flex items-center">
                            <div class="w-8 h-8 rounded-full bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-xs mr-3 border border-slate-200 shrink-0">
                                {{ substr($lead->full_name ?: '?', 0, 1) }}
                            </div>
                            <button type="button" @click.stop="openModal('{{ $lead->id }}')"
                                    class="text-sm font-bold text-brand-blue hover:underline text-left leading-tight whitespace-nowrap">
                                {{ $lead->full_name ?: 'Unknown' }}
                            </button>
                        </div>
                    </td>
                    <td class="px-6 py-4"><div class="text-xs font-medium text-slate-700 whitespace-nowrap">{{ $lead->job_title ?: '-' }}</div></td>
                    <td class="px-6 py-4"><div class="text-xs font-medium text-slate-700 whitespace-nowrap uppercase">{{ $lead->position ?: '-' }}</div></td>
                    <td class="px-6 py-4" @click.stop>
                        @if($lead->linkedin_url)
                        <a href="{{ $lead->linkedin_url }}" target="_blank" class="text-[10px] text-brand-blue font-bold hover:underline flex items-center whitespace-nowrap">
                            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg> Profile
                        </a>
                        @else
                        <span class="text-[10px] text-slate-300">-</span>
                        @endif
                    </td>
                    <td class="px-6 py-4" data-outreach-status="{{ $lead->campaignRecipients->first()?->status ?? '' }}">
                        @include('leads.partials.outreach-status-badge', ['lead' => $lead])
                    </td>
                    <td class="px-6 py-4"><div class="text-xs font-medium text-slate-700 whitespace-nowrap">{{ $lead->company_email ?: '-' }}</div></td>
                    
                    <td class="px-6 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-slate-50 border-l border-slate-100" @click.stop>
                        <div class="flex items-center justify-end space-x-2">
                            <button type="button" @click.stop="openModal('{{ $lead->id }}')"
                                    class="text-slate-400 hover:text-brand-blue p-2 rounded-lg hover:bg-brand-blue/5 transition-all" title="View Details">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                            </button>
                            
                            <form action="{{ route('leads.destroy', $lead->id) }}" method="POST" onsubmit="return confirm('Delete this lead?');" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-slate-300 hover:text-red-500 p-2 rounded-lg hover:bg-red-50 transition-all" title="Delete Lead">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="px-6 py-16 text-center">
                        <p class="text-slate-400 text-sm font-medium">No leads found matching your criteria.</p>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($leads->hasPages())
    <div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between pagination">
        <div class="text-xs text-slate-500 font-medium">
            Showing {{ $leads->firstItem() }} to {{ $leads->lastItem() }} of {{ $leads->total() }} leads
        </div>
        <div>
            {{ $leads->links('vendor.pagination.custom-tailwind') }}
        </div>
    </div>
    @endif
</div>
