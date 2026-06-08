<x-app-layout>
    <x-slot name="header">
        Opened Emails
    </x-slot>
    <x-slot name="subheader">
        Sent emails that recipients have opened (tracking pixel)
    </x-slot>

    <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="bg-slate-50/50 border-b border-slate-100">
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Recipient</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subject</th>
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</th>
                        {{-- <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sent At</th> --}}
                        <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Opened At</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100">
                    @forelse($openedEmails as $recipient)
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-slate-800">{{ $recipient->lead?->full_name ?? 'Unknown' }}</div>
                            <div class="text-xs text-slate-500">{{ $recipient->lead?->company_name ?? '—' }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $recipient->lead?->personal_email ?? $recipient->lead?->company_email ?? '' }}</div>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600 max-w-xs truncate">{{ $recipient->subject ?? '—' }}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 rounded-md text-[10px] font-bold uppercase {{ $recipient->status === 'sent' ? 'bg-emerald-50 text-emerald-700' : 'bg-amber-50 text-amber-700' }}">
                                {{ $recipient->status }}
                            </span>
                        </td>
                        {{-- <td class="px-6 py-4 whitespace-nowrap">
                            @if($recipient->sent_at)
                                <div class="text-sm font-bold text-slate-800">{{ $recipient->sent_at->format('M d, Y') }}</div>
                                <div class="text-xs text-slate-500">{{ $recipient->sent_at->format('H:i') }}</div>
                            @else
                                <span class="text-slate-400 text-sm">—</span>
                            @endif
                        </td> --}}                     
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-brand-blue">{{ $recipient->opened_at?->format('M d, Y') }}</div>
                            <div class="text-xs text-slate-500">{{ $recipient->opened_at?->format('h:i A') }}</div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="px-6 py-16 text-center text-slate-400 text-sm">
                            No opened emails yet. Opens will appear here once recipients view your outreach.
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
