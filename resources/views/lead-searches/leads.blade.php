<x-app-layout>
    <x-slot name="header">
        Leads for Extraction
    </x-slot>

    <x-slot name="subheader">
        This is the list of leads that were collected for the extraction.
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('lead-searches.index') }}" class="bg-white border border-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center shadow-sm">
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
                    <p class="text-sm font-bold text-slate-800 truncate uppercase" title="{{ $leadSearch->position ?: 'Any Position' }}">
                        {{ $leadSearch->position ?: 'Any Position' }}
                    </p>
                </div>

                {{-- Location --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Location</p>
                    <p class="text-sm font-bold text-slate-800 truncate uppercase" title="{{ $leadSearch->target_location }}">
                       
                        {{ $leadSearch->target_location }}
                        
                    </p>
                </div>

                {{-- Industry --}}
                <div class="space-y-1">
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Industry</p>
                    <p class="text-sm font-bold text-slate-800 truncate uppercase" title="{{ $leadSearch->industry ?: 'Any Industry' }}">
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

            {{-- Right: Actions --}}
            <div class="flex items-center space-x-3 shrink-0">
                <button x-bind:disabled="selectedLeadIds.length === 0" @click="showDispatchModal = true" 
                        :class="selectedLeadIds.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300 text-slate-500 shadow-none' : 'bg-brand-blue text-white hover:bg-blue-600 shadow-lg shadow-blue-500/20 active:scale-95'" 
                        class="px-6 py-2 rounded-xl text-sm font-bold transition-all flex items-center h-[42px]">
                    EMAIL OUTREACH (<span x-text="selectedLeadIds.length"></span>)
                </button>
            </div>
        </div>


        {{-- Results Container --}}
        <div id="leads-container" class="transition-opacity duration-200" :class="loading ? 'opacity-50' : 'opacity-100'">
            @include('lead-searches.partials.leads-table', ['leads' => $leads, 'leadSearch' => $leadSearch])
        </div>

        {{-- Detail Modal --}}
        @include('leads.partials.details-modal-v2')

        {{-- Dispatch Modal --}}
        <div x-show="showDispatchModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="w-full max-w-5xl h-[90vh] bg-white rounded-xl flex overflow-hidden shadow-2xl relative" @click.stop>
                
                {{-- Left Pane --}}
                <div class="w-1/3 bg-gray-50 flex flex-col border-r border-slate-200">
                    <div class="p-6 border-b border-slate-200 bg-white shrink-0">
                        <h3 class="font-bold text-slate-800">Selected Targets</h3>
                        <div class="mt-4 text-2xl font-black text-brand-blue" x-text="selectedLeadIds.length"></div>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4 custom-scrollbar">
                        <template x-for="id in selectedLeadIds" :key="id">
                            <div class="p-4 bg-white border border-slate-200 rounded-xl mb-2 flex flex-col hover:border-brand-blue/30 transition-colors shadow-sm">
                                <span class="text-sm font-black text-slate-800" 
                                      x-text="(leadsData.data.find(l => l.id == id) || {}).full_name || 'Unknown Name'"></span>
                                <span class="text-[11px] font-bold text-slate-500 mt-1" 
                                      x-text="(leadsData.data.find(l => l.id == id) || {}).job_title || 'No Title Available'"></span>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Right Pane --}}
                <div class="w-2/3 flex flex-col bg-white">
                    <form action="{{ route('leads.dispatch') }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col overflow-hidden">
                        @csrf
                        <template x-for="id in selectedLeadIds">
                            <input type="hidden" name="lead_ids[]" :value="id">
                        </template>

                        <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6 custom-scrollbar">
                            {{-- Delivery Mode --}}
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-3">Delivery Mode *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    {{-- Save as Draft --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" name="delivery_mode" value="Save as Draft" class="peer sr-only" required checked>
                                        <div class="p-3 bg-white border border-slate-200 rounded-xl peer-checked:border-brand-blue peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center">
                                            <svg class="w-5 h-5 text-slate-400 peer-checked:text-brand-blue mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                                            <span class="text-xs font-bold text-slate-700 peer-checked:text-brand-blue">Save as Draft</span>
                                        </div>
                                    </label>
                                    {{-- Sent Immediately --}}
                                    <label class="cursor-pointer">
                                        <input type="radio" name="delivery_mode" value="Send Immediately" class="peer sr-only">
                                        <div class="p-3 bg-white border border-slate-200 rounded-xl peer-checked:border-brand-blue peer-checked:bg-blue-50 transition-all flex flex-col items-center justify-center text-center">
                                            <svg class="w-5 h-5 text-slate-400 peer-checked:text-brand-blue mb-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                                            <span class="text-xs font-bold text-slate-700 peer-checked:text-brand-blue">Send Immediately</span>
                                        </div>
                                    </label>                                  
                                </div>
                            </div>

                            {{-- Unified Template Selection --}}
                            <div>
                                <label class="text-xs font-bold text-slate-400">Template</label>
                                <select x-model="selectedTemplate" 
                                        @change="let t = templatesData.find(temp => temp.id == selectedTemplate); if(t) { form.subject = t.subject; form.body = t.body; form.sender_name = t.signature_name; form.sender_role = t.signature_position; form.sender_company = t.signature_company; form.sender_address = t.signature_address; } else { form.subject = ''; form.body = ''; form.sender_name = ''; form.sender_role = ''; form.sender_company = ''; form.sender_address = ''; }"
                                        class="w-full bg-slate-50 border-slate-200 rounded-xl py-3 mt-1 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <option value="">Custom Template</option>
                                    <template x-for="t in templatesData" :key="t.id">
                                        <option :value="t.id" x-text="t.name"></option>
                                    </template>
                                </select>
                            </div>

                            {{-- Subject --}}
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Subject *</label>
                                <input type="text" name="subject" x-model="form.subject" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4 transition-all" placeholder="e.g. Quick question regarding [Company]">
                            </div>

                            {{-- Body --}}
                            <div>
                                <label class="text-xs font-bold text-slate-400">Body</label>
                                <textarea name="body" x-model="form.body" rows="6" required class="w-full bg-slate-50 border-slate-200 rounded-xl mt-1 p-4 text-sm focus:ring-brand-blue focus:border-brand-blue" placeholder="Hi..."></textarea>
                            </div>

                            {{-- Signature / Context --}}
                            <div>
                                <label class="text-xs font-bold text-slate-400 mb-2 block">Signature Context (Optional)</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <input name="sender_name" x-model="form.sender_name" placeholder="Sender Name" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_role" x-model="form.sender_role" placeholder="Sender Role/Position" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_company" x-model="form.sender_company" placeholder="Sender Company" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_address" x-model="form.sender_address" placeholder="Sender Address" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                            </div>

                            {{-- Attachments --}}
                            <div>
                                <label class="text-xs font-bold text-slate-400 mb-2 block">Attachments (Optional)</label>
                                <div class="w-full flex items-center justify-center p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-slate-100 hover:border-brand-blue transition-colors cursor-pointer relative">
                                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,image/*" @change="files = $event.target.files" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="text-center pointer-events-none">
                                        <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        <span class="mt-2 block text-sm font-semibold text-slate-700">Drop files here or click to upload</span>
                                    </div>
                                </div>
                                <div x-show="files.length > 0" class="mt-2 text-xs font-bold text-brand-blue">
                                    <span x-text="files.length + ' file(s) selected'"></span>
                                </div>
                            </div>
                        </div>

                        {{-- Sticky Footer --}}
                        <div class="p-4 md:p-6 border-t border-slate-200 bg-white flex justify-end gap-3 shrink-0">
                            <button type="button" @click="showDispatchModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">Cancel Session</button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/30">Initiate Automation Sequence</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        {{-- Email Preview Modal --}}
        <div x-show="showEmailPreviewModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4">
            <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl overflow-hidden relative" @click.away="showEmailPreviewModal = false">
                {{-- Header --}}
                <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                    <div>
                        <h3 class="font-bold text-slate-800 text-lg">Email Preview</h3>
                        <p class="text-sm text-slate-500 mt-1" x-text="previewSubject || 'No Subject'"></p>
                    </div>
                    <button @click="showEmailPreviewModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition-all">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                {{-- Body --}}
                <div class="p-6 overflow-y-auto max-h-[70vh] custom-scrollbar bg-slate-50">
                    {{-- Hyper-Personalized Callout --}}
                    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-6 rounded-r shadow-sm">
                        <h4 class="text-xs font-bold text-blue-800 uppercase tracking-widest mb-2">AI Hyper-Personalized Icebreaker</h4>
                        <p class="text-sm text-blue-900 italic leading-relaxed" x-text="previewHyperLine || 'No personalization generated.'"></p>
                    </div>

                    {{-- HTML Email Body --}}
                    <div class="bg-white p-6 rounded-xl border border-slate-200 shadow-sm">
                        <div x-html="previewBody || 'No email drafted yet.'" class="prose max-w-none text-gray-800 text-sm"></div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="p-4 border-t border-slate-200 bg-white flex justify-end shrink-0">
                    <button type="button" @click="showEmailPreviewModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">Close</button>
                </div>
            </div>
        </div>
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
                totalLeadsCount: {{ $leads->total() }},
                filters: {
                    q: '',
                    page: 1
                },
                leadsData: @json($leads),
                selectedLeadIds: [],
                selectAll: false,
                showDispatchModal: false,
                templatesData: @json($templates ?? []),
                selectedTemplate: '',
                form: {
                    subject: '',
                    body: '',
                    sender_name: '',
                    sender_role: '',
                    sender_company: '',
                    sender_address: ''
                },
                showEmailPreviewModal: false,
                previewSubject: '',
                previewHyperLine: '',
                previewBody: '',
                openPreview(subject, hyperLine, body) {
                    this.previewSubject = subject;
                    this.previewHyperLine = hyperLine;
                    this.previewBody = body;
                    this.showEmailPreviewModal = true;
                },
                
                outreachPollInterval: null,

                init() {
                    window.leadManager = this;
                    this.syncOutreachPolling();
                    
                    const defaultTemplate = this.templatesData.find(t => t.is_default);
                    if (defaultTemplate) {
                        this.selectedTemplate = defaultTemplate.id;
                        this.form.subject = defaultTemplate.subject;
                        this.form.body = defaultTemplate.body;
                        this.form.sender_name = defaultTemplate.signature_name || '';
                        this.form.sender_role = defaultTemplate.signature_position || '';
                        this.form.sender_company = defaultTemplate.signature_company || '';
                        this.form.sender_address = defaultTemplate.signature_address || '';
                    }

                    this.$watch('selectedLeadIds', (val) => {
                        const totalCheckboxes = document.querySelectorAll('.lead-checkbox').length;
                        this.selectAll = val.length > 0 && val.length === totalCheckboxes;
                    });
                },
                toggleSelectAll() {
                    if (this.selectAll) {
                        const checkboxes = document.querySelectorAll('.lead-checkbox');
                        this.selectedLeadIds = Array.from(checkboxes).map(cb => cb.value);
                    } else {
                        this.selectedLeadIds = [];
                    }
                },

                hasPendingOutreach() {
                    const container = document.getElementById('leads-container')?.firstElementChild;

                    return container?.dataset?.hasPendingOutreach === '1';
                },

                syncOutreachPolling() {
                    if (this.hasPendingOutreach()) {
                        this.startOutreachPolling();
                    } else if (this.outreachPollInterval) {
                        clearInterval(this.outreachPollInterval);
                        this.outreachPollInterval = null;
                    }
                },

                startOutreachPolling() {
                    if (this.outreachPollInterval) {
                        return;
                    }

                    this.outreachPollInterval = setInterval(() => {
                        if (document.activeElement?.matches('input[type="text"], textarea')) {
                            return;
                        }

                        if (!this.hasPendingOutreach()) {
                            clearInterval(this.outreachPollInterval);
                            this.outreachPollInterval = null;
                            return;
                        }

                        this.fetchLeads(true);
                    }, 5000);
                },

                fetchLeads(isSilent = false) {
                    if (!isSilent) {
                        this.loading = true;
                    }

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
                        const container = document.querySelector('[data-total-count]');
                        if (container) {
                            this.totalLeadsCount = parseInt(container.getAttribute('data-total-count'));
                        }

                        this.syncOutreachPolling();
                        this.loading = false;
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
                            { label: 'Industry', key: 'industry' },
                            { label: 'Company Name', key: 'company_name' },
                            { label: 'Website', key: 'company_website', isUrl: true },
                            { label: 'LinkedIn', key: 'company_linkedin', isUrl: true },
                            { label: 'City', key: 'company_city' },
                            { label: 'Country', key: 'company_country' },
                            { label: 'Address', key: 'company_address' },
                            { label: 'State', key: 'company_state' },
                            { label: 'Domain', key: 'company_domain' },
                            { label: 'Description', key: 'company_description' },
                            { label: 'Annual Revenue', key: 'company_annual_revenue' },
                            { label: 'Total Funding', key: 'company_total_funding' },
                            { label: 'Technology', key: 'company_technology' }
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
