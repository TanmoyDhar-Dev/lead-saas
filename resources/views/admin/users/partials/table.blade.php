<div class="p-0 overflow-x-auto no-scrollbar">
    <table class="w-full text-left border-separate border-spacing-0">
        <thead class="bg-slate-50/50 sticky top-0 z-10 backdrop-blur-md">
            <tr class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] border-b border-slate-100">
                <th class="px-8 py-5 border-b border-slate-100">Operator</th>
                <th class="px-6 py-5 border-b border-slate-100 text-center">Security</th>
                <th class="px-6 py-5 border-b border-slate-100">Quotas</th>
                <th class="px-6 py-5 border-b border-slate-100 text-center">Expiry</th>
                <th class="px-8 py-5 border-b border-slate-100 text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-50">
            @forelse($users as $user)
            <tr class="group hover:bg-slate-50/50 transition-all">
                <td class="px-8 py-4">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-lg bg-slate-100 flex items-center justify-center font-black text-slate-400 group-hover:bg-indigo-600 group-hover:text-white transition-all uppercase text-[10px] border border-slate-200">
                            {{ strtoupper(substr($user->name, 0, 2)) }}
                        </div>
                        <div>
                            <div class="font-bold text-slate-900 text-sm tracking-tight leading-none mb-1">{{ $user->name }}</div>
                            <div class="text-[10px] text-slate-400 font-medium lowercase tracking-tight">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    @if($user->is_admin)
                        <span class="px-2 py-0.5 bg-slate-900 text-white text-[8px] font-black uppercase rounded shadow-sm">System Access</span>
                    @else
                        <div class="flex items-center justify-center gap-2">
                            <div class="w-1.5 h-1.5 rounded-full {{ str_contains((string)$user->subscription_status, 'Active') ? 'bg-emerald-500' : 'bg-rose-500' }}"></div>
                            <span class="text-[10px] font-bold text-slate-600 uppercase tracking-tighter">{{ $user->subscription_status }}</span>
                        </div>
                    @endif
                </td>
                <td class="px-6 py-4">
                    @if(!$user->is_admin)
                    <div class="flex items-center gap-4">
                        {{-- Query Quota --}}
                        <div class="w-24">
                            <div class="flex justify-between items-end text-[8px] font-black uppercase mb-1.5">
                                <span class="text-slate-400 tracking-widest">Queries</span>
                                <span class="text-slate-900 bg-slate-100 px-1.5 py-0.5 rounded shadow-sm">
                                    {{ $user->profile_usage }}<span class="text-slate-400 mx-0.5">/</span>{{ $user->query_limit }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden border border-slate-200/50">
                                <div class="h-full bg-indigo-500 rounded-full transition-all duration-500" 
                                     style="width: {{ min(100, ($user->query_limit > 0 ? ($user->profile_usage / $user->query_limit) * 100 : 0)) }}%"></div>
                            </div>
                        </div>

                        {{-- Lead Quota --}}
                        <div class="w-24">
                            <div class="flex justify-between items-end text-[8px] font-black uppercase mb-1.5">
                                <span class="text-slate-400 tracking-widest">Leads</span>
                                <span class="text-slate-900 bg-slate-100 px-1.5 py-0.5 rounded shadow-sm">
                                    {{ $user->results_count }}<span class="text-slate-400 mx-0.5">/</span>{{ $user->profile_limit }}
                                </span>
                            </div>
                            <div class="h-1.5 w-full bg-slate-100 rounded-full overflow-hidden border border-slate-200/50">
                                <div class="h-full bg-amber-500 rounded-full transition-all duration-500" 
                                     style="width: {{ min(100, ($user->profile_limit > 0 ? ($user->results_count / $user->profile_limit) * 100 : 0)) }}%"></div>
                            </div>
                        </div>
                    </div>
                    @else
                        <span class="text-[9px] font-black text-slate-300 uppercase tracking-widest italic flex items-center gap-2">
                            <div class="w-1 h-1 rounded-full bg-slate-200"></div> System Root
                        </span>
                    @endif
                </td>
                <td class="px-6 py-4 text-center">
                    @if(!$user->is_admin && $user->access_until)
                        <div class="text-[11px] font-bold text-slate-700">{{ \Carbon\Carbon::parse($user->access_until)->format('d M, Y') }}</div>
                        @php
                            $isPast = \Carbon\Carbon::parse($user->access_until)->isPast();
                            $days = ceil(now()->diffInDays(\Carbon\Carbon::parse($user->access_until), false));
                        @endphp
                        <div class="text-[8px] font-black uppercase {{ $isPast ? 'text-rose-400' : 'text-indigo-400' }}">
                            {{ $isPast ? 'Terminated' : $days . ' Days' }}
                        </div>
                    @else
                        <span class="text-slate-300 text-[9px] font-bold uppercase tracking-widest">Permanent</span>
                    @endif
                </td>
                <td class="px-8 py-4 text-right">
                    <div class="flex items-center justify-end gap-2">
                        @if(!$user->is_admin)
                            <button type="button" onclick="openStatusModal('{{ $user->id }}', '{{ $user->email }}')" class="text-[9px] font-black uppercase tracking-widest text-indigo-600 bg-indigo-50 hover:bg-indigo-600 hover:text-white px-3 py-1.5 rounded-lg transition-all">Security</button>
                            <button type="button" onclick="openLimitModal('{{ $user->id }}', '{{ $user->name }}', '{{ $user->query_limit }}', '{{ $user->profile_limit }}')" class="text-[9px] font-black uppercase tracking-widest text-amber-600 bg-amber-50 hover:bg-amber-600 hover:text-white px-3 py-1.5 rounded-lg transition-all">Limits</button>
                            <button type="button" onclick="openPaymentModal('{{ $user->id }}', '{{ $user->email }}')" class="text-[9px] font-black uppercase tracking-widest text-emerald-600 bg-emerald-50 hover:bg-emerald-600 hover:text-white px-3 py-1.5 rounded-lg transition-all">Billing</button>
                            <button type="button" onclick="openDeleteModal('{{ $user->id }}', '{{ $user->email }}')" class="text-[9px] font-black uppercase tracking-widest text-rose-500 hover:bg-rose-500 hover:text-white px-3 py-1.5 rounded-lg transition-all">Delete</button>
                        @else
                            <span class="text-[8px] font-black text-slate-200 uppercase tracking-widest bg-slate-50 px-3 py-1.5 rounded-lg border border-slate-100">Root Protected</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-8 py-10 text-center text-slate-400 font-bold">
                    No users found.
                </td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="px-6 py-6 border-t border-slate-100 bg-slate-50/30 ajax-pagination">
    {{ $users->links('vendor.pagination.custom-tailwind') }}
</div>
@endif
