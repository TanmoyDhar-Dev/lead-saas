<div class="overflow-x-auto no-scrollbar">
    <table class="w-full text-left border-separate border-spacing-0">
        <thead class="bg-slate-50/50 sticky top-0 z-10 backdrop-blur-md">
            <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">
                @if(auth()->user()->is_admin)
                <th class="px-8 py-5 border-b border-slate-100">User</th>
                @endif
                <th class="px-6 py-5 border-b border-slate-100">Date</th>
                <th class="px-6 py-5 border-b border-slate-100">Amount</th>
                <th class="px-6 py-5 border-b border-slate-100">Method</th>
                <th class="px-6 py-5 border-b border-slate-100">Duration / Note</th>
                <th class="px-6 py-5 border-b border-slate-100">Status</th>
                <th class="px-8 py-5 border-b border-slate-100 text-right">Invoice</th>
            </tr>
        </thead>

        <tbody class="divide-y divide-slate-50">
            @forelse($payments as $p)
            <tr class="group hover:bg-slate-50/50 transition-all">
                @if(auth()->user()->is_admin)
                <td class="px-8 py-4">
                    <div class="font-bold text-slate-900 text-sm tracking-tight leading-none mb-1">{{ $p->user?->name ?? 'Deleted User' }}</div>
                    <div class="text-[10px] text-slate-400 font-medium lowercase tracking-tight">{{ $p->user?->email ?? '—' }}</div>
                </td>
                @endif

                <td class="px-6 py-4 text-sm font-bold text-slate-700">
                    {{ $p->created_at->format('M d, Y') }}
                </td>

                <td class="px-6 py-4 text-sm font-black text-slate-900">
                    ${{ number_format($p->amount ?? 0, 2) }}
                </td>

                <td class="px-6 py-4 text-sm text-slate-700">
                    {{ $p->gateway }}
                </td>

                <td class="px-6 py-4">
                    @php
                        $parts = explode('|', $p->duration_note ?? '');
                        $targetDate = $parts[0] ?: 'Subscription Update';
                        $targetDate = str_replace('Set to ', '', $targetDate);
                        $actionNote = $parts[1] ?? '';
                    @endphp
                    <div class="font-medium text-gray-900">{{ $targetDate }}</div>
                    @if($actionNote && $actionNote !== 'Payment Only')
                        <div class="text-sm text-gray-500">{{ $actionNote }}</div>
                    @endif
                </td>

                <td class="px-6 py-4">
                    @php
                    $statusLower = strtolower($p->status);
                    $badge = 'bg-slate-100 text-slate-600';
                    if ($statusLower === 'paid') $badge = 'bg-emerald-50 text-emerald-700';
                    if ($statusLower === 'failed') $badge = 'bg-rose-50 text-rose-700';
                    if ($statusLower === 'pending') $badge = 'bg-amber-50 text-amber-700';
                    @endphp
                    <span class="px-3 py-1 rounded-full text-[10px] font-black uppercase {{ $badge }}">
                        {{ $p->status }}
                    </span>
                </td>

                <td class="px-8 py-4 text-right">
                    <div class="flex items-center justify-end gap-4">
                        {{-- Download Link (Visible to All) --}}
                        <a href="{{ route('billing.invoice.download', $p->id) }}"
                            class="text-indigo-600 hover:text-indigo-900 text-xs font-black uppercase tracking-widest">
                            Download
                        </a>

                        {{-- Delete Action (Strictly Admin Only) --}}
                        @if(auth()->user()->is_admin)
                        <form action="{{ route('billing.destroy', $p->id) }}" method="POST"
                            onsubmit="return confirm('ADMIN: Are you sure you want to delete this record? This cannot be undone.');"
                            class="inline">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-rose-400 hover:text-rose-600 transition-colors p-1 rounded-md hover:bg-rose-50" title="Delete Record">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                </svg>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td class="px-8 py-10 text-center text-slate-400 font-bold" colspan="{{ auth()->user()->is_admin ? 7 : 6 }}">
                    No billing history found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($payments->hasPages())
<div class="px-6 py-4 bg-slate-50/50 border-t border-slate-100 ajax-pagination">
    {{ $payments->links() }}
</div>
@endif
