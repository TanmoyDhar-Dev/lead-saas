<div class="p-0 overflow-x-auto">
    <table class="w-full text-left border-collapse min-w-[1000px]">
        <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Organization</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Contact</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Emails</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Phones</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Imported</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right whitespace-nowrap sticky right-0 z-10 bg-slate-50/90 backdrop-blur border-l border-slate-100">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($importedLeads as $lead)
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="px-6 py-4">
                    <div class="text-sm font-bold text-slate-800">{{ $lead->organization_name ?: '—' }}</div>
                    @if($lead->address)
                        <div class="text-[11px] text-slate-400 mt-0.5 line-clamp-1 max-w-[240px]" title="{{ $lead->address }}">{{ $lead->address }}</div>
                    @endif
                </td>
                <td class="px-6 py-4">
                    <div class="text-sm font-medium text-slate-700">{{ $lead->contact_name ?: '—' }}</div>
                </td>
                <td class="px-6 py-4">
                    @php $emails = $lead->emails; @endphp
                    @if($emails->isEmpty())
                        <span class="text-[10px] text-slate-300">—</span>
                    @else
                        <div class="text-xs font-medium text-slate-700">{{ $emails->firstWhere('is_primary', true)?->email ?? $emails->first()->email }}</div>
                        @if($emails->count() > 1)
                            <div class="text-[10px] text-slate-400 mt-0.5">+{{ $emails->count() - 1 }} more</div>
                        @endif
                    @endif
                </td>
                <td class="px-6 py-4">
                    @php $phones = $lead->phones; @endphp
                    @if($phones->isEmpty())
                        <span class="text-[10px] text-slate-300">—</span>
                    @else
                        <div class="text-xs font-medium text-slate-700">{{ $phones->firstWhere('is_primary', true)?->phone ?? $phones->first()->phone }}</div>
                        @if($phones->count() > 1)
                            <div class="text-[10px] text-slate-400 mt-0.5">+{{ $phones->count() - 1 }} more</div>
                        @endif
                    @endif
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 font-medium whitespace-nowrap">
                    {{ optional($lead->created_at)->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-slate-50 border-l border-slate-100">
                    <div class="flex items-center justify-end space-x-1">
                        <button type="button" @click="openDetail('{{ $lead->id }}')" class="p-2 text-slate-400 hover:text-brand-blue transition-colors" title="View">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                        </button>
                        <button type="button" @click="openEdit('{{ $lead->id }}')" class="p-2 text-slate-400 hover:text-brand-blue transition-colors" title="Edit">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                        </button>
                        <form action="{{ route('imported-leads.destroy', $lead) }}" method="POST" class="inline" onsubmit="return confirm('Delete this imported lead?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors" title="Delete">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                            </button>
                        </form>
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="6" class="px-6 py-16 text-center">
                    <p class="text-slate-400 text-sm font-medium">No imported leads yet. Click Import Leads to upload a CSV or Excel file.</p>
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($importedLeads->hasPages())
<div class="px-6 py-5 border-t border-slate-100 bg-slate-50/30 flex items-center justify-between pagination">
    <div class="text-xs text-slate-500 font-medium">
        Showing {{ $importedLeads->firstItem() }} to {{ $importedLeads->lastItem() }} of {{ $importedLeads->total() }} leads
    </div>
    <div>
        {{ $importedLeads->links('vendor.pagination.custom-tailwind') }}
    </div>
</div>
@endif
