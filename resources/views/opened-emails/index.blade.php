<x-app-layout>
    <x-slot name="header">
        Opened Emails
    </x-slot>
    <x-slot name="subheader">
        Sent emails that recipients have opened
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recipient</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Source</th>
                        {{-- <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subject</th> --}}
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sent At</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Opened At</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Opens</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($openedEmails as $row)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ $row->recipient_name }}</div>
                            <div class="text-xs text-slate-500">{{ $row->recipient_org }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $row->recipient_email }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase {{ $row->source === 'imported' ? 'bg-violet-50 text-violet-700' : 'bg-slate-100 text-slate-600' }}">
                                {{ $row->source === 'imported' ? 'Imported' : 'Extraction' }}
                            </span>
                        </td>
                        {{-- <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">{{ $row->subject ?? '—' }}</td> --}}
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase {{ $row->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $row->status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($row->sent_at)
                                <div class="text-sm font-bold text-slate-800">{{ $row->sent_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $row->sent_at->format('h:i A') }}</div>
                            @else
                                <span class="text-slate-400 text-sm">—</span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-brand-blue">{{ $row->opened_at?->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $row->opened_at?->format('h:i A') }}</div>
                        </td>
                        <td class="px-6 py-4 text-center">
                            <span class="inline-flex items-center justify-center min-w-[2rem] px-2 py-1 rounded-lg bg-blue-50 text-brand-blue text-xs font-bold">
                                {{ $row->open_count ?? 0 }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-16 text-center text-slate-400 text-sm">
                            No opened emails yet. Open tracking is included for extraction and imported outreach (send and draft).
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($openedEmails->hasPages())
            <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                {{ $openedEmails->links() }}
            </div>
        @endif
    </div>
</x-app-layout>
