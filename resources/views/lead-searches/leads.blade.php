<x-app-layout>
    <x-slot name="header">
        Leads for Extraction
    </x-slot>

    <x-slot name="subheader">
        {{ $leadSearch->city }}, {{ $leadSearch->country }} • {{ $leadSearch->industry ?: 'Any Industry' }}
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('lead-searches.index') }}" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            BACK TO HISTORY
        </a>
    </x-slot>

    <div class="space-y-6" x-data="scopedLeadManager()">
        {{-- Compact Summary Grid --}}
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-6">
                {{-- Position --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target Position</p>
                    <p class="text-sm font-bold text-slate-800 truncate" title="{{ $leadSearch->position ?: 'Any Position' }}">
                        {{ $leadSearch->position ?: 'Any Position' }}
                    </p>
                </div>

                {{-- Location --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Location</p>
                    <p class="text-sm font-bold text-slate-800 truncate" title="{{ $leadSearch->city }}, {{ $leadSearch->country }}">
                        {{ $leadSearch->city }}, {{ $leadSearch->country }}
                    </p>
                </div>

                {{-- Industry --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Industry</p>
                    <p class="text-sm font-bold text-slate-800 truncate" title="{{ $leadSearch->industry ?: 'Any Industry' }}">
                        {{ $leadSearch->industry ?: 'Any Industry' }}
                    </p>
                </div>

                {{-- Volume --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Target Volume</p>
                    <p class="text-sm font-bold text-slate-800">{{ $leadSearch->volume ?: 10 }}</p>
                </div>

                {{-- Collected --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Collected</p>
                    <p class="text-sm font-bold text-brand-blue">{{ $leads->total() }}</p>
                </div>

                {{-- Status --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status</p>
                    <div>
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $leadSearch->status === 'completed' ? 'bg-emerald-50 text-emerald-600' : ($leadSearch->status === 'failed' ? 'bg-red-50 text-red-600' : 'bg-blue-50 text-blue-600 animate-pulse') }}">
                            {{ $leadSearch->status }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Improved Filter & Action Bar --}}
        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            {{-- Left: Search --}}
            <div class="relative flex-1 max-w-xl">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" x-model="filters.q" @input.debounce.250ms="fetchLeads()" 
                       placeholder="Filter leads by name, email, company, position..." 
                       class="block w-full pl-10 pr-10 py-2 bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue transition-all">
                <button x-show="filters.q" @click="filters.q = ''; fetchLeads()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Right: Selection Actions --}}
            <div class="flex items-center gap-3">
                <div class="flex items-center bg-slate-50 px-3 py-1.5 rounded-xl border border-slate-100">
                    <span class="text-[10px] font-bold text-slate-500 uppercase tracking-widest mr-2">Selected:</span>
                    <span class="bg-brand-blue text-white w-5 h-5 rounded-md flex items-center justify-center text-[10px] font-bold" x-text="selectAllTotal ? totalLeadsCount : selectedIds.length">0</span>
                </div>

                <div class="flex items-center gap-2">
                    <form action="{{ route('campaigns.from-lead-search') }}" method="POST">
                        @csrf
                        <input type="hidden" name="lead_search_id" value="{{ $leadSearch->id }}">
                        <input type="hidden" name="select_all_leads" :value="selectAllTotal ? 1 : 0">
                        <template x-if="!selectAllTotal">
                            <template x-for="id in selectedIds" :key="id">
                                <input type="hidden" name="selected_lead_ids[]" :value="id">
                            </template>
                        </template>
                        <button type="submit" 
                                :disabled="selectedIds.length === 0 && !selectAllTotal"
                                :class="selectedIds.length === 0 && !selectAllTotal ? 'opacity-50 cursor-not-allowed bg-slate-100 text-slate-400' : 'bg-brand-blue text-white hover:bg-blue-600 shadow-blue-500/10'"
                                class="px-5 py-2.5 rounded-xl text-xs font-bold transition-all shadow-sm active:scale-95 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            CONTINUE TO CAMPAIGN
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="px-4" x-show="selectedIds.length === 0">
            <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest flex items-center">
                <svg class="w-3 h-3 mr-1 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                Select leads to continue to campaign.
            </p>
        </div>

        {{-- Select All Total Banner --}}
        <div x-show="selectAll && !selectAllTotal && selectedIds.length > 0 && selectedIds.length < totalLeadsCount" 
             x-transition 
             class="mx-4 bg-blue-50 border border-blue-100 p-3 rounded-2xl flex items-center justify-between">
            <div class="text-xs text-blue-700">
                All <span class="font-bold" x-text="selectedIds.length"></span> leads on this page are selected.
                <button @click="selectAllTotalLeads()" class="ml-2 font-bold underline hover:text-blue-800">Select all <span x-text="totalLeadsCount"></span> leads in this extraction</button>
            </div>
        </div>

        <div x-show="selectAllTotal" 
             x-transition 
             class="mx-4 bg-brand-blue border border-blue-600 p-3 rounded-2xl flex items-center justify-between shadow-lg shadow-blue-500/10">
            <div class="text-xs text-white">
                <span class="font-bold text-white">All <span x-text="totalLeadsCount"></span> leads</span> in this extraction are selected.
                <button @click="resetSelection()" class="ml-2 font-bold underline text-white/80 hover:text-white">Clear selection</button>
            </div>
        </div>

        {{-- Results Container --}}
        <div id="leads-container" class="transition-opacity duration-200" :class="loading ? 'opacity-50' : 'opacity-100'">
            @include('lead-searches.partials.leads-table', ['leads' => $leads, 'leadSearch' => $leadSearch])
        </div>

        {{-- Detail Modal --}}
        @include('leads.partials.details-modal-v2')
    </div>

    @push('scripts')
    <script>
        function scopedLeadManager() {
            return {
                loading: false,
                modalOpen: false,
                modalLoading: false,
                modalError: null,
                modalLeadId: null,
                modalData: null,
                selectedIds: [],
                selectAll: false,
                selectAllTotal: false,
                totalLeadsCount: {{ $leads->total() }},
                filters: {
                    q: '',
                    page: 1
                },
                
                init() {
                    window.leadManager = this;
                },

                toggleSelectAll() {
                    const checkboxes = document.querySelectorAll('.lead-checkbox');
                    const allIdsOnPage = Array.from(checkboxes).map(cb => cb.value);
                    
                    if (this.selectAll) {
                        // Add only those not already selected
                        allIdsOnPage.forEach(id => {
                            if (!this.selectedIds.includes(id)) {
                                this.selectedIds.push(id);
                            }
                        });
                    } else {
                        // Remove current page IDs from selection
                        this.selectedIds = this.selectedIds.filter(id => !allIdsOnPage.includes(id));
                        this.selectAllTotal = false;
                    }
                },

                selectAllTotalLeads() {
                    this.selectAllTotal = true;
                    // For UI feedback we can show a large number or just manage it via flag
                    // But the forms need the IDs. We'll fetch all IDs via AJAX if needed, 
                    // or just use a flag in the form. Let's use a flag.
                },

                resetSelection() {
                    this.selectedIds = [];
                    this.selectAll = false;
                    this.selectAllTotal = false;
                },

                updateSelectAllState() {
                    const checkboxes = document.querySelectorAll('.lead-checkbox');
                    const allIdsOnPage = Array.from(checkboxes).map(cb => cb.value);
                    
                    if (allIdsOnPage.length === 0) {
                        this.selectAll = false;
                    } else {
                        this.selectAll = allIdsOnPage.every(id => this.selectedIds.includes(id));
                    }
                    
                    if (!this.selectAll) {
                        this.selectAllTotal = false;
                    }
                },

                fetchLeads() {
                    this.loading = true;
                    let params = new URLSearchParams(this.filters);
                    
                    fetch("{{ route('lead-searches.leads', $leadSearch) }}?" + params.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(r => r.text())
                    .then(html => {
                        document.getElementById('leads-container').innerHTML = html;
                        if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                            window.Alpine.initTree(document.getElementById('leads-container'));
                        }
                        // Update total count from the new HTML
                        const container = document.querySelector('[data-total-count]');
                        if (container) {
                            this.totalLeadsCount = parseInt(container.getAttribute('data-total-count'));
                        }
                        
                        this.loading = false;
                        this.updateSelectAllState();
                    })
                    .catch(() => {
                        this.loading = false;
                    });
                },

                openModal(leadId) {
                    this.modalOpen = true;
                    this.modalLoading = true;
                    this.modalError = null;
                    this.modalData = null;
                    this.modalLeadId = leadId;
                    const base = @js(url('/lead-searches/'.$leadSearch->id.'/leads'));
                    const url = base + '/' + encodeURIComponent(leadId) + '/json';
                    fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                        }
                    })
                    .then(async (r) => {
                        if (!r.ok) {
                            throw new Error('request_failed');
                        }
                        return r.json();
                    })
                    .then((data) => {
                        this.modalData = data;
                        this.modalLoading = false;
                    })
                    .catch(() => {
                        this.modalError = 'Unable to load lead details. You may not have access, or the lead is no longer part of this extraction.';
                        this.modalLoading = false;
                    });
                },

                closeModal() {
                    this.modalOpen = false;
                    this.modalData = null;
                    this.modalError = null;
                    this.modalLeadId = null;
                },

                getSectionFields(section) {
                    if (!this.modalData) return [];
                    
                    const sections = {
                        person: [
                            { label: 'Full Name', key: 'full_name' },
                            { label: 'Job Title', key: 'job_title' },
                            { label: 'Position', key: 'position' },
                            { label: 'Address', key: 'address' },
                            { label: 'Bio', key: 'bio' },
                            { label: 'LinkedIn URL', key: 'linkedin_url', isUrl: true },
                            { label: 'Personal Email', key: 'personal_email' },
                            { label: 'Company Email', key: 'company_email' }
                        ],
                        company: [
                            { label: 'Company Name', key: 'company_name' },
                            { label: 'Industry', key: 'industry' },
                            { label: 'Website', key: 'company_website', isUrl: true },
                            { label: 'LinkedIn', key: 'company_linkedin', isUrl: true },
                            { label: 'Domain', key: 'company_domain' },
                            { label: 'Description', key: 'company_description' },
                            { label: 'Revenue', key: 'company_annual_revenue' },
                            { label: 'Funding', key: 'company_total_funding' },
                            { label: 'Technology', key: 'company_technology' }
                        ],
                        location: [
                            { label: 'City', key: 'company_city' },
                            { label: 'State', key: 'company_state' },
                            { label: 'Country', key: 'company_country' },
                            { label: 'Address', key: 'company_address' }
                        ],
                        search: [
                            { label: 'Position', key: 'position' },
                            { label: 'Industry', key: 'industry' }
                        ],
                        status: [
                            { label: 'Added At', key: 'created_at_human' },
                            { label: 'Last Updated', key: 'updated_at_human' }
                        ]
                    };

                    return (sections[section] || []).map(f => ({
                        ...f,
                        value: this.modalData[f.key]
                    }));
                }
            }
        }

        window.scopedLeadManager = scopedLeadManager;

        // Handle pagination via AJAX (delegation survives innerHTML table refresh)
        document.addEventListener('click', function(e) {
            // Pagination
            if (e.target.closest('#leads-container .pagination a') && window.leadManager) {
                e.preventDefault();
                let url = new URL(e.target.closest('a').href);
                let page = url.searchParams.get('page');
                window.leadManager.filters.page = page;
                window.leadManager.fetchLeads();
            }
        });
    </script>
    @endpush
</x-app-layout>
