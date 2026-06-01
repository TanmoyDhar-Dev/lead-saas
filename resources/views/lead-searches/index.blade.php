<x-app-layout>
    <x-slot name="header">
        Leads
    </x-slot>

    <x-slot name="subheader">
        LeadFlow AI Intelligence • Extraction History
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('lead-searches.create') }}"
            class="bg-brand-blue text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-all flex items-center shadow-lg shadow-blue-500/20 active:scale-95">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            LEAD HUNTER
        </a>
    </x-slot>

    <div class="space-y-6" x-data="leadHistoryManager()">
        {{-- Compact Filter Bar --}}
        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100">
            <div class="flex flex-col lg:flex-row items-center gap-4">
                {{-- Search history input --}}
                <div class="relative flex-1 w-full">
                    <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                        <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                        </svg>
                    </span>
                    <input type="text" x-model="filters.q" @input.debounce.300ms="fetchHistory()"
                        placeholder="Search by country, city, industry, position..."
                        class="block w-full pl-10 pr-4 py-2 bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue transition-all">
                </div>

                <div class="flex flex-wrap items-center gap-3 w-full lg:w-auto">
                    {{-- Owner Dropdown (Admin only) --}}
                    @if(Auth::user()->isAdmin())
                        <div class="flex-1 lg:w-48 min-w-[160px]">
                            <select x-model="filters.user_id" @change="fetchHistory()"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2">
                                <option value="">All Owners</option>
                                @foreach(\App\Models\User::orderBy('name')->get() as $u)
                                    <option value="{{ $u->id }}">{{ $u->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    {{-- Reset Button --}}
                    <button @click="resetFilters()"
                        class="text-xs font-bold text-slate-400 hover:text-brand-blue transition-colors flex items-center px-2 py-2 shrink-0">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15">
                            </path>
                        </svg>
                        RESET
                    </button>
                </div>
            </div>
        </div>

        {{-- Results Container --}}
        <div id="history-container" class="transition-opacity duration-200"
            :class="loading ? 'opacity-50' : 'opacity-100'">
            @include('lead-searches.partials.table', ['searches' => $searches])
        </div>
    </div>

    @push('scripts')
        <script>
            function leadHistoryManager() {
                return {
                    loading: false,
                    pollInterval: null, // Track the polling timer
                    filters: {
                        q: '',
                        status: '',
                        user_id: '',
                        page: 1
                    },
                    init() {
                        // Start checking for updates as soon as the page loads
                        this.startPolling();
                    },
                    startPolling() {
                        this.pollInterval = setInterval(() => {
                            // Don't interrupt the user if they are currently typing in the search box
                            if (document.activeElement !== document.querySelector('input[type="text"]')) {
                                this.fetchHistory(true);
                            }
                        }, 5000); // Check every 5 seconds
                    },
                    fetchHistory(isSilent = false) {
                        if (!isSilent) this.loading = true; // Only show loading spinner on manual actions

                        let params = new URLSearchParams(this.filters);

                        fetch("{{ route('lead-searches.index') }}?" + params.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' }
                        })
                            .then(r => r.text())
                            .then(html => {
                                document.getElementById('history-container').innerHTML = html;
                                this.loading = false;

                                // Smart Polling Logic: Stop polling if no searches are currently "Processing"
                                if (!html.includes('animate-pulse') && !html.includes('Processing')) {
                                    clearInterval(this.pollInterval);
                                    this.pollInterval = null;
                                } else if (!this.pollInterval) {
                                    this.startPolling(); // Restart if a new processing task appears
                                }
                            })
                            .catch(() => {
                                this.loading = false;
                            });
                    },
                    resetFilters() {
                        this.filters = { q: '', status: '', user_id: '', page: 1 };
                        this.fetchHistory();
                    }
                }
            }

            // Handle pagination clicks via AJAX
            document.addEventListener('click', function (e) {
                if (e.target.closest('#history-container .pagination a')) {
                    e.preventDefault();
                    let url = new URL(e.target.closest('a').href);
                    let page = url.searchParams.get('page');
                    let app = Alpine.evaluate(document.querySelector('[x-data="leadHistoryManager()"]'), 'filters');
                    app.page = page;
                    Alpine.evaluate(document.querySelector('[x-data="leadHistoryManager()"]'), 'fetchHistory()');
                }
            });
        </script>
    @endpush
</x-app-layout>