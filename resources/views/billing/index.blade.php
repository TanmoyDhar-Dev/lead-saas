<x-app-layout>
    <x-slot name="header">
        Billing History
    </x-slot>

    <x-slot name="subheader">
        @if(auth()->user()->is_admin)
            Admin view — all transactions
        @else
            Your payments & plan status
        @endif
    </x-slot>

    <div x-data="billingManagement()" class="space-y-6">
        <div class="flex flex-col sm:flex-row sm:items-end justify-between gap-4 bg-white p-6 rounded-[2rem] border border-slate-200/60 shadow-sm">
            <div>
                <h1 class="text-xl font-black text-slate-900 tracking-tight leading-none mb-1">
                    Billing <span class="text-indigo-600 italic">Records</span>
                </h1>
                <p class="text-[10px] text-slate-400 font-bold uppercase tracking-tight">
                    @if(auth()->user()->is_admin)
                        Admin Console • Audit Log
                    @else
                        Personal Billing Dashboard
                    @endif
                </p>
            </div>

            {{-- Admin filters --}}
            @if(auth()->user()->is_admin)
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
                <select x-model="filters.user_id" class="flex-1 sm:flex-none bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none py-2.5 px-4 transition-all">
                    <option value="">All users</option>
                    @foreach($users as $u)
                    <option value="{{ $u->id }}">
                        {{ $u->name }} ({{ $u->email }})
                    </option>
                    @endforeach
                </select>

                <select x-model="filters.status" class="flex-1 sm:flex-none bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none py-2.5 px-4 transition-all">
                    <option value="">All status</option>
                    <option value="Paid">Paid</option>
                    <option value="Pending">Pending</option>
                    <option value="Failed">Failed</option>
                </select>
            </div>
            @endif
        </div>

        {{-- Summary cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white border border-slate-200/60 rounded-[2rem] p-6 shadow-sm">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">{{ auth()->user()->is_admin ? 'Total Revenue' : 'Total Paid Amount' }}</p>
                <p class="text-2xl font-black text-slate-900" x-text="totalRevenue">${{ number_format($totalRevenue, 2) }}</p>
            </div>

            <div class="bg-white border border-slate-200/60 rounded-[2rem] p-6 shadow-sm">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Paid Invoices</p>
                <p class="text-2xl font-black text-slate-900" x-text="paidCount">{{ $paidCount }}</p>
            </div>

            <div class="bg-white border border-slate-200/60 rounded-[2rem] p-6 shadow-sm">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Last Payment</p>
                <div>
                    <p class="text-sm font-black text-slate-900 mb-0.5" x-text="lastPaid">
                        {{ $lastPaid ? $lastPaid->created_at->format('M d, Y') : '—' }}
                    </p>
                    <p class="text-[10px] text-slate-400 font-bold uppercase" x-show="lastPaidSub" x-text="lastPaidSub">
                        {{ $lastPaid ? '$' . number_format($lastPaid->amount, 2) . ' • ' . $lastPaid->gateway : '' }}
                    </p>
                </div>
            </div>

            {{-- User plan card (only for non-admin) --}}
            @if(!auth()->user()->is_admin)
            @php
                $u = auth()->user();
                $plan = $u->userPlan;
                $status = $plan ? $plan->security_label : 'No Plan Configured';
                $hasNeverBeenSet = is_null($plan) || is_null($plan->expiry_date); 
                $isExpired = $plan && $plan->expiry_date && $plan->expiry_date->isPast();
                $isRevoked = $plan && $plan->security_status === \App\Models\UserPlan::SECURITY_INACTIVE_REVOKED;
                $isPastDue = $plan && $plan->security_status === \App\Models\UserPlan::SECURITY_PAST_DUE;
                $daysLeft = ($plan && $plan->expiry_date && !$isExpired && !$isRevoked) ? (int) ceil(now()->diffInDays($plan->expiry_date, false)) : 0;
            @endphp
            <div class="bg-white border border-slate-200/60 rounded-[2rem] p-6 shadow-sm">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Plan Status</p>
                <p class="text-sm font-black {{ ($plan && $plan->expiry_date && !$isExpired && !$isRevoked && !$isPastDue) ? 'text-emerald-600' : ($hasNeverBeenSet ? 'text-slate-400' : 'text-rose-600') }} mb-0.5">
                    @if($isRevoked) Access Revoked
                    @elseif($isPastDue) Payment Failed
                    @elseif($hasNeverBeenSet) Not Set
                    @elseif($isExpired) Expired
                    @else Active
                    @endif
                </p>
                <p class="text-[10px] text-slate-400 font-bold uppercase">
                    @if($plan && $plan->expiry_date && !$isExpired && !$isRevoked)
                        Expires: {{ $plan->expiry_date->format('M d, Y') }} ({{ $daysLeft }} days)
                    @elseif($isExpired)
                        Ended on {{ $plan->expiry_date->format('M d, Y') }}
                    @else
                        No active expiry date
                    @endif
                </p>
            </div>
            @else
            <div class="bg-white border border-slate-200/60 rounded-[2rem] p-6 shadow-sm">
                <p class="text-[8px] font-black text-slate-400 uppercase tracking-widest mb-1">Access Role</p>
                <p class="text-sm font-black text-indigo-600 mb-0.5">System Admin</p>
                <p class="text-[10px] text-slate-400 font-bold uppercase">Permanent Access</p>
            </div>
            @endif
        </div>

        {{-- Billing table wrapper --}}
        <div id="table-container" class="bg-white border border-slate-200/60 rounded-[2rem] overflow-hidden shadow-sm transition-all duration-200"
             :class="loading ? 'opacity-50' : ''">
            @include('billing.partials.table')
        </div>
    </div>

    <script>
    function billingManagement() {
        return {
            filters: {
                user_id: '{{ request('user_id', '') }}',
                status: '{{ request('status', '') }}'
            },
            loading: false,
            totalRevenue: '${{ number_format($totalRevenue, 2) }}',
            paidCount: '{{ $paidCount }}',
            lastPaid: '{{ $lastPaid ? $lastPaid->created_at->format('M d, Y') : '—' }}',
            lastPaidSub: '{{ $lastPaid ? '$' . number_format($lastPaid->amount, 2) . ' • ' . $lastPaid->gateway : '' }}',
            
            init() {
                // Watch for changes in filters and trigger fetch
                this.$watch('filters.user_id', () => this.fetchBilling());
                this.$watch('filters.status', () => this.fetchBilling());
                
                // Listen to pagination clicks inside table-container
                document.addEventListener('click', (e) => {
                    const link = e.target.closest('.ajax-pagination a');
                    if (link) {
                        e.preventDefault();
                        this.fetchBilling(link.href);
                    }
                });
            },
            
            fetchBilling(url = null) {
                this.loading = true;
                
                // Build URL
                let fetchUrl = url || '{{ route('billing.index') }}';
                let parsedUrl = new URL(fetchUrl);
                
                // If not calling a specific pagination URL, set query params from filters
                if (!url) {
                    if (this.filters.user_id) {
                        parsedUrl.searchParams.set('user_id', this.filters.user_id);
                    } else {
                        parsedUrl.searchParams.delete('user_id');
                    }
                    
                    if (this.filters.status) {
                        parsedUrl.searchParams.set('status', this.filters.status);
                    } else {
                        parsedUrl.searchParams.delete('status');
                    }
                }
                
                // Perform Fetch
                fetch(parsedUrl.toString(), {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(res => res.json())
                .then(data => {
                    // Update table html
                    document.getElementById('table-container').innerHTML = data.table;
                    
                    // Update stats
                    this.totalRevenue = data.totalRevenue;
                    this.paidCount = data.paidCount;
                    this.lastPaid = data.lastPaid;
                    this.lastPaidSub = data.lastPaidSub;
                    
                    // Update browser URL query params without reloading page
                    window.history.pushState({}, '', parsedUrl.toString());
                })
                .catch(err => {
                    console.error('Error fetching billing:', err);
                })
                .finally(() => {
                    this.loading = false;
                });
            }
        }
    }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
    </style>
</x-app-layout>
