<x-app-layout>
    <x-slot name="header">
        Lead Intelligence
    </x-slot>

    <x-slot name="subheader">
        eGSales AI Engine • Visible Leads: {{ $leads->total() }}
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('leads.export') }}" class="bg-white border border-slate-200 text-slate-700 px-4 py-2 rounded-xl text-sm font-bold hover:bg-slate-50 transition-colors flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
            EXPORT CSV
        </a>
    </x-slot>

    <div class="space-y-6" x-data="leadManager()">

        @php
            $user = auth()->user();
            $plan = $user->userPlan;
            $searchLimitReached = $user->role !== 'admin' && $plan && $plan->search_limit > 0 && $plan->searches_used >= $plan->search_limit;
            $leadLimitReached = $user->role !== 'admin' && $plan && $plan->lead_limit > 0 && $user->leads()->count() >= $plan->lead_limit;
            $limitReached = $searchLimitReached || $leadLimitReached;
        @endphp

        {{-- ===== RUN HUNTER PANEL ===== --}}
        
        {{-- Session Flash Messages --}}
        @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-start">
                <svg class="w-5 h-5 text-emerald-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <p class="text-sm text-emerald-800 font-bold">{{ session('success') }}</p>
            </div>
        @endif
        @if(session('error') || $errors->any())
            <div class="bg-red-50 border border-red-200 p-4 rounded-2xl flex items-start">
                <svg class="w-5 h-5 text-red-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <div>
                    <p class="text-sm text-red-800 font-bold">{{ session('error') ?? 'Please fix the errors below.' }}</p>
                    @if($errors->any())
                        <ul class="text-xs text-red-700 mt-1 list-disc list-inside">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        @endif

        <div class="bg-navy-900 rounded-3xl p-3 shadow-xl" x-data="{ expanded: false, volume: 10, volumeInvalid: false }">
            <form action="{{ route('lead-searches.store') }}" method="POST">
                @csrf

                <div class="flex flex-col md:flex-row items-stretch md:items-start space-y-2 md:space-y-0 md:space-x-2">
                    <div class="flex-1">
                        <input type="text" name="target_location" placeholder="Target Location (e.g. San Francisco, US)" required
                               class="w-full bg-navy-800 border-none rounded-2xl text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-blue py-4 px-5 text-sm">
                    </div>

                    {{-- Volume field: always visible --}}
                    <div class="flex flex-col w-full md:w-44">
                        <div class="relative">
                            <input type="number" name="volume" id="volume-input"
                                   x-model.number="volume"
                                   min="1" max="100"
                                   placeholder="Volume"
                                   @input="volumeInvalid = (volume < 1 || volume > 100 || !volume)"
                                   :class="volumeInvalid ? 'ring-2 ring-red-500 bg-red-950/30' : 'bg-navy-800'"
                                   class="w-full border-none rounded-2xl text-white placeholder-slate-500 py-4 px-5 text-sm pr-16 transition-colors">
                            <span class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-500 uppercase tracking-widest pointer-events-none">/100</span>
                        </div>
                        <p x-show="volumeInvalid"
                           class="text-red-400 text-[10px] font-bold mt-1 px-2 leading-tight"
                           x-cloak>
                            Max 100 leads per search.
                        </p>
                    </div>

                    <button type="button" @click="expanded = !expanded"
                            class="bg-navy-800 hover:bg-navy-950 text-slate-400 font-bold py-4 px-4 rounded-2xl transition-all flex items-center justify-center shrink-0"
                            :title="expanded ? 'Hide filters' : 'More filters'">
                        <svg class="w-5 h-5 transition-transform" :class="expanded ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                    </button>

                    {{-- Run Hunter button — disabled reactively when volume is invalid or limit reached --}}
                    @if($limitReached)
                        <button type="button" disabled
                                class="bg-red-500 text-white cursor-not-allowed font-bold py-4 px-8 rounded-2xl flex items-center justify-center whitespace-nowrap shrink-0 opacity-80"
                                title="{{ $searchLimitReached ? 'Search' : 'Lead' }} limit reached">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            LIMIT REACHED
                        </button>
                    @else
                        <button type="submit"
                                :disabled="volumeInvalid"
                                :class="volumeInvalid
                                    ? 'bg-slate-700 text-slate-500 cursor-not-allowed opacity-60'
                                    : 'bg-brand-blue hover:bg-blue-600 text-white shadow-lg shadow-blue-500/20 active:scale-95'"
                                class="font-bold py-4 px-8 rounded-2xl transition-all transform flex items-center justify-center whitespace-nowrap shrink-0">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            RUN HUNTER
                        </button>
                    @endif
                </div>

                {{-- Expanded optional filters --}}
                <div x-show="expanded" x-collapse class="mt-2">
                    <div class="flex flex-col md:flex-row items-stretch md:items-center space-y-2 md:space-y-0 md:space-x-2">
                        <div class="flex-1">
                            <input type="text" name="industry" placeholder="Industry (e.g. Artificial Intelligence)"
                                   class="w-full bg-navy-800 border-none rounded-2xl text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-blue py-4 px-5 text-sm">
                        </div>
                        <div class="flex-1">
                            <input type="text" name="position" placeholder='Position (e.g. "CEO" OR "Founder")'
                                   class="w-full bg-navy-800 border-none rounded-2xl text-white placeholder-slate-500 focus:ring-2 focus:ring-brand-blue py-4 px-5 text-sm">
                        </div>
                    </div>
                </div>
            </form>
        </div>

        {{-- ===== AJAX FILTERS ===== --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100 space-y-4 md:space-y-0 md:flex md:items-center md:space-x-4">
            <div class="relative flex-1">
                <span class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" x-model="filters.q" @input.debounce.300ms="fetchLeads()" placeholder="Search local leads..."
                       class="block w-full pl-10 pr-4 py-2.5 bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue transition-colors">
            </div>
            
            <div class="flex flex-wrap gap-3 items-center">
                {{-- Local Leads Filter --}}
                <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Leads Type</label>
                    <select x-model="filters.type" @change="fetchLeads()" class="bg-transparent border-none text-xs font-bold text-slate-600 focus:ring-0 py-0 pl-1 pr-8 cursor-pointer">
                        <option value="">All Leads</option>
                        <option value="local">Local Only</option>
                        <option value="scraped">Scraped Only</option>
                    </select>
                </div>

                {{-- Owner Filter (Admin only) --}}
                @if(Auth::user()->isAdmin())
                <div class="flex items-center space-x-2 bg-slate-50 border border-slate-200 px-3 py-1.5 rounded-xl">
                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Owner</label>
                    <select x-model="filters.user_id" @change="fetchLeads()" class="bg-transparent border-none text-xs font-bold text-slate-600 focus:ring-0 py-0 pl-1 pr-8 cursor-pointer">
                        <option value="">All Owners</option>
                        @foreach(\App\Models\User::orderBy('name')->get() as $u)
                            <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <button @click="resetFilters()" class="text-[10px] font-bold text-slate-400 hover:text-brand-blue uppercase tracking-widest transition-colors">
                    Reset
                </button>
            </div>
        </div>

        {{-- ===== RESULTS TABLE CONTAINER ===== --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative" id="leads-table-container">
            <div x-show="tableLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20" x-cloak>
                <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full"></div>
            </div>
            @include('leads.partials.table', ['leads' => $leads])
        </div>

        {{-- ===== LEAD DETAIL MODAL ===== --}}
        <div x-show="modalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
            {{-- Backdrop --}}
            <div @click="closeModal()" class="fixed inset-0 bg-black/60 backdrop-blur-sm"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"></div>

            {{-- Modal Panel --}}
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl max-h-[85vh] overflow-hidden z-10"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95"
                 @click.away="closeModal()">

                {{-- Loading State --}}
                <div x-show="modalLoading" class="p-12 text-center">
                    <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full mx-auto"></div>
                    <p class="text-slate-400 text-sm mt-4">Loading lead details...</p>
                </div>

                {{-- Content --}}
                <div x-show="!modalLoading && modalData" class="flex flex-col max-h-[85vh]">
                    {{-- Header --}}
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="flex items-center min-w-0">
                            <div class="w-12 h-12 rounded-2xl bg-brand-blue flex items-center justify-center text-white font-bold text-lg mr-4 shrink-0 shadow-lg shadow-blue-500/20">
                                <span x-text="modalData?.person_name?.charAt(0)?.toUpperCase() || '?'"></span>
                            </div>
                            <div class="min-w-0">
                                <h3 class="text-lg font-bold text-slate-800 truncate" x-text="modalData?.person_name || 'Unknown'"></h3>
                                <p class="text-xs text-slate-400" x-text="(modalData?.position_by_search_param || modalData?.position_by_apifiapi || 'No position') + ' at ' + (modalData?.company_name || 'Unknown')"></p>
                            </div>
                        </div>
                        <button @click="closeModal()" class="text-slate-400 hover:text-slate-600 p-2 rounded-xl hover:bg-slate-100 transition-all shrink-0">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>

                    {{-- Body --}}
                    <div class="p-6 overflow-y-auto flex-1 space-y-4">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <template x-for="(value, key) in getDisplayFields()" :key="key">
                                <div class="bg-slate-50 rounded-2xl p-4 border border-slate-100">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1" x-text="formatLabel(key)"></label>
                                    <template x-if="isUrl(value)">
                                        <a :href="value" target="_blank" class="text-brand-blue hover:underline text-sm font-medium break-all" x-text="value"></a>
                                    </template>
                                    <template x-if="!isUrl(value)">
                                        <p class="text-sm font-medium text-slate-700 break-words" x-text="value || '-'"></p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Footer --}}
                    <div class="p-4 border-t border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                        <div class="text-[10px] text-slate-400">
                            Created: <span class="font-bold" x-text="modalData?.created_at ? new Date(modalData.created_at).toLocaleDateString() : '-'"></span>
                        </div>
                        <div class="flex space-x-2">
                            <template x-if="modalData?.personal__linkdin_url">
                                <a :href="modalData.personal__linkdin_url" target="_blank"
                                   class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-xs font-bold transition-colors">
                                    OPEN LINKEDIN
                                </a>
                            </template>
                            @if(Auth::user()->isAdmin())
                            <form :action="'/leads/' + modalData?.id" method="POST" @click.stop onsubmit="return confirm('Delete this lead permanently?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="bg-red-50 hover:bg-red-100 text-red-600 px-4 py-2 rounded-xl text-xs font-bold transition-colors">
                                    DELETE
                                </button>
                            </form>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function leadManager() {
            return {
                modalOpen: false,
                modalLoading: false,
                modalData: null,
                tableLoading: false,
                filters: {
                    q: '{{ request('q') }}',
                    user_id: '{{ request('user_id') }}',
                    type: '{{ request('type') }}'
                },

                init() {
                    // Handle pagination clicks via AJAX
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('.ajax-pagination a');
                        if (link) {
                            e.preventDefault();
                            this.fetchLeads(link.href);
                        }
                    });
                },

                async fetchLeads(url = null) {
                    this.tableLoading = true;
                    try {
                        const baseUrl = url || window.location.pathname;
                        const params = new URLSearchParams(this.filters);
                        
                        // If it's a pagination URL, it already has params, so we merge them if needed
                        // But usually, pagination links have all current params in them if we use withQueryString()
                        const targetUrl = url ? url : `${baseUrl}?${params.toString()}`;

                        const response = await fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (response.ok) {
                            const html = await response.text();
                            document.getElementById('leads-table-container').innerHTML = html;
                            
                            // Update browser URL without refresh
                            if (!url) {
                                window.history.pushState({}, '', targetUrl);
                            } else {
                                window.history.pushState({}, '', url);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch leads:', e);
                    } finally {
                        this.tableLoading = false;
                    }
                },

                resetFilters() {
                    this.filters = { q: '', user_id: '', type: '' };
                    this.fetchLeads();
                },

                async openModal(leadId) {
                    this.modalOpen = true;
                    this.modalLoading = true;
                    this.modalData = null;
                    try {
                        const response = await fetch(`/leads/${leadId}/json`);
                        if (response.ok) {
                            this.modalData = await response.json();
                        }
                    } catch (e) {
                        console.error('Failed to load lead:', e);
                    } finally {
                        this.modalLoading = false;
                    }
                },

                closeModal() {
                    this.modalOpen = false;
                    this.modalData = null;
                },

                getDisplayFields() {
                    if (!this.modalData) return {};
                    const exclude = ['id', 'main_search_query', 'imported_at', 'updated_at', 'lead_search_id', 'user_id', 'email_sent', 'source', 'created_at'];
                    const result = {};
                    for (const [key, value] of Object.entries(this.modalData)) {
                        if (!exclude.includes(key) && value !== null && value !== '') {
                            result[key] = value;
                        }
                    }
                    return result;
                },

                formatLabel(key) {
                    return key
                        .replace(/_/g, ' ')
                        .replace(/by search param/gi, '')
                        .replace(/by apifiapi/gi, '(API)')
                        .replace(/by apifyapi/gi, '(API)')
                        .replace(/personal /gi, '')
                        .replace(/company /gi, 'Company ')
                        .replace(/  +/g, ' ')
                        .trim();
                },

                isUrl(value) {
                    if (typeof value !== 'string') return false;
                    return value.startsWith('http://') || value.startsWith('https://');
                }
            };
        }
    </script>
</x-app-layout>
